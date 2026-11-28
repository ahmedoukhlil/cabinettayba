<?php

namespace App\Http\Controllers;

use App\Models\PublicAppointmentRequest;
use App\Models\PushSubscription;
use App\Services\AppointmentQueueService;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(private AppointmentQueueService $queue)
    {
    }

    public function storeForUser(Request $request)
    {
        $user = $request->user();
        $this->storeSubscription($request, [
            'user_id' => $user->getAuthIdentifier(),
            'public_appointment_request_id' => null,
            'cabinet_id' => $user->fkidcabinet,
            'target_url' => route('appointment-requests.index', [], false),
        ]);

        return response()->json(['message' => 'Notifications activées.']);
    }

    public function destroyForUser(Request $request)
    {
        $validated = $request->validate(['endpoint' => ['required', 'url', 'max:2048']]);
        PushSubscription::where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('cabinet_id', $request->user()->fkidcabinet)
            ->delete();

        return response()->json(['message' => 'Notifications désactivées.']);
    }

    public function storeForAppointment(Request $request, string $reference, string $token)
    {
        $appointmentRequest = $this->appointmentRequest($reference, $token);
        if ($this->queue->state($appointmentRequest->loadMissing('appointment'))['expired']) {
            return response()->json(['message' => 'انتهت صلاحية رابط هذا الموعد.'], 410);
        }
        $this->storeSubscription($request, [
            'user_id' => null,
            'public_appointment_request_id' => $appointmentRequest->getKey(),
            'cabinet_id' => $appointmentRequest->fkidcabinet,
            'target_url' => route('public.appointment.show', [$reference, $token], false),
        ]);

        return response()->json(['message' => 'تم تفعيل إشعارات الموعد.']);
    }

    public function destroyForAppointment(Request $request, string $reference, string $token)
    {
        $appointmentRequest = $this->appointmentRequest($reference, $token);
        $validated = $request->validate(['endpoint' => ['required', 'url', 'max:2048']]);
        PushSubscription::where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->where('public_appointment_request_id', $appointmentRequest->getKey())
            ->where('cabinet_id', $appointmentRequest->fkidcabinet)
            ->delete();

        return response()->json(['message' => 'تم إيقاف إشعارات الموعد.']);
    }

    private function storeSubscription(Request $request, array $owner): PushSubscription
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:512'],
            'contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
            'replacesEndpoint' => ['nullable', 'url', 'max:2048'],
        ]);

        $endpointHash = hash('sha256', $validated['endpoint']);
        $ownerKey = $owner['user_id']
            ? 'user:' . $owner['user_id']
            : 'appointment:' . $owner['public_appointment_request_id'];

        $subscription = PushSubscription::updateOrCreate(
            ['target_key' => hash('sha256', $endpointHash . '|' . $ownerKey)],
            array_merge($owner, [
                'endpoint_hash' => $endpointHash,
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
            ])
        );

        if (!empty($validated['replacesEndpoint'])) {
            $replacedHash = hash('sha256', $validated['replacesEndpoint']);
            if (!hash_equals($endpointHash, $replacedHash)) {
                $staleQuery = PushSubscription::where('endpoint_hash', $replacedHash)
                    ->where('cabinet_id', $owner['cabinet_id']);

                if ($owner['user_id']) {
                    $staleQuery->where('user_id', $owner['user_id']);
                } else {
                    $staleQuery->where('public_appointment_request_id', $owner['public_appointment_request_id']);
                }

                $staleQuery->delete();
            }
        }

        return $subscription;
    }

    private function appointmentRequest(string $reference, string $token): PublicAppointmentRequest
    {
        $appointmentRequest = PublicAppointmentRequest::where('reference', $reference)->firstOrFail();
        abort_unless(
            hash_equals($appointmentRequest->tracking_token_hash, hash('sha256', $token)),
            404
        );

        return $appointmentRequest;
    }
}
