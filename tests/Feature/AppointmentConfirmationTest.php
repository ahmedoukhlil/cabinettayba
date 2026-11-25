<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PublicAppointmentRequest;
use App\Models\TUser;
use App\Services\AppointmentQueueService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentConfirmationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'webpush.vapid.public_key' => null,
            'webpush.vapid.private_key' => null,
        ]);
        DB::purge('sqlite');

        Schema::create('patients', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Nom')->nullable();
            $table->string('Prenom')->nullable();
            $table->string('Telephone1')->nullable();
            $table->string('Telephone2')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        Schema::create('public_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fkidcabinet');
            $table->unsignedInteger('fkidmedecin');
            $table->unsignedInteger('fkidacte')->nullable();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('telephone');
            $table->date('date_souhaitee');
            $table->time('heure_souhaitee');
            $table->string('status');
            $table->string('reference');
            $table->string('tracking_token_hash');
            $table->string('slot_key')->nullable();
            $table->unsignedInteger('confirmed_by')->nullable();
            $table->unsignedInteger('rejected_by')->nullable();
            $table->unsignedInteger('patient_id')->nullable();
            $table->unsignedInteger('appointment_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
        Schema::create('rendezvous', function (Blueprint $table) {
            $table->increments('IDRdv');
            $table->text('ActePrevu')->nullable();
            $table->dateTime('DtAjRdv')->nullable();
            $table->dateTime('dtPrevuRDV')->nullable();
            $table->string('user')->nullable();
            $table->dateTime('HeureRdv')->nullable();
            $table->unsignedInteger('fkidPatient')->nullable();
            $table->string('rdvConfirmer')->nullable();
            $table->unsignedInteger('fkidMedecin')->nullable();
            $table->unsignedInteger('OrdreRDV')->nullable();
            $table->dateTime('HeureConfRDV')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('public_appointment_request_id')->nullable();
        });
    }

    public function test_confirming_a_public_request_creates_a_confirmed_appointment(): void
    {
        $user = new TUser();
        $user->forceFill(['Iduser' => 7, 'IdClasseUser' => 3, 'fkidcabinet' => 1]);
        $user->exists = true;
        $this->actingAs($user);

        $patient = Patient::create([
            'Nom' => 'Test',
            'Prenom' => 'Patient',
            'Telephone1' => '22334455',
            'fkidcabinet' => 1,
        ]);
        $request = PublicAppointmentRequest::create([
            'fkidcabinet' => 1,
            'fkidmedecin' => 5,
            'nom' => 'Patient Test',
            'prenom' => '',
            'telephone' => '22334455',
            'date_souhaitee' => now()->addDay()->toDateString(),
            'heure_souhaitee' => '09:00:00',
            'status' => 'pending',
            'reference' => 'TYB-CONFIRM-1',
            'tracking_token_hash' => hash('sha256', 'token'),
            'patient_id' => $patient->ID,
        ]);

        $this->post(route('appointment-requests.confirm', $request))->assertRedirect();

        $this->assertDatabaseHas('rendezvous', [
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => 5,
            'fkidcabinet' => 1,
            'rdvConfirmer' => 'confirmé',
        ]);
        $this->assertDatabaseHas('public_appointment_requests', [
            'id' => $request->getKey(),
            'status' => 'confirmed',
            'confirmed_by' => 7,
        ]);
        $this->assertNotNull($request->fresh()->appointment_id);
    }

    public function test_pending_request_can_be_rescheduled_before_confirmation(): void
    {
        $user = new TUser();
        $user->forceFill(['Iduser' => 7, 'IdClasseUser' => 3, 'fkidcabinet' => 1]);
        $user->exists = true;
        $this->actingAs($user);

        $appointmentRequest = PublicAppointmentRequest::create([
            'fkidcabinet' => 1,
            'fkidmedecin' => 5,
            'nom' => 'Patient Test',
            'prenom' => '',
            'telephone' => '22334455',
            'date_souhaitee' => now()->addDay()->toDateString(),
            'heure_souhaitee' => '09:00:00',
            'status' => 'pending',
            'reference' => 'TYB-RESCHEDULE-1',
            'tracking_token_hash' => hash('sha256', 'token'),
        ]);

        $newDate = now()->next('Monday')->addWeek()->toDateString();

        $this->post(route('appointment-requests.reschedule', $appointmentRequest), [
            'date_souhaitee' => $newDate,
            'heure_souhaitee' => '10:30',
        ])->assertRedirect(route('appointment-requests.show', $appointmentRequest));

        $this->assertDatabaseHas('public_appointment_requests', [
            'id' => $appointmentRequest->getKey(),
            'heure_souhaitee' => '10:30:00',
            'status' => 'pending',
        ]);
        $rescheduledRequest = $appointmentRequest->fresh();
        $this->assertSame($newDate, $rescheduledRequest->date_souhaitee->toDateString());
        $this->assertNotNull($rescheduledRequest->slot_key);
    }

    public function test_confirmed_appointment_can_be_rescheduled_in_request_and_agenda(): void
    {
        $user = $this->staffUser();
        $this->actingAs($user);
        $patient = $this->patient();
        $appointmentRequest = $this->pendingRequest($patient, 'TYB-CONFIRMED-MOVE');

        $this->post(route('appointment-requests.confirm', $appointmentRequest))->assertRedirect();
        $appointmentRequest->refresh();
        $newDate = now()->next('Monday')->addWeek()->toDateString();

        $this->post(route('appointment-requests.reschedule', $appointmentRequest), [
            'date_souhaitee' => $newDate,
            'heure_souhaitee' => '11:30',
        ])->assertRedirect(route('appointment-requests.show', $appointmentRequest));

        $appointmentRequest->refresh();
        $rendezVous = DB::table('rendezvous')->where('IDRdv', $appointmentRequest->appointment_id)->first();
        $this->assertSame('confirmed', $appointmentRequest->status);
        $this->assertSame($newDate, $appointmentRequest->date_souhaitee->toDateString());
        $this->assertSame('11:30:00', $appointmentRequest->heure_souhaitee);
        $this->assertStringStartsWith($newDate . ' 11:30:00', $rendezVous->HeureRdv);
    }

    public function test_confirmed_appointment_can_be_cancelled_and_frees_its_slot(): void
    {
        $user = $this->staffUser();
        $this->actingAs($user);
        $patient = $this->patient();
        $appointmentRequest = $this->pendingRequest($patient, 'TYB-CONFIRMED-CANCEL');

        $this->post(route('appointment-requests.confirm', $appointmentRequest))->assertRedirect();
        $appointmentRequest->refresh();

        $this->post(route('appointment-requests.cancel', $appointmentRequest))->assertRedirect();

        $this->assertDatabaseHas('public_appointment_requests', [
            'id' => $appointmentRequest->getKey(),
            'status' => 'cancelled',
            'slot_key' => null,
        ]);
        $this->assertDatabaseHas('rendezvous', [
            'IDRdv' => $appointmentRequest->appointment_id,
            'rdvConfirmer' => 'annulé',
        ]);
    }

    public function test_staff_can_advance_the_real_queue_without_changing_desktop_status(): void
    {
        $user = $this->staffUser();
        $this->actingAs($user);
        $first = $this->pendingRequest($this->patient(), 'TYB-QUEUE-FIRST', '09:00:00');
        $second = $this->pendingRequest($this->patient(), 'TYB-QUEUE-SECOND', '09:30:00');
        $this->post(route('appointment-requests.confirm', $first))->assertRedirect();
        $this->post(route('appointment-requests.confirm', $second))->assertRedirect();
        $first->refresh();
        $second->refresh();

        $queue = app(AppointmentQueueService::class);
        $this->assertSame(1, $queue->state($second->load('appointment'))['people_ahead']);

        foreach (['arrived', 'in_consultation', 'completed'] as $status) {
            $this->post(route('appointment-requests.queue-status', $first), [
                'queue_status' => $status,
            ])->assertRedirect();
            $first->refresh();
            $this->assertSame($status, $first->status);
        }

        $second->unsetRelation('appointment');
        $this->assertSame(0, $queue->state($second->load('appointment'))['people_ahead']);
        $this->assertDatabaseHas('rendezvous', [
            'IDRdv' => $first->appointment_id,
            'rdvConfirmer' => 'confirmé',
        ]);
    }

    private function staffUser(): TUser
    {
        $user = new TUser();
        $user->forceFill(['Iduser' => 7, 'IdClasseUser' => 3, 'fkidcabinet' => 1]);
        $user->exists = true;

        return $user;
    }

    private function patient(): Patient
    {
        return Patient::create([
            'Nom' => 'Test',
            'Prenom' => 'Patient',
            'Telephone1' => '22334455',
            'fkidcabinet' => 1,
        ]);
    }

    private function pendingRequest(
        Patient $patient,
        string $reference,
        string $time = '09:00:00'
    ): PublicAppointmentRequest
    {
        return PublicAppointmentRequest::create([
            'fkidcabinet' => 1,
            'fkidmedecin' => 5,
            'nom' => 'Patient Test',
            'prenom' => '',
            'telephone' => '22334455',
            'date_souhaitee' => now()->addDay()->toDateString(),
            'heure_souhaitee' => $time,
            'status' => 'pending',
            'reference' => $reference,
            'tracking_token_hash' => hash('sha256', 'token'),
            'slot_key' => hash('sha256', $reference),
            'patient_id' => $patient->ID,
        ]);
    }
}
