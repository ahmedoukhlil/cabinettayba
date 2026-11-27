<?php

namespace Tests\Feature;

use App\Models\PublicAppointmentRequest;
use App\Models\Rendezvou;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicAppointmentTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-25 08:00:00');
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'appointments.tracking_link_expiry_minutes' => 180,
        ]);
        DB::purge('sqlite');

        Schema::create('infocabinet', function (Blueprint $table) {
            $table->unsignedInteger('idEntete')->primary();
            $table->string('NomCabFr')->nullable();
            $table->string('NomCabAr')->nullable();
        });
        Schema::create('medecins', function (Blueprint $table) {
            $table->increments('idMedecin');
            $table->string('Nom')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        Schema::create('rendezvous', function (Blueprint $table) {
            $table->increments('IDRdv');
            $table->dateTime('dtPrevuRDV')->nullable();
            $table->dateTime('HeureRdv')->nullable();
            $table->unsignedInteger('fkidMedecin')->nullable();
            $table->unsignedInteger('OrdreRDV')->nullable();
            $table->string('rdvConfirmer')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        Schema::create('public_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fkidcabinet');
            $table->unsignedInteger('fkidmedecin');
            $table->date('date_souhaitee');
            $table->time('heure_souhaitee');
            $table->string('status');
            $table->string('reference');
            $table->string('tracking_token_hash');
            $table->unsignedInteger('appointment_id')->nullable();
            $table->timestamps();
        });

        DB::table('infocabinet')->insert(['idEntete' => 1, 'NomCabAr' => 'عيادة طيبة']);
        DB::table('medecins')->insert(['idMedecin' => 5, 'Nom' => 'Test', 'fkidcabinet' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_confirmed_patient_sees_appointment_number_and_people_ahead(): void
    {
        $this->createAppointment(1, '09:30', 1);
        $target = $this->createAppointment(2, '10:00', 2);
        [$request, $token] = $this->createTrackedRequest($target);

        $this->get(route('public.appointment.show', [$request->reference, $token]))
            ->assertOk()
            ->assertSee('رقم موعدك')
            ->assertSee('id="appointment-number"', false)
            ->assertSee('id="people-ahead"', false)
            ->assertSee('>1</p>', false);

        $this->getJson(route('public.appointment.status', [$request->reference, $token]))
            ->assertOk()
            ->assertJson([
                'status' => 'confirmed',
                'expired' => false,
                'appointmentNumber' => 2,
                'peopleAhead' => 1,
            ]);
    }

    public function test_expired_tracking_link_hides_appointment_details_and_requests_a_new_booking(): void
    {
        Carbon::setTestNow('2026-11-25 14:00:00');
        $target = $this->createAppointment(1, '09:00', 1);
        [$request, $token] = $this->createTrackedRequest($target);

        $this->get(route('public.appointment.show', [$request->reference, $token]))
            ->assertOk()
            ->assertSee('انتهت صلاحية الرابط')
            ->assertSee('طلب موعد جديد')
            ->assertDontSee('رقم موعدك');
    }

    private function createAppointment(int $id, string $time, int $order): Rendezvou
    {
        return Rendezvou::create([
            'IDRdv' => $id,
            'dtPrevuRDV' => "2026-11-25 {$time}:00",
            'HeureRdv' => "2026-11-25 {$time}:00",
            'fkidMedecin' => 5,
            'OrdreRDV' => $order,
            'rdvConfirmer' => 'confirmé',
            'fkidcabinet' => 1,
        ]);
    }

    private function createTrackedRequest(Rendezvou $appointment): array
    {
        $token = 'ValidTrackingToken123';
        $request = PublicAppointmentRequest::create([
            'fkidcabinet' => 1,
            'fkidmedecin' => 5,
            'date_souhaitee' => '2026-11-25',
            'heure_souhaitee' => Carbon::parse($appointment->HeureRdv)->format('H:i:s'),
            'status' => 'confirmed',
            'reference' => 'RDV-QUEUE-' . $appointment->IDRdv,
            'tracking_token_hash' => hash('sha256', $token),
            'appointment_id' => $appointment->IDRdv,
        ]);

        return [$request, $token];
    }
}
