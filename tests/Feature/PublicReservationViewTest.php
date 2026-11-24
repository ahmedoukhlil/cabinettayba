<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicReservationViewTest extends TestCase
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

        Schema::create('infocabinet', function (Blueprint $table) {
            $table->unsignedInteger('idEntete')->primary();
            $table->string('NomCabFr')->nullable();
            $table->string('NomCabAr')->nullable();
            $table->string('TelephonePublic')->nullable();
            $table->string('AdresseFr1')->nullable();
        });
        Schema::create('medecins', function (Blueprint $table) {
            $table->increments('idMedecin');
            $table->string('Nom')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
        DB::table('medecins')->insert([
            'Nom' => 'Dr Test',
            'fkidcabinet' => 1,
        ]);
    }

    public function test_public_reservation_page_renders_with_the_pwa_configuration(): void
    {
        $this->get(route('public.appointment.create'))
            ->assertOk()
            ->assertSee('window.PWA_CONFIG', false)
            ->assertSee('manifest-patient.webmanifest', false)
            ->assertSee('د. Dr Test', false)
            ->assertDontSee("idMedecin)&gt;", false);
    }
}
