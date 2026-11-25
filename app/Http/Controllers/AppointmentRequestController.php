<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PublicAppointmentRequest;
use App\Models\Rendezvou;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentRequestController extends Controller
{
    private WebPushService $webPush;

    public function __construct(WebPushService $webPush)
    {
        $this->webPush = $webPush;
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        if (!in_array($status, ['pending', 'confirmed', 'completed', 'no_show', 'rejected', 'cancelled'], true)) {
            $status = 'pending';
        }

        $requestsQuery = PublicAppointmentRequest::with(['medecin', 'acte'])
            ->where('fkidcabinet', Auth::user()->fkidcabinet);

        if ($status === 'confirmed') {
            $requestsQuery->whereIn('status', ['confirmed', 'arrived', 'in_consultation']);
        } else {
            $requestsQuery->where('status', $status);
        }

        if ((int) Auth::user()->IdClasseUser === 2) {
            $requestsQuery->where('fkidmedecin', Auth::user()->fkidmedecin);
        }

        $requests = $requestsQuery
            ->orderBy('date_souhaitee')
            ->orderBy('heure_souhaitee')
            ->paginate(20)
            ->withQueryString();

        $view = $request->boolean('embedded')
            ? 'appointments.requests.embedded'
            : 'appointments.requests.index';

        return view($view, compact('requests', 'status'));
    }

    public function show(PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);
        abort_unless($this->canView($appointmentRequest), 403);
        $appointmentRequest->load(['medecin', 'acte']);
        $canManage = $this->canManage($appointmentRequest);

        return view('appointments.requests.show', compact('appointmentRequest', 'canManage'));
    }

    public function confirm(PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);
        abort_unless($this->canManage($appointmentRequest), 403);

        try {
            DB::transaction(function () use ($appointmentRequest) {
                $request = PublicAppointmentRequest::whereKey($appointmentRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($request->status !== 'pending') {
                    throw new \RuntimeException('Cette demande a déjà été traitée.');
                }

                $occupied = Rendezvou::where('fkidcabinet', $request->fkidcabinet)
                    ->where('fkidMedecin', $request->fkidmedecin)
                    ->whereDate('dtPrevuRDV', $request->date_souhaitee)
                    ->whereTime('HeureRdv', $request->heure_souhaitee)
                    ->where(function ($query) {
                        $query->whereNull('rdvConfirmer')
                            ->orWhere('rdvConfirmer', '!=', 'annulé');
                    })
                    ->exists();

                if ($occupied) {
                    throw new \RuntimeException('Le créneau est déjà occupé dans le planning.');
                }

                $patient = null;

                if ($request->patient_id) {
                    $patient = Patient::where('fkidcabinet', $request->fkidcabinet)
                        ->whereKey($request->patient_id)
                        ->first();
                }

                if (!$patient) {
                    $patient = Patient::where('fkidcabinet', $request->fkidcabinet)
                        ->where(function ($query) use ($request) {
                            $query->where('Telephone1', $request->telephone)
                                ->orWhere('Telephone2', $request->telephone);
                        })
                        ->first();
                }

                if (!$patient) {
                    $nextPatientNumber = (Patient::where('fkidcabinet', $request->fkidcabinet)
                        ->max('IdentifiantPatient') ?? 0) + 1;

                    $patient = Patient::create([
                        'Nom' => $request->nom,
                        'Prenom' => $request->prenom,
                        'NomContact' => trim($request->prenom . ' ' . $request->nom),
                        'Telephone1' => $request->telephone,
                        'Assureur' => 1,
                        'choix' => false,
                        'IdentifiantPatient' => $nextPatientNumber,
                        'Dtajout' => now(),
                        'fkidUser' => Auth::id(),
                        'fkidtypeTiers' => 1,
                        'fkidcabinet' => $request->fkidcabinet,
                    ]);
                }

                $dateTime = Carbon::parse(
                    $request->date_souhaitee->format('Y-m-d') . ' ' . $request->heure_souhaitee
                );

                $appointment = Rendezvou::create([
                    'fkidPatient' => $patient->ID,
                    'fkidMedecin' => $request->fkidmedecin,
                    'dtPrevuRDV' => $dateTime,
                    'HeureRdv' => $dateTime,
                    'ActePrevu' => optional($request->acte)->Acte ?? 'Consultation',
                    'rdvConfirmer' => 'confirmé',
                    'HeureConfRDV' => now(),
                    'DtAjRdv' => now(),
                    'user' => (string) Auth::id(),
                    'fkidcabinet' => $request->fkidcabinet,
                    'OrdreRDV' => Rendezvou::generateNextOrderNumber($dateTime, $request->fkidcabinet),
                ]);

                $request->update([
                    'status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'patient_id' => $patient->ID,
                    'appointment_id' => $appointment->IDRdv,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $appointmentRequest->refresh();
        $this->webPush->notifyPatientStatus($appointmentRequest);

        return back()->with('message', 'La demande a été confirmée et ajoutée au planning.');
    }

    public function reject(PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);
        abort_unless($this->canManage($appointmentRequest), 403);

        $updated = PublicAppointmentRequest::whereKey($appointmentRequest->getKey())
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'slot_key' => null,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $appointmentRequest->refresh();
        $this->webPush->notifyPatientStatus($appointmentRequest);

        return back()->with('message', 'La demande a été refusée et le créneau a été libéré.');
    }

    public function cancel(PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);
        abort_unless($this->canManage($appointmentRequest), 403);

        try {
            DB::transaction(function () use ($appointmentRequest) {
                $request = PublicAppointmentRequest::whereKey($appointmentRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($request->status, ['confirmed', 'arrived'], true)) {
                    throw new \RuntimeException('Ce rendez-vous ne peut plus être annulé à cette étape.');
                }

                $rendezVous = Rendezvou::where('fkidcabinet', $request->fkidcabinet)
                    ->whereKey($request->appointment_id)
                    ->lockForUpdate()
                    ->first();

                if (!$rendezVous) {
                    throw new \RuntimeException('Le rendez-vous lié est introuvable dans l’agenda.');
                }

                $rendezVous->update(['rdvConfirmer' => 'annulé']);
                $request->update([
                    'status' => 'cancelled',
                    'slot_key' => null,
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $appointmentRequest->refresh();
        $this->webPush->notifyPatientCancelled($appointmentRequest);

        return back()->with('message', 'Le rendez-vous a été annulé, le créneau libéré et le patient notifié.');
    }

    public function updateQueueStatus(Request $request, PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);
        abort_unless($this->canManage($appointmentRequest), 403);
        $validated = $request->validate([
            'queue_status' => ['required', 'in:arrived,in_consultation,completed,no_show'],
        ]);

        $transitions = [
            'confirmed' => ['arrived', 'in_consultation', 'no_show'],
            'arrived' => ['in_consultation', 'no_show'],
            'in_consultation' => ['completed'],
        ];

        try {
            DB::transaction(function () use ($appointmentRequest, $validated, $transitions) {
                $trackedRequest = PublicAppointmentRequest::whereKey($appointmentRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($validated['queue_status'], $transitions[$trackedRequest->status] ?? [], true)) {
                    throw new \RuntimeException('Cette progression n’est pas autorisée depuis l’état actuel.');
                }

                $rendezVousExists = Rendezvou::where('fkidcabinet', $trackedRequest->fkidcabinet)
                    ->whereKey($trackedRequest->appointment_id)
                    ->exists();
                if (!$rendezVousExists) {
                    throw new \RuntimeException('Le rendez-vous lié est introuvable dans l’agenda.');
                }

                $trackedRequest->update([
                    'status' => $validated['queue_status'],
                    'slot_key' => in_array($validated['queue_status'], ['completed', 'no_show'], true)
                        ? null
                        : $trackedRequest->slot_key,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $appointmentRequest->refresh();
        $this->webPush->notifyPatientWorkflowStatus($appointmentRequest);
        if (in_array($appointmentRequest->status, ['completed', 'no_show'], true)) {
            $this->webPush->notifyPatientsAfterQueueAdvanced($appointmentRequest);
        }

        $messages = [
            'arrived' => 'Le patient a été marqué comme arrivé.',
            'in_consultation' => 'Le patient a été appelé en consultation.',
            'completed' => 'Le rendez-vous a été terminé. La file va avancer.',
            'no_show' => 'Le patient a été marqué absent. La file va avancer.',
        ];

        return back()->with('message', $messages[$validated['queue_status']]);
    }

    public function reschedule(Request $request, PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);
        abort_unless($this->canManage($appointmentRequest), 403);

        $horizon = max(1, (int) config('appointments.booking_horizon_days'));
        $validated = $request->validate([
            'date_souhaitee' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:' . now()->addDays($horizon)->toDateString(),
            ],
            'heure_souhaitee' => ['required', 'date_format:H:i'],
        ]);

        $newDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date_souhaitee'] . ' ' . $validated['heure_souhaitee']
        );

        if ($newDateTime->isPast()) {
            return back()->withInput()->with('error', 'Le nouveau créneau doit être dans le futur.');
        }

        if ($newDateTime->isSunday()) {
            return back()->withInput()->with('error', 'Le cabinet est fermé le dimanche.');
        }

        $openingTime = config('appointments.opening_time');
        $closingTime = config('appointments.closing_time');
        $newTime = $newDateTime->format('H:i');
        if ($newTime < $openingTime || $newTime >= $closingTime) {
            return back()->withInput()->with(
                'error',
                "Choisissez une heure entre {$openingTime} et {$closingTime}."
            );
        }

        try {
            DB::transaction(function () use ($appointmentRequest, $newDateTime) {
                $appointment = PublicAppointmentRequest::whereKey($appointmentRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($appointment->status, ['pending', 'confirmed', 'arrived'], true)) {
                    throw new \RuntimeException('Cette demande ne peut plus être reprogrammée.');
                }

                $date = $newDateTime->toDateString();
                $time = $newDateTime->format('H:i:s');

                $occupiedInAgenda = Rendezvou::where('fkidcabinet', $appointment->fkidcabinet)
                    ->where('fkidMedecin', $appointment->fkidmedecin)
                    ->when($appointment->appointment_id, function ($query) use ($appointment) {
                        $query->where('IDRdv', '!=', $appointment->appointment_id);
                    })
                    ->whereDate('dtPrevuRDV', $date)
                    ->whereTime('HeureRdv', $time)
                    ->where(function ($query) {
                        $query->whereNull('rdvConfirmer')
                            ->orWhere('rdvConfirmer', '!=', 'annulé');
                    })
                    ->exists();

                $occupiedByRequest = PublicAppointmentRequest::where('fkidcabinet', $appointment->fkidcabinet)
                    ->where('fkidmedecin', $appointment->fkidmedecin)
                    ->where($appointment->getKeyName(), '!=', $appointment->getKey())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->whereDate('date_souhaitee', $date)
                    ->whereTime('heure_souhaitee', $time)
                    ->exists();

                if ($occupiedInAgenda || $occupiedByRequest) {
                    throw new \RuntimeException('Ce créneau est déjà occupé. Choisissez une autre heure.');
                }

                $appointment->update([
                    'date_souhaitee' => $date,
                    'heure_souhaitee' => $time,
                    'slot_key' => hash('sha256', implode('|', [
                        $appointment->fkidcabinet,
                        $appointment->fkidmedecin,
                        $date,
                        $newDateTime->format('H:i'),
                    ])),
                ]);

                if ($appointment->status === 'confirmed') {
                    $rendezVous = Rendezvou::where('fkidcabinet', $appointment->fkidcabinet)
                        ->whereKey($appointment->appointment_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$rendezVous) {
                        throw new \RuntimeException('Le rendez-vous lié est introuvable dans l’agenda.');
                    }

                    $rendezVous->update([
                        'dtPrevuRDV' => $newDateTime,
                        'HeureRdv' => $newDateTime,
                        'OrdreRDV' => Rendezvou::generateNextOrderNumber(
                            $newDateTime,
                            $appointment->fkidcabinet
                        ),
                    ]);
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $appointmentRequest->refresh();
        $this->webPush->notifyPatientRescheduled($appointmentRequest);

        return redirect()
            ->route('appointment-requests.show', $appointmentRequest)
            ->with('message', 'La demande a été reprogrammée et le patient a été notifié.');
    }

    private function ensureSameCabinet(PublicAppointmentRequest $appointmentRequest): void
    {
        abort_unless(
            (int) $appointmentRequest->fkidcabinet === (int) Auth::user()->fkidcabinet,
            404
        );
    }

    private function canManage(PublicAppointmentRequest $appointmentRequest): bool
    {
        $user = Auth::user();

        return in_array((int) $user->IdClasseUser, [1, 3], true)
            || ((int) $user->IdClasseUser === 2
                && (int) $user->fkidmedecin === (int) $appointmentRequest->fkidmedecin);
    }

    private function canView(PublicAppointmentRequest $appointmentRequest): bool
    {
        $user = Auth::user();

        return in_array((int) $user->IdClasseUser, [1, 3], true)
            || ((int) $user->IdClasseUser === 2
                && (int) $user->fkidmedecin === (int) $appointmentRequest->fkidmedecin);
    }
}
