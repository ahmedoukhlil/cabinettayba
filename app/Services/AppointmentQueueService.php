<?php

namespace App\Services;

use App\Models\PublicAppointmentRequest;
use App\Models\Rendezvou;
use Carbon\Carbon;

class AppointmentQueueService
{
    public function state(PublicAppointmentRequest $request): array
    {
        $appointment = $request->relationLoaded('appointment')
            ? $request->appointment
            : $request->appointment()->first();

        if ($request->status !== 'confirmed' || !$appointment) {
            return $this->emptyState();
        }

        $appointmentAt = Carbon::parse($appointment->HeureRdv ?: $appointment->dtPrevuRDV);
        $expiresAt = $appointmentAt->copy()->addMinutes(
            max(0, (int) config('appointments.tracking_link_expiry_minutes', 180))
        );
        $cancelled = mb_strtolower((string) $appointment->rdvConfirmer) === 'annulé';
        $expired = $cancelled || now()->greaterThanOrEqualTo($expiresAt);

        return [
            'confirmed' => true,
            'expired' => $expired,
            'cancelled' => $cancelled,
            'appointment_number' => (int) ($appointment->OrdreRDV ?: $appointment->IDRdv),
            'people_ahead' => $expired ? 0 : $this->peopleAhead($appointment, $appointmentAt),
            'appointment_at' => $appointmentAt,
            'expires_at' => $expiresAt,
        ];
    }

    private function peopleAhead(Rendezvou $appointment, Carbon $appointmentAt): int
    {
        $query = Rendezvou::where('fkidcabinet', $appointment->fkidcabinet)
            ->where('fkidMedecin', $appointment->fkidMedecin)
            ->whereDate('dtPrevuRDV', $appointmentAt->toDateString())
            ->where('IDRdv', '!=', $appointment->IDRdv)
            ->where(function ($query) {
                $query->whereNull('rdvConfirmer')
                    ->orWhere('rdvConfirmer', '!=', 'annulé');
            });

        if ($appointment->OrdreRDV) {
            $query->where('OrdreRDV', '<', $appointment->OrdreRDV);
        } else {
            $query->where('HeureRdv', '<', $appointmentAt);
        }

        if ($appointmentAt->isToday()) {
            $query->where('HeureRdv', '>=', now());
        }

        return $query->count();
    }

    private function emptyState(): array
    {
        return [
            'confirmed' => false,
            'expired' => false,
            'cancelled' => false,
            'appointment_number' => null,
            'people_ahead' => null,
            'appointment_at' => null,
            'expires_at' => null,
        ];
    }
}
