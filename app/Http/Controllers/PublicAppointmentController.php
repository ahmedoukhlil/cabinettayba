<?php

namespace App\Http\Controllers;

use App\Models\Infocabinet;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\PublicAppointmentRequest;
use App\Models\Rendezvou;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicAppointmentController extends Controller
{
    private WebPushService $webPush;

    public function __construct(WebPushService $webPush)
    {
        $this->webPush = $webPush;
    }

    public function create()
    {
        $cabinetId = $this->cabinetId();

        return view('public.appointments.create', [
            'cabinet' => Infocabinet::find($cabinetId),
            'medecins' => Medecin::where('fkidcabinet', $cabinetId)
                ->orderBy('Nom')
                ->get(['idMedecin', 'Nom']),
        ]);
    }

    public function availability(Request $request)
    {
        $cabinetId = $this->cabinetId();
        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:' . now()->addDays(config('appointments.booking_horizon_days'))->toDateString(),
            ],
            'medecin_id' => [
                'required',
                'integer',
                Rule::exists('medecins', 'idMedecin')->where(fn ($query) => $query->where('fkidcabinet', $cabinetId)),
            ],
        ]);

        return response()->json([
            'slots' => $this->availableSlots($cabinetId, (int) $validated['medecin_id'], $validated['date']),
        ]);
    }

    public function lookupPatient(Request $request)
    {
        $validated = $request->validate([
            'telephone' => $this->telephoneRules(),
        ], $this->telephoneValidationMessages());

        $patient = $this->findPatientByTelephone(
            $this->cabinetId(),
            $validated['telephone']
        );

        if (!$patient) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'name' => $this->patientDisplayName($patient),
        ]);
    }

    public function store(Request $request)
    {
        $cabinetId = $this->cabinetId();
        $validated = $request->validate([
            'nom' => ['nullable', 'string', 'max:120'],
            'telephone' => $this->telephoneRules(),
            'medecin_id' => [
                'required',
                'integer',
                Rule::exists('medecins', 'idMedecin')
                    ->where(fn ($query) => $query->where('fkidcabinet', $cabinetId)),
            ],
            'periode' => ['required', Rule::in(['morning', 'evening'])],
            'website' => ['nullable', 'max:0'],
        ], array_merge($this->telephoneValidationMessages(), [
            'nom.required' => 'يرجى إدخال الاسم الكامل.',
            'nom.min' => 'يجب أن يحتوي الاسم على حرفين على الأقل.',
            'medecin_id.required' => 'يرجى اختيار الطبيب.',
            'medecin_id.exists' => 'الطبيب المختار غير متاح.',
            'periode.required' => 'يرجى اختيار الفترة المناسبة.',
            'periode.in' => 'الفترة المختارة غير صالحة.',
        ]));

        $telephone = $this->normalizeTelephone($validated['telephone']);
        $patient = $this->findPatientByTelephone($cabinetId, $telephone);

        if ($patient) {
            $nom = $this->patientDisplayName($patient);
        } else {
            $request->validate([
                'nom' => ['required', 'string', 'min:2', 'max:120'],
            ], [
                'nom.required' => 'يرجى إدخال الاسم الكامل.',
                'nom.min' => 'يجب أن يحتوي الاسم على حرفين على الأقل.',
            ]);
            $nom = trim($validated['nom']);
        }

        $token = Str::random(64);
        $periodLabel = $validated['periode'] === 'morning' ? 'الفترة الصباحية' : 'الفترة المسائية';
        $reference = null;
        $appointmentRequest = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $slot = $this->firstAvailableSlot(
                $cabinetId,
                (int) $validated['medecin_id'],
                $validated['periode']
            );
            if (!$slot) {
                return back()->withErrors([
                    'periode' => 'لا يوجد موعد متاح في هذه الفترة حالياً. يرجى اختيار الفترة الأخرى أو الاتصال بالعيادة.',
                ])->withInput();
            }

            $reference = $this->generateReference();
            $slotKey = hash('sha256', implode('|', [
                $cabinetId,
                $slot['medecin_id'],
                $slot['date'],
                $slot['heure'],
            ]));

            try {
                $appointmentRequest = PublicAppointmentRequest::create([
                    'fkidcabinet' => $cabinetId,
                    'fkidmedecin' => $slot['medecin_id'],
                    'fkidacte' => null,
                    'nom' => $nom,
                    'prenom' => '',
                    'telephone' => $telephone,
                    'patient_id' => $patient ? $patient->ID : null,
                    'date_souhaitee' => $slot['date'],
                    'heure_souhaitee' => $slot['heure'],
                    'message' => 'الفترة المطلوبة: ' . $periodLabel,
                    'status' => 'pending',
                    'reference' => $reference,
                    'tracking_token_hash' => hash('sha256', $token),
                    'slot_key' => $slotKey,
                ]);
                break;
            } catch (QueryException $exception) {
                $reference = null;
            }
        }

        if (!$reference) {
            return back()->withErrors([
                'periode' => 'تعذر حجز الموعد الآن. يرجى المحاولة مرة أخرى بعد قليل.',
            ])->withInput();
        }

        $this->webPush->notifyStaffForNewRequest($appointmentRequest);

        return redirect()->route('public.appointment.show', [
            'reference' => $reference,
            'token' => $token,
        ]);
    }

    public function show(string $reference, string $token)
    {
        $appointmentRequest = PublicAppointmentRequest::where('reference', $reference)->firstOrFail();
        abort_unless(
            hash_equals($appointmentRequest->tracking_token_hash, hash('sha256', $token)),
            404
        );

        return view('public.appointments.show', compact('appointmentRequest'));
    }

    private function availableSlots(int $cabinetId, int $medecinId, string $date): array
    {
        $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        if ($day->isSunday()) {
            return [];
        }

        $occupied = Rendezvou::where('fkidcabinet', $cabinetId)
            ->where('fkidMedecin', $medecinId)
            ->whereDate('dtPrevuRDV', $date)
            ->where(function ($query) {
                $query->whereNull('rdvConfirmer')
                    ->orWhere('rdvConfirmer', '!=', 'annulé');
            })
            ->pluck('HeureRdv')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'));

        $requested = PublicAppointmentRequest::where('fkidcabinet', $cabinetId)
            ->where('fkidmedecin', $medecinId)
            ->whereDate('date_souhaitee', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('heure_souhaitee')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'));

        $unavailable = $occupied->merge($requested)->unique()->all();
        $start = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . config('appointments.opening_time'));
        $end = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . config('appointments.closing_time'));
        $duration = max(10, (int) config('appointments.slot_duration_minutes'));
        $slots = [];

        while ($start->lt($end)) {
            $slot = $start->format('H:i');
            if (!in_array($slot, $unavailable, true) && $start->isFuture()) {
                $slots[] = $slot;
            }
            $start->addMinutes($duration);
        }

        return $slots;
    }

    private function firstAvailableSlot(int $cabinetId, int $medecinId, string $period): ?array
    {
        $horizon = max(1, (int) config('appointments.booking_horizon_days'));
        $periodLimit = max(1, (int) config('appointments.period_limit', 20));

        for ($offset = 0; $offset <= $horizon; $offset++) {
            $date = now()->startOfDay()->addDays($offset)->toDateString();

            if ($this->periodBookingCount($cabinetId, $date, $period) >= $periodLimit) {
                continue;
            }

            foreach ($this->availableSlots($cabinetId, $medecinId, $date) as $slot) {
                if ($this->slotMatchesPeriod($slot, $period)) {
                    return [
                        'medecin_id' => $medecinId,
                        'date' => $date,
                        'heure' => $slot,
                    ];
                }
            }
        }

        return null;
    }

    private function periodBookingCount(int $cabinetId, string $date, string $period): int
    {
        $scheduledQuery = Rendezvou::where('fkidcabinet', $cabinetId)
            ->whereDate('dtPrevuRDV', $date)
            ->where(function ($query) {
                $query->whereNull('rdvConfirmer')
                    ->orWhere('rdvConfirmer', '!=', 'annulé');
            });

        // Une demande confirmée possède déjà un rendez-vous et ne doit pas
        // être comptée deux fois. Seules les demandes en attente s'ajoutent.
        $pendingQuery = PublicAppointmentRequest::where('fkidcabinet', $cabinetId)
            ->whereDate('date_souhaitee', $date)
            ->where('status', 'pending');

        if ($period === 'morning') {
            $scheduledQuery
                ->whereTime('HeureRdv', '>=', config('appointments.morning_start_time'))
                ->whereTime('HeureRdv', '<', config('appointments.evening_start_time'));
            $pendingQuery
                ->whereTime('heure_souhaitee', '>=', config('appointments.morning_start_time'))
                ->whereTime('heure_souhaitee', '<', config('appointments.evening_start_time'));
        } else {
            $scheduledQuery->whereTime('HeureRdv', '>=', config('appointments.evening_start_time'));
            $pendingQuery->whereTime('heure_souhaitee', '>=', config('appointments.evening_start_time'));
        }

        return $scheduledQuery->count() + $pendingQuery->count();
    }

    private function slotMatchesPeriod(string $slot, string $period): bool
    {
        if ($period === 'morning') {
            return $slot >= config('appointments.morning_start_time')
                && $slot < config('appointments.evening_start_time');
        }

        return $slot >= config('appointments.evening_start_time');
    }

    private function telephoneRules(): array
    {
        return [
            'required',
            'string',
            'max:30',
            'regex:/^(?=(?:\D*\d){8,15}\D*$)[+0-9 .()\-]+$/',
        ];
    }

    private function telephoneValidationMessages(): array
    {
        return [
            'telephone.required' => 'يرجى إدخال رقم الهاتف.',
            'telephone.regex' => 'يرجى إدخال رقم هاتف صحيح يحتوي على 8 إلى 15 رقماً.',
        ];
    }

    private function normalizeTelephone(string $telephone): string
    {
        return preg_replace('/\D+/', '', $telephone);
    }

    private function findPatientByTelephone(int $cabinetId, string $telephone): ?Patient
    {
        $telephone = $this->normalizeTelephone($telephone);

        if ($telephone === '') {
            return null;
        }

        $normalizedTelephone1 = $this->normalizedPhoneSql('Telephone1');
        $normalizedTelephone2 = $this->normalizedPhoneSql('Telephone2');

        return Patient::where('fkidcabinet', $cabinetId)
            ->where(function ($query) use ($normalizedTelephone1, $normalizedTelephone2, $telephone) {
                $query->whereRaw("{$normalizedTelephone1} = ?", [$telephone])
                    ->orWhereRaw("{$normalizedTelephone2} = ?", [$telephone]);
            })
            ->first();
    }

    private function normalizedPhoneSql(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
    }

    private function patientDisplayName(Patient $patient): string
    {
        $name = trim(implode(' ', array_filter([
            trim((string) $patient->Prenom),
            trim((string) $patient->Nom),
        ])));

        if ($name === '') {
            $name = trim((string) $patient->NomContact);
        }

        return Str::limit($name !== '' ? $name : 'مريض مسجل', 120, '');
    }

    private function cabinetId(): int
    {
        return (int) config('appointments.public_cabinet_id');
    }

    private function generateReference(): string
    {
        do {
            $reference = 'TYB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (PublicAppointmentRequest::where('reference', $reference)->exists());

        return $reference;
    }
}
