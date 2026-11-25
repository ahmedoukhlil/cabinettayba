<?php

namespace Tests\Feature;

use App\Http\Livewire\AssureurManager;
use App\Http\Livewire\PatientSearch;
use App\Http\Livewire\PatientManager;
use App\Http\Livewire\ReglementFacture;
use App\Http\Livewire\UserManager;
use App\Models\TUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class UpgradedUiBusinessLogicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('u', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');

        Schema::create('t_user', function (Blueprint $table) {
            $table->increments('Iduser');
            $table->string('login');
            $table->string('password');
            $table->string('NomComplet')->nullable();
            $table->string('fonction')->nullable();
            $table->unsignedInteger('IdClasseUser')->default(1);
            $table->unsignedInteger('fkidmedecin')->nullable();
            $table->unsignedInteger('fkidcabinet');
            $table->boolean('ismasquer')->default(false);
            $table->dateTime('DtCr')->nullable();
        });

        Schema::create('medecins', function (Blueprint $table) {
            $table->increments('idMedecin');
            $table->string('Nom')->nullable();
            $table->string('Contact')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });

        Schema::create('assureurs', function (Blueprint $table) {
            $table->increments('IDAssureur');
            $table->string('LibAssurance');
            $table->double('TauxdePEC')->default(0);
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Nom')->nullable();
            $table->string('Prenom')->nullable();
            $table->string('IdentifiantPatient')->nullable();
            $table->string('Telephone1')->nullable();
            $table->string('Telephone2')->nullable();
            $table->string('Adresse')->nullable();
            $table->string('Genre')->nullable();
            $table->date('DtNaissance')->nullable();
            $table->unsignedInteger('Assureur')->default(1);
            $table->string('IdentifiantAssurance')->nullable();
            $table->string('NNI')->nullable();
            $table->string('MatriculeFonct')->nullable();
            $table->string('NomContact')->nullable();
            $table->string('ClasserSous')->nullable();
            $table->boolean('choix')->nullable();
            $table->dateTime('Dtajout')->nullable();
            $table->unsignedInteger('fkidUser')->nullable();
            $table->unsignedInteger('fkidtypeTiers')->default(1);
            $table->unsignedInteger('DernDossierFermer')->default(0);
            $table->unsignedInteger('fkidcabinet');
        });

        Schema::create('ref_type_paiement', function (Blueprint $table) {
            $table->increments('idtypepaie');
            $table->string('LibPaie');
        });

        Schema::create('actes', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Acte')->nullable();
            $table->double('PrixRef')->default(0);
            $table->unsignedInteger('fkidTypeActe')->default(0);
            $table->unsignedInteger('nordre')->default(1);
            $table->string('user')->nullable();
            $table->unsignedInteger('fkidassureur')->default(1);
            $table->string('ActeArab')->nullable();
            $table->unsignedInteger('Masquer')->default(0);
        });

        Schema::create('facture', function (Blueprint $table) {
            $table->increments('Idfacture');
            $table->string('Nfacture')->nullable();
            $table->unsignedInteger('anneeFacture')->nullable();
            $table->unsignedInteger('nordre')->nullable();
            $table->dateTime('DtFacture')->nullable();
            $table->unsignedInteger('IDPatient');
            $table->unsignedInteger('ISTP')->default(0);
            $table->double('TXPEC')->default(0);
            $table->double('TotFacture')->default(0);
            $table->double('TotalPEC')->default(0);
            $table->double('TotalfactPatient')->default(0);
            $table->double('TotReglPatient')->default(0);
            $table->double('ReglementPEC')->default(0);
            $table->unsignedInteger('FkidMedecinInitiateur');
            $table->double('PartLaboratoire')->default(0);
            $table->double('MontantAffectation')->default(0);
            $table->unsignedInteger('fkidCabinet');
            $table->string('user')->nullable();
        });

        Schema::create('detailfacturepatient', function (Blueprint $table) {
            $table->increments('idDetfacture');
            $table->unsignedInteger('fkidfacture');
            $table->dateTime('DtAjout')->nullable();
            $table->string('Actes')->nullable();
            $table->double('PrixRef')->nullable();
            $table->double('PrixFacture')->nullable();
            $table->double('Quantite')->nullable();
            $table->unsignedInteger('fkidMedecin');
            $table->string('Dents')->nullable();
            $table->unsignedInteger('fkidacte')->nullable();
            $table->unsignedInteger('fkidcabinet');
        });
    }

    public function test_insurance_percentage_is_edited_as_percent_and_stored_as_ratio(): void
    {
        $id = DB::table('assureurs')->insertGetId([
            'LibAssurance' => 'Assurance test',
            'TauxdePEC' => 0.8,
        ]);

        Livewire::test(AssureurManager::class)
            ->call('openModal', $id)
            ->assertSet('tauxdePEC', 80.0)
            ->set('tauxdePEC', 75)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEqualsWithDelta(0.75, (float) DB::table('assureurs')->where('IDAssureur', $id)->value('TauxdePEC'), 0.0001);
    }

    public function test_doctor_user_must_be_linked_to_a_doctor_from_the_same_cabinet(): void
    {
        $owner = $this->createOwner();
        $localDoctor = DB::table('medecins')->insertGetId(['Nom' => 'Local', 'fkidcabinet' => 1]);
        $foreignDoctor = DB::table('medecins')->insertGetId(['Nom' => 'Étranger', 'fkidcabinet' => 2]);

        $component = Livewire::actingAs($owner)
            ->test(UserManager::class)
            ->set('login', 'nouveau-medecin')
            ->set('password', 'clair123')
            ->set('nomComplet', 'Médecin test')
            ->set('role', 2)
            ->set('medecinId', $foreignDoctor)
            ->call('save')
            ->assertHasErrors(['medecinId']);

        $component
            ->set('medecinId', $localDoctor)
            ->call('save')
            ->assertHasNoErrors();

        $created = DB::table('t_user')->where('login', 'nouveau-medecin')->first();
        $this->assertSame($localDoctor, (int) $created->fkidmedecin);
        $this->assertSame('clair123', $created->password);
    }

    public function test_invoice_totals_include_the_act_quantity(): void
    {
        $owner = $this->createOwner();
        $doctor = DB::table('medecins')->insertGetId(['Nom' => 'Dr Facture', 'fkidcabinet' => 1]);
        $actId = DB::table('actes')->insertGetId([
            'Acte' => 'Soin test',
            'PrixRef' => 100,
            'fkidassureur' => 1,
        ]);
        $invoiceId = DB::table('facture')->insertGetId([
            'Nfacture' => '1-2026',
            'IDPatient' => 10,
            'ISTP' => 1,
            'TXPEC' => 0.8,
            'FkidMedecinInitiateur' => $doctor,
            'fkidCabinet' => 1,
        ]);

        Livewire::actingAs($owner)
            ->test(ReglementFacture::class, ['selectedPatient' => ['ID' => 10]])
            ->set('factureIdForActe', $invoiceId)
            ->set('selectedActeId', $actId)
            ->set('prixReference', 100)
            ->set('prixFacture', 100)
            ->set('quantite', 3)
            ->set('seance', 'Dent 11')
            ->call('saveActeToFacture')
            ->assertHasNoErrors();

        $invoice = DB::table('facture')->where('Idfacture', $invoiceId)->first();
        $this->assertEqualsWithDelta(300, (float) $invoice->TotFacture, 0.001);
        $this->assertEqualsWithDelta(240, (float) $invoice->TotalPEC, 0.001);
        $this->assertEqualsWithDelta(60, (float) $invoice->TotalfactPatient, 0.001);
        $this->assertDatabaseHas('detailfacturepatient', [
            'fkidfacture' => $invoiceId,
            'PrixFacture' => 100,
            'Quantite' => 3,
        ]);
    }

    public function test_patient_search_preserves_the_real_insurance_identifier(): void
    {
        $owner = $this->createOwner();
        $assureurId = DB::table('assureurs')->insertGetId([
            'LibAssurance' => 'Assurance sélection',
            'TauxdePEC' => 0.8,
        ]);
        $patientId = DB::table('patients')->insertGetId([
            'Nom' => 'Assuré',
            'Prenom' => 'Patient',
            'Telephone1' => '22123456',
            'Assureur' => $assureurId,
            'IdentifiantAssurance' => 'PEC-2026-01',
            'fkidcabinet' => 1,
        ]);

        Livewire::actingAs($owner)
            ->test(PatientSearch::class)
            ->call('selectPatient', $patientId)
            ->assertSet('selectedPatient.IdentifiantAssurance', 'PEC-2026-01')
            ->assertSet('selectedPatient.Assureur', $assureurId);
    }

    public function test_phone_search_starts_automatically_after_three_digits(): void
    {
        $owner = $this->createOwner();
        DB::table('patients')->insert([
            'Nom' => 'Recherche',
            'Prenom' => 'Automatique',
            'Telephone1' => '22324567',
            'Assureur' => 1,
            'fkidcabinet' => 1,
        ]);

        Livewire::actingAs($owner)
            ->test(PatientSearch::class)
            ->set('search', '223')
            ->assertCount('patients', 1)
            ->assertSet('patients.0.NomPatient', 'Recherche');
    }

    public function test_new_patient_can_be_saved_with_blank_optional_fields(): void
    {
        $owner = $this->createOwner();

        Livewire::actingAs($owner)
            ->test(PatientManager::class)
            ->call('openModal')
            ->set('nom', 'Patient Minimal')
            ->set('telephone1', '22334455')
            ->set('prenom', '')
            ->set('dateNaissance', '')
            ->set('genre', '')
            ->set('nni', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $patient = DB::table('patients')->where('Telephone1', '22334455')->first();
        $this->assertNotNull($patient);
        $this->assertNull($patient->DtNaissance);
        $this->assertNull($patient->NNI);
        $this->assertSame(1, (int) $patient->Assureur);
    }

    private function createOwner(): TUser
    {
        return TUser::create([
            'login' => 'owner-' . uniqid(),
            'password' => 'clair',
            'NomComplet' => 'Propriétaire',
            'IdClasseUser' => 3,
            'fkidcabinet' => 1,
            'ismasquer' => false,
        ]);
    }
}
