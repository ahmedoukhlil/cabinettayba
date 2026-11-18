<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PublicAppointmentRequest;
use App\Models\Rendezvou;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        if (!in_array($status, ['pending', 'confirmed', 'rejected'], true)) {
            $status = 'pending';
        }

        $requests = PublicAppointmentRequest::with(['medecin', 'acte'])
            ->where('fkidcabinet', Auth::user()->fkidcabinet)
            ->where('status', $status)
            ->orderBy('date_souhaitee')
            ->orderBy('heure_souhaitee')
            ->paginate(20)
            ->withQueryString();

        return view('appointments.requests.index', compact('requests', 'status'));
    }

    public function confirm(PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);

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

                $patient = Patient::where('fkidcabinet', $request->fkidcabinet)
                    ->where(function ($query) use ($request) {
                        $query->where('Telephone1', $request->telephone)
                            ->orWhere('Telephone2', $request->telephone);
                    })
                    ->first();

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

        return back()->with('message', 'La demande a été confirmée et ajoutée au planning.');
    }

    public function reject(PublicAppointmentRequest $appointmentRequest)
    {
        $this->ensureSameCabinet($appointmentRequest);

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

        return back()->with('message', 'La demande a été refusée et le créneau a été libéré.');
    }

    private function ensureSameCabinet(PublicAppointmentRequest $appointmentRequest): void
    {
        abort_unless(
            (int) $appointmentRequest->fkidcabinet === (int) Auth::user()->fkidcabinet,
            404
        );
    }
}
