<?php

namespace App\Console\Commands;

use App\Models\PublicAppointmentRequest;
use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Services\AppointmentQueueService;
use App\Services\WebPushService;
use App\Tenancy\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyAppointmentQueue extends Command
{
    protected $signature = 'appointments:notify-queue';

    protected $description = 'Notify patients when their estimated appointment queue advances';

    public function handle(
        AppointmentQueueService $queue,
        WebPushService $webPush,
        TenantManager $tenants
    ): int
    {
        if (!config('tenancy.enabled')) {
            $this->notifyCurrentTenant($queue, $webPush);
            return self::SUCCESS;
        }

        $failed = false;
        foreach (Tenant::active()->cursor() as $tenant) {
            try {
                $tenants->activate($tenant);
                $this->notifyCurrentTenant($queue, $webPush);
            } catch (\Throwable $exception) {
                $failed = true;
                $this->error("{$tenant->slug}: {$exception->getMessage()}");
            } finally {
                $tenants->deactivate();
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function notifyCurrentTenant(AppointmentQueueService $queue, WebPushService $webPush): void
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

    }
}
