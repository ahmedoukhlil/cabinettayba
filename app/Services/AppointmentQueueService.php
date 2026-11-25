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

        if (in_array($request->status, ['cancelled', 'completed', 'no_show'], true)) {
            return $this->emptyState(
                $request->status === 'cancelled',
                $request->status
            );
        }

        if (!in_array($request->status, ['confirmed', 'arrived', 'in_consultation'], true) || !$appointment) {
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
            'closed_status' => $cancelled ? 'cancelled' : null,
            'appointment_number' => (int) ($appointment->OrdreRDV ?: $appointment->IDRdv),
            'people_ahead' => $expired ? 0 : $this->peopleAhead($appointment, $appointmentAt),
            'appointment_at' => $appointmentAt,
            'expires_at' => $expiresAt,
        ];
    }

    private function peopleAhead(Rendezvou $appointment, Carbon $appointmentAt): int
    {
        $appointmentsAhead = Rendezvou::where('fkidcabinet', $appointment->fkidcabinet)
            ->where('fkidMedecin', $appointment->fkidMedecin)
            ->whereDate('dtPrevuRDV', $appointmentAt->toDateString())
            ->where('IDRdv', '!=', $appointment->IDRdv)
            ->where(function ($query) {
                $query->whereNull('rdvConfirmer')
                    ->orWhere('rdvConfirmer', '!=', 'annulé');
            });

        if ($appointment->OrdreRDV) {
            $appointmentsAhead->where('OrdreRDV', '<', $appointment->OrdreRDV);
        } else {
            $appointmentsAhead->where('HeureRdv', '<', $appointmentAt);
        }

        $appointmentsAhead = $appointmentsAhead->get(['IDRdv', 'HeureRdv']);
        $workflowStatuses = PublicAppointmentRequest::whereIn(
            'appointment_id',
            $appointmentsAhead->pluck('IDRdv')
        )->pluck('status', 'appointment_id');
        $activeStatuses = ['confirmed', 'arrived', 'in_consultation'];
        $terminalStatuses = ['completed', 'no_show', 'cancelled', 'rejected'];

        return $appointmentsAhead->filter(function ($item) use (
            $workflowStatuses,
            $activeStatuses,
            $terminalStatuses,
            $appointmentAt
        ) {
            $status = $workflowStatuses->get($item->IDRdv);
            if (in_array($status, $terminalStatuses, true)) {
                return false;
            }
            if (in_array($status, $activeStatuses, true)) {
                return true;
            }

            return !$appointmentAt->isToday() || Carbon::parse($item->HeureRdv)->greaterThanOrEqualTo(now());
        })->count();
    }

    private function emptyState(bool $cancelled = false, ?string $closedStatus = null): array
    {
        return [
            'confirmed' => false,
            'expired' => $closedStatus !== null,
            'cancelled' => $cancelled,
            'closed_status' => $closedStatus,
            'appointment_number' => null,
            'people_ahead' => null,
            'appointment_at' => null,
            'expires_at' => null,
        ];
    }
}
