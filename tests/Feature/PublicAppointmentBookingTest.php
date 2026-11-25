<?php

namespace Tests\Feature;

use App\Models\PublicAppointmentRequest;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicAppointmentBookingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-25 07:00:00');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'appointments.public_cabinet_id' => 1,
            'appointments.booking_horizon_days' => 30,
            'appointments.opening_time' => '08:00',
            'appointments.closing_time' => '18:00',
            'appointments.morning_start_time' => '09:00',
            'appointments.evening_start_time' => '17:00',
            'appointments.slot_duration_minutes' => 30,
            'appointments.period_limit' => 20,
            'webpush.vapid.public_key' => null,
            'webpush.vapid.private_key' => null,
        ]);
        DB::purge('sqlite');

        Schema::create('medecins', function (Blueprint $table) {
            $table->increments('idMedecin');
            $table->string('Nom')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        Schema::create('patients', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Nom')->nullable();
            $table->string('Prenom')->nullable();
            $table->string('NomContact')->nullable();
            $table->string('Telephone1')->nullable();
            $table->string('Telephone2')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        Schema::create('rendezvous', function (Blueprint $table) {
            $table->increments('IDRdv');
            $table->dateTime('dtPrevuRDV')->nullable();
            $table->dateTime('HeureRdv')->nullable();
            $table->string('rdvConfirmer')->nullable();
            $table->unsignedInteger('fkidMedecin')->nullable();
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
            $table->unsignedInteger('patient_id')->nullable();
            $table->date('date_souhaitee');
            $table->time('heure_souhaitee');
            $table->text('message')->nullable();
            $table->string('status');
            $table->string('reference')->unique();
            $table->string('tracking_token_hash', 64);
            $table->string('slot_key', 64)->nullable()->unique();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->unsignedInteger('appointment_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
        Schema::create('t_user', function (Blueprint $table) {
            $table->increments('Iduser');
            $table->unsignedInteger('fkidcabinet');
            $table->unsignedInteger('fkidmedecin')->nullable();
            $table->unsignedInteger('IdClasseUser');
            $table->unsignedInteger('ismasquer')->default(0);
        });
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        DB::table('medecins')->insert([
            'idMedecin' => 5,
            'Nom' => 'Dr Test',
            'fkidcabinet' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_availability_can_be_filtered_by_period(): void
    {
        $response = $this->getJson(route('public.appointment.availability', [
            'medecin_id' => 5,
            'date' => '2026-11-26',
            'periode' => 'morning',
        ]));

        $response->assertOk();
        $slots = $response->json('slots');
        $this->assertContains('09:00', $slots);
        $this->assertNotContains('17:00', $slots);
    }

    public function test_patient_books_the_explicit_selected_slot(): void
    {
        DB::table('patients')->insert([
            'ID' => 9,
            'Nom' => 'Test',
            'Prenom' => 'Patient',
            'Telephone1' => '22 33 44 55',
            'fkidcabinet' => 1,
        ]);

        $response = $this->post(route('public.appointment.store'), [
            'telephone' => '22 33 44 55',
            'medecin_id' => 5,
            'periode' => 'morning',
            'date_souhaitee' => '2026-11-26',
            'heure_souhaitee' => '09:30',
            'website' => '',
        ]);

        $response->assertRedirectContains('/reservation-suivi/');
        $appointmentRequest = PublicAppointmentRequest::firstOrFail();
        $this->assertSame(5, $appointmentRequest->fkidmedecin);
        $this->assertSame(9, $appointmentRequest->patient_id);
        $this->assertSame('Patient Test', $appointmentRequest->nom);
        $this->assertSame('22334455', $appointmentRequest->telephone);
        $this->assertSame('2026-11-26', $appointmentRequest->date_souhaitee->toDateString());
        $this->assertSame('09:30', substr($appointmentRequest->heure_souhaitee, 0, 5));
        $this->assertSame('pending', $appointmentRequest->status);
    }

    public function test_an_occupied_slot_is_rejected_on_submit(): void
    {
        DB::table('rendezvous')->insert([
            'dtPrevuRDV' => '2026-11-26 09:30:00',
            'HeureRdv' => '2026-11-26 09:30:00',
            'rdvConfirmer' => 'confirmé',
            'fkidMedecin' => 5,
            'fkidcabinet' => 1,
        ]);

        $response = $this->from(route('public.appointment.create'))->post(route('public.appointment.store'), [
            'nom' => 'Nouveau Patient',
            'telephone' => '22334455',
            'medecin_id' => 5,
            'periode' => 'morning',
            'date_souhaitee' => '2026-11-26',
            'heure_souhaitee' => '09:30',
            'website' => '',
        ]);

        $response->assertRedirect(route('public.appointment.create'));
        $response->assertSessionHasErrors('heure_souhaitee');
        $this->assertDatabaseCount('public_appointment_requests', 0);
    }
}
