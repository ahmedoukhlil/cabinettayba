<?php

namespace App\Console\Commands;

use App\Models\PublicAppointmentRequest;
use App\Models\PushSubscription;
use App\Services\AppointmentQueueService;
use App\Services\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyAppointmentQueue extends Command
{
    protected $signature = 'appointments:notify-queue';

    protected $description = 'Notify patients when their estimated appointment queue advances';

    public function handle(AppointmentQueueService $queue, WebPushService $webPush): int
    {
        PublicAppointmentRequest::with('appointment')
            ->whereIn('status', ['confirmed', 'arrived', 'in_consultation'])
            ->whereNotNull('appointment_id')
            ->whereHas('appointment', function ($query) {
                $query->whereDate('dtPrevuRDV', '>=', now()->toDateString());
            })
            ->chunkById(100, function ($requests) use ($queue, $webPush) {
                foreach ($requests as $appointmentRequest) {
                    $state = $queue->state($appointmentRequest);
                    $cacheKey = 'appointment-queue:' . $appointmentRequest->getKey();

                    if ($state['cancelled']) {
                        $webPush->notifyPatientCancelled($appointmentRequest);
                    }

                    if ($state['expired'] || $state['cancelled']) {
                        PushSubscription::where(
                            'public_appointment_request_id',
                            $appointmentRequest->getKey()
                        )->delete();
                        Cache::forget($cacheKey);
                        continue;
                    }

                    $peopleAhead = (int) $state['people_ahead'];
                    $previous = Cache::get($cacheKey);
                    if ($previous !== null && $peopleAhead < (int) $previous) {
                        $webPush->notifyPatientQueue($appointmentRequest, $peopleAhead);
                    }

                    Cache::put($cacheKey, $peopleAhead, $state['expires_at']);
                }
            });

        return self::SUCCESS;
    }
}
