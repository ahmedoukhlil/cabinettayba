<?php

namespace App\Services;

use App\Models\PublicAppointmentRequest;
use App\Models\PushSubscription as PushSubscriptionModel;
use App\Models\TUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function __construct(
        private AppointmentQueueService $queue,
        private CabinetBranding $branding
    )
    {
    }

    public function notifyStaffForNewRequest(PublicAppointmentRequest $appointmentRequest): void
    {
        $userIds = TUser::where('fkidcabinet', $appointmentRequest->fkidcabinet)
            ->where('ismasquer', 0)
            ->where(function ($query) use ($appointmentRequest) {
                $query->whereIn('IdClasseUser', [1, 3])
                    ->orWhere(function ($doctorQuery) use ($appointmentRequest) {
                        $doctorQuery->where('IdClasseUser', 2)
                            ->where('fkidmedecin', $appointmentRequest->fkidmedecin);
                    });
            })
            ->pluck('Iduser');

        $subscriptions = PushSubscriptionModel::whereIn('user_id', $userIds)
            ->where('cabinet_id', $appointmentRequest->fkidcabinet)
            ->get();
        $pendingCount = PublicAppointmentRequest::where('fkidcabinet', $appointmentRequest->fkidcabinet)
            ->where('status', 'pending')
            ->count();
        $this->send($subscriptions, [
            'title' => 'Nouvelle demande de rendez-vous',
            'body' => 'Une nouvelle demande attend votre validation.',
            'url' => route('appointment-requests.show', $appointmentRequest, false),
            'tag' => 'appointment-request-' . $appointmentRequest->getKey(),
            'dir' => 'ltr',
            'badgeCount' => $pendingCount,
        ]);
    }

    public function notifyPatientStatus(PublicAppointmentRequest $appointmentRequest): void
    {
        $confirmed = $appointmentRequest->status === 'confirmed';
        $state = $this->queue->state($appointmentRequest->loadMissing('appointment'));
        $subscriptions = $this->patientSubscriptions($appointmentRequest);

        $this->send($subscriptions, [
            'title' => $confirmed ? 'تم تأكيد موعدك' : 'تحديث طلب الموعد',
            'body' => $confirmed
                ? sprintf(
                    'تم تأكيد موعدك في %s. رقم موعدك %s، وأمامك %s في الطابور.',
                    $this->branding->get()['name_ar'],
                    $state['appointment_number'] ?? '—',
                    $state['people_ahead'] ?? 0
                )
                : 'تعذر تأكيد الموعد المطلوب. اضغط لعرض التفاصيل.',
            'tag' => 'patient-appointment-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);
    }

    public function notifyPatientQueue(PublicAppointmentRequest $appointmentRequest, int $peopleAhead): void
    {
        $subscriptions = $this->patientSubscriptions($appointmentRequest);

        $this->send($subscriptions, [
            'title' => $peopleAhead === 0 ? 'حان دورك قريباً' : 'تحديث طابور الموعد',
            'body' => $peopleAhead === 0
                ? 'لم يعد أمامك أي مريض. يرجى الاستعداد للدخول.'
                : "بقي أمامك {$peopleAhead} في الطابور.",
            'tag' => 'patient-queue-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);
    }

    public function notifyPatientCancelled(PublicAppointmentRequest $appointmentRequest): void
    {
        $subscriptions = $this->patientSubscriptions($appointmentRequest);

        $this->send($subscriptions, [
            'title' => 'تم إلغاء موعدك',
            'body' => 'تم إلغاء هذا الموعد. اضغط لإرسال طلب موعد جديد.',
            'tag' => 'patient-appointment-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);
    }

    public function notifyPatientWorkflowStatus(PublicAppointmentRequest $appointmentRequest): void
    {
        $subscriptions = $this->patientSubscriptions($appointmentRequest);
        $messages = [
            'arrived' => ['تم تسجيل وصولك', 'أنت الآن ضمن طابور العيادة. تابع عدد المرضى أمامك.'],
            'in_consultation' => ['حان دورك', 'يرجى التوجه إلى غرفة الطبيب.'],
            'completed' => ['تم إنهاء الموعد', 'نتمنى لك الشفاء. يمكنك طلب موعد جديد عند الحاجة.'],
            'no_show' => ['تم تسجيل غيابك', 'يمكنك الضغط هنا لإرسال طلب موعد جديد.'],
        ];
        [$title, $body] = $messages[$appointmentRequest->status]
            ?? ['تحديث الموعد', 'تم تحديث حالة موعدك.'];

        $this->send($subscriptions, [
            'title' => $title,
            'body' => $body,
            'tag' => 'patient-workflow-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);

        if (in_array($appointmentRequest->status, ['completed', 'no_show'], true)) {
            PushSubscriptionModel::where(
                'public_appointment_request_id',
                $appointmentRequest->getKey()
            )->where('cabinet_id', $appointmentRequest->fkidcabinet)->delete();
        }
    }

    public function notifyPatientsAfterQueueAdvanced(PublicAppointmentRequest $completedRequest): void
    {
        PublicAppointmentRequest::with('appointment')
            ->where('fkidcabinet', $completedRequest->fkidcabinet)
            ->where('fkidmedecin', $completedRequest->fkidmedecin)
            ->whereDate('date_souhaitee', $completedRequest->date_souhaitee)
            ->whereIn('status', ['confirmed', 'arrived', 'in_consultation'])
            ->where($completedRequest->getKeyName(), '!=', $completedRequest->getKey())
            ->get()
            ->each(function (PublicAppointmentRequest $appointmentRequest) {
                $state = $this->queue->state($appointmentRequest);
                if ($state['people_ahead'] === null) {
                    return;
                }

                $this->notifyPatientQueue($appointmentRequest, (int) $state['people_ahead']);
                Cache::put(
                    'appointment-queue:' . $appointmentRequest->getKey(),
                    (int) $state['people_ahead'],
                    $state['expires_at']
                );
            });
    }

    public function notifyPatientRescheduled(PublicAppointmentRequest $appointmentRequest): void
    {
        $subscriptions = $this->patientSubscriptions($appointmentRequest);

        $date = $appointmentRequest->date_souhaitee->format('d/m/Y');
        $time = substr($appointmentRequest->heure_souhaitee, 0, 5);

        $this->send($subscriptions, [
            'title' => 'تمت إعادة جدولة موعدك',
            'body' => "الموعد الجديد: {$date} - {$time}. اضغط لعرض التفاصيل.",
            'tag' => 'patient-appointment-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);
    }

    public function sendDiagnostic(Collection $subscriptions, string $targetUrl = '/accueil-patient'): array
    {
        return $this->send($subscriptions, [
            'title' => 'Test des notifications',
            'body' => 'La configuration Web Push de ce cabinet fonctionne.',
            'url' => $targetUrl,
            'tag' => 'webpush-diagnostic-' . now()->timestamp,
            'dir' => 'ltr',
        ]);
    }

    private function patientSubscriptions(PublicAppointmentRequest $appointmentRequest): Collection
    {
        return PushSubscriptionModel::where(
            'public_appointment_request_id',
            $appointmentRequest->getKey()
        )->where('cabinet_id', $appointmentRequest->fkidcabinet)->get();
    }

    private function send(Collection $subscriptions, array $payload): array
    {
        $vapid = config('webpush.vapid');
        $result = [
            'queued' => $subscriptions->count(),
            'success' => 0,
            'expired' => 0,
            'failed' => 0,
            'skipped' => false,
            'reasons' => [],
        ];

        if (
            $subscriptions->isEmpty()
            || empty($vapid['public_key'])
            || empty($vapid['private_key'])
        ) {
            $result['skipped'] = true;
            return $result;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $vapid['subject'],
                    'publicKey' => $vapid['public_key'],
                    'privateKey' => $vapid['private_key'],
                ],
            ], [
                'TTL' => config('webpush.ttl', 86400),
                'urgency' => 'high',
            ], 5);

            foreach ($subscriptions as $storedSubscription) {
                $subscriptionPayload = $payload;
                $subscriptionPayload['url'] = $payload['url']
                    ?? ($storedSubscription->target_url ?: '/');

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $storedSubscription->endpoint,
                        'publicKey' => $storedSubscription->public_key,
                        'authToken' => $storedSubscription->auth_token,
                        'contentEncoding' => $storedSubscription->content_encoding,
                    ]),
                    json_encode($subscriptionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    $result['expired']++;
                    PushSubscriptionModel::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
                } elseif (!$report->isSuccess()) {
                    $result['failed']++;
                    $reason = (string) $report->getReason();
                    $result['reasons'][] = $reason;
                    Log::warning('Échec d’envoi Web Push', ['reason' => $reason]);
                } else {
                    $result['success']++;
                }
            }
        } catch (\Throwable $exception) {
            $result['failed'] = max(
                $result['failed'],
                $result['queued'] - $result['success'] - $result['expired']
            );
            Log::warning('Notification Web Push indisponible', [
                'message' => $exception->getMessage(),
            ]);
            $result['reasons'][] = $exception->getMessage();
        }

        return $result;
    }
}
