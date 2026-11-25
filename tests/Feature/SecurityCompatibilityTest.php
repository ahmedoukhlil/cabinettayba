<?php

namespace Tests\Feature;

use App\Http\Livewire\PatientSearch;
use App\Models\TUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');

        Schema::create('t_user', function (Blueprint $table) {
            $table->increments('Iduser');
            $table->string('login');
            $table->string('password');
            $table->unsignedInteger('IdClasseUser')->default(1);
            $table->unsignedInteger('fkidcabinet');
            $table->unsignedInteger('fkidmedecin')->nullable();
            $table->boolean('ismasquer')->default(false);
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Nom')->nullable();
            $table->string('Prenom')->nullable();
            $table->unsignedInteger('IdentifiantPatient')->nullable();
            $table->string('Telephone1')->nullable();
            $table->string('Telephone2')->nullable();
            $table->string('Adresse')->nullable();
            $table->string('Genre')->nullable();
            $table->date('DtNaissance')->nullable();
            $table->unsignedInteger('Assureur')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
    }

    public function test_user_password_remains_plaintext_for_desktop_compatibility(): void
    {
        $user = new TUser(['password' => 'desktop-compatible']);

        $this->assertSame('desktop-compatible', $user->getAttributes()['password']);
        $this->assertTrue($user->checkPassword('desktop-compatible'));
    }

    public function test_login_page_renders_with_vite_assets(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('/build/assets/app-', false);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['login' => 'unknown', 'password' => 'wrong'])
                ->assertRedirect();
        }

        $this->post('/login', ['login' => 'unknown', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_patient_search_cannot_select_a_patient_from_another_cabinet(): void
    {
        $user = TUser::create([
            'login' => 'cabinet-one',
            'password' => 'plain',
            'IdClasseUser' => 1,
            'fkidcabinet' => 1,
            'ismasquer' => false,
        ]);
        $foreignPatientId = DB::table('patients')->insertGetId([
            'Nom' => 'Patient étranger',
            'fkidcabinet' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(PatientSearch::class)
            ->call('selectPatient', $foreignPatientId)
            ->assertSet('selectedPatient', null);
    }
}
