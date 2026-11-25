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
            'minimumBookingDate' => now()->toDateString(),
            'maximumBookingDate' => now()->addDays(config('appointments.booking_horizon_days'))->toDateString(),
            'morningStartTime' => config('appointments.morning_start_time'),
            'eveningStartTime' => config('appointments.evening_start_time'),
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
            'periode' => ['nullable', Rule::in(['morning', 'evening'])],
        ]);

        $slots = $this->availableSlots($cabinetId, (int) $validated['medecin_id'], $validated['date']);
        if (!empty($validated['periode'])) {
            if ($this->periodBookingCount($cabinetId, $validated['date'], $validated['periode']) >= max(1, (int) config('appointments.period_limit', 20))) {
                $slots = [];
            } else {
                $slots = array_values(array_filter(
                    $slots,
                    fn (string $slot) => $this->slotMatchesPeriod($slot, $validated['periode'])
                ));
            }
        }

        return response()->json([
            'slots' => $slots,
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
            'date_souhaitee' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:' . now()->addDays(config('appointments.booking_horizon_days'))->toDateString(),
            ],
            'heure_souhaitee' => ['required', 'date_format:H:i'],
            'website' => ['nullable', 'max:0'],
        ], array_merge($this->telephoneValidationMessages(), [
            'nom.required' => 'يرجى إدخال الاسم الكامل.',
            'nom.min' => 'يجب أن يحتوي الاسم على حرفين على الأقل.',
            'medecin_id.required' => 'يرجى اختيار الطبيب.',
            'medecin_id.exists' => 'الطبيب المختار غير متاح.',
            'periode.required' => 'يرجى اختيار الفترة المناسبة.',
            'periode.in' => 'الفترة المختارة غير صالحة.',
            'date_souhaitee.required' => 'يرجى اختيار تاريخ الموعد.',
            'date_souhaitee.after_or_equal' => 'يجب اختيار تاريخ ابتداءً من اليوم.',
            'date_souhaitee.before_or_equal' => 'التاريخ المختار خارج فترة الحجز المتاحة.',
            'heure_souhaitee.required' => 'يرجى اختيار ساعة الموعد.',
            'heure_souhaitee.date_format' => 'ساعة الموعد غير صالحة.',
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

        $selectedTime = substr($validated['heure_souhaitee'], 0, 5);
        $availableSlots = $this->availableSlots(
            $cabinetId,
            (int) $validated['medecin_id'],
            $validated['date_souhaitee']
        );
        $periodIsFull = $this->periodBookingCount(
            $cabinetId,
            $validated['date_souhaitee'],
            $validated['periode']
        ) >= max(1, (int) config('appointments.period_limit', 20));

        if (
            $periodIsFull
            || !$this->slotMatchesPeriod($selectedTime, $validated['periode'])
            || !in_array($selectedTime, $availableSlots, true)
        ) {
            return back()->withErrors([
                'heure_souhaitee' => 'هذا الموعد لم يعد متاحاً. يرجى اختيار موعد آخر.',
            ])->withInput();
        }

        $token = Str::random(64);
        $periodLabel = $validated['periode'] === 'morning' ? 'الفترة الصباحية' : 'الفترة المسائية';
        $reference = null;
        $appointmentRequest = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $reference = $this->generateReference();
            $slotKey = hash('sha256', implode('|', [
                $cabinetId,
                $validated['medecin_id'],
                $validated['date_souhaitee'],
                $selectedTime,
            ]));

            try {
                $appointmentRequest = PublicAppointmentRequest::create([
                    'fkidcabinet' => $cabinetId,
                    'fkidmedecin' => $validated['medecin_id'],
                    'fkidacte' => null,
                    'nom' => $nom,
                    'prenom' => '',
                    'telephone' => $telephone,
                    'patient_id' => $patient ? $patient->ID : null,
                    'date_souhaitee' => $validated['date_souhaitee'],
                    'heure_souhaitee' => $selectedTime,
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
                'heure_souhaitee' => 'لم يعد هذا الموعد متاحاً. يرجى اختيار موعد آخر.',
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
