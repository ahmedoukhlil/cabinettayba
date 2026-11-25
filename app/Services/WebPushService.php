<?php

namespace App\Services;

use App\Models\PublicAppointmentRequest;
use App\Models\PushSubscription as PushSubscriptionModel;
use App\Models\TUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function __construct(private AppointmentQueueService $queue)
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

        $subscriptions = PushSubscriptionModel::whereIn('user_id', $userIds)->get();
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
        $subscriptions = PushSubscriptionModel::where(
            'public_appointment_request_id',
            $appointmentRequest->getKey()
        )->get();

        $this->send($subscriptions, [
            'title' => $confirmed ? 'تم تأكيد موعدك' : 'تحديث طلب الموعد',
            'body' => $confirmed
                ? sprintf(
                    'رقم موعدك %s، وأمامك %s في الطابور. اضغط للمتابعة.',
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
        $subscriptions = PushSubscriptionModel::where(
            'public_appointment_request_id',
            $appointmentRequest->getKey()
        )->get();

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
        $subscriptions = PushSubscriptionModel::where(
            'public_appointment_request_id',
            $appointmentRequest->getKey()
        )->get();

        $this->send($subscriptions, [
            'title' => 'تم إلغاء موعدك',
            'body' => 'تم إلغاء هذا الموعد. اضغط لإرسال طلب موعد جديد.',
            'tag' => 'patient-appointment-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);
    }

    public function notifyPatientRescheduled(PublicAppointmentRequest $appointmentRequest): void
    {
        $subscriptions = PushSubscriptionModel::where(
            'public_appointment_request_id',
            $appointmentRequest->getKey()
        )->get();

        $date = $appointmentRequest->date_souhaitee->format('d/m/Y');
        $time = substr($appointmentRequest->heure_souhaitee, 0, 5);

        $this->send($subscriptions, [
            'title' => 'تمت إعادة جدولة موعدك',
            'body' => "الموعد الجديد: {$date} - {$time}. اضغط لعرض التفاصيل.",
            'tag' => 'patient-appointment-' . $appointmentRequest->getKey(),
            'dir' => 'rtl',
        ]);
    }

    private function send(Collection $subscriptions, array $payload): void
    {
        $vapid = config('webpush.vapid');
        if (
            $subscriptions->isEmpty()
            || empty($vapid['public_key'])
            || empty($vapid['private_key'])
        ) {
            return;
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
                    PushSubscriptionModel::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
                } elseif (!$report->isSuccess()) {
                    Log::warning('Échec d’envoi Web Push', ['reason' => $report->getReason()]);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Notification Web Push indisponible', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
