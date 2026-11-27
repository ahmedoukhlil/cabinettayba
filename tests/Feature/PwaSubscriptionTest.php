<?php

namespace Tests\Feature;

use App\Models\PublicAppointmentRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PwaSubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('public_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fkidcabinet');
            $table->string('reference', 32)->unique();
            $table->string('tracking_token_hash', 64);
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->char('target_key', 64)->unique();
            $table->char('endpoint_hash', 64);
            $table->text('endpoint');
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding', 20);
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedBigInteger('public_appointment_request_id')->nullable();
            $table->unsignedInteger('cabinet_id');
            $table->string('target_url', 2048);
            $table->timestamps();
        });
    }

    public function test_patient_can_subscribe_only_with_the_private_tracking_token(): void
    {
        $token = 'ValidTrackingToken123';
        $appointmentRequest = PublicAppointmentRequest::create([
            'fkidcabinet' => 1,
            'reference' => 'RDV-TEST-123',
            'tracking_token_hash' => hash('sha256', $token),
        ]);
        $payload = [
            'endpoint' => 'https://push.example.test/subscription/123',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ];

        $this->postJson(route('public.appointment.notifications.store', [
            $appointmentRequest->reference,
            'WrongTrackingToken',
        ]), $payload)->assertNotFound();

        $this->postJson(route('public.appointment.notifications.store', [
            $appointmentRequest->reference,
            $token,
        ]), $payload)->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'public_appointment_request_id' => $appointmentRequest->getKey(),
            'cabinet_id' => 1,
        ]);

        $secondToken = 'SecondValidTrackingToken';
        $secondRequest = PublicAppointmentRequest::create([
            'fkidcabinet' => 1,
            'reference' => 'RDV-TEST-456',
            'tracking_token_hash' => hash('sha256', $secondToken),
        ]);
        $this->postJson(route('public.appointment.notifications.store', [
            $secondRequest->reference,
            $secondToken,
        ]), $payload)->assertOk();

        $this->assertSame(2, DB::table('push_subscriptions')
            ->where('endpoint_hash', hash('sha256', $payload['endpoint']))
            ->count());
    }
}
