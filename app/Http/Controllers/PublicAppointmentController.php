<?php

namespace App\Http\Controllers;

use App\Models\Acte;
use App\Models\Infocabinet;
use App\Models\Medecin;
use App\Models\PublicAppointmentRequest;
use App\Models\Rendezvou;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicAppointmentController extends Controller
{
    public function create()
    {
        $cabinetId = $this->cabinetId();

        return view('public.appointments.create', [
            'cabinet' => Infocabinet::find($cabinetId),
            'medecins' => Medecin::where('fkidcabinet', $cabinetId)
                ->orderBy('Nom')
                ->get(['idMedecin', 'Nom']),
            'actes' => Acte::where('Masquer', 0)
                ->whereNotNull('Acte')
                ->orderBy('nordre')
                ->get(['ID', 'Acte'])
                ->unique('Acte')
                ->values(),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays(config('appointments.booking_horizon_days'))->toDateString(),
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

    public function store(Request $request)
    {
        $cabinetId = $this->cabinetId();
        $validated = $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:120'],
            'prenom' => ['nullable', 'string', 'max:120'],
            'telephone' => [
                'required',
                'string',
                'max:30',
                'regex:/^(?=(?:\D*\d){8,15}\D*$)[+0-9 .()\-]+$/',
            ],
            'medecin_id' => [
                'required',
                'integer',
                Rule::exists('medecins', 'idMedecin')->where(fn ($query) => $query->where('fkidcabinet', $cabinetId)),
            ],
            'acte_id' => ['nullable', 'integer', Rule::exists('actes', 'ID')->where(fn ($query) => $query->where('Masquer', 0))],
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:' . now()->addDays(config('appointments.booking_horizon_days'))->toDateString(),
            ],
            'heure' => ['required', 'date_format:H:i'],
            'message' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ], [
            'consent.accepted' => 'Vous devez accepter le traitement des informations nécessaires à la réservation.',
            'telephone.regex' => 'Saisissez un numéro de téléphone valide contenant entre 8 et 15 chiffres.',
        ]);

        $medecinId = (int) $validated['medecin_id'];
        $availableSlots = $this->availableSlots($cabinetId, $medecinId, $validated['date']);
        if (!in_array($validated['heure'], $availableSlots, true)) {
            return back()->withErrors([
                'heure' => 'Ce créneau n’est plus disponible. Veuillez en choisir un autre.',
            ])->withInput();
        }

        $telephone = preg_replace('/[^0-9+]/', '', $validated['telephone']);
        $token = Str::random(64);
        $reference = $this->generateReference();
        $slotKey = hash('sha256', implode('|', [
            $cabinetId,
            $medecinId,
            $validated['date'],
            $validated['heure'],
        ]));

        try {
            PublicAppointmentRequest::create([
                'fkidcabinet' => $cabinetId,
                'fkidmedecin' => $medecinId,
                'fkidacte' => $validated['acte_id'] ?? null,
                'nom' => trim($validated['nom']),
                'prenom' => trim($validated['prenom'] ?? ''),
                'telephone' => $telephone,
                'date_souhaitee' => $validated['date'],
                'heure_souhaitee' => $validated['heure'],
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
                'reference' => $reference,
                'tracking_token_hash' => hash('sha256', $token),
                'slot_key' => $slotKey,
            ]);
        } catch (QueryException $exception) {
            return back()->withErrors([
                'heure' => 'Ce créneau vient d’être réservé. Veuillez en choisir un autre.',
            ])->withInput();
        }

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
