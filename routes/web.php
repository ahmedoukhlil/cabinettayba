<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReglementFactureController;
use App\Http\Livewire\StatistiquesManager;
use App\Http\Controllers\CaisseController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentification
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/accueil', function () {
        return view('accueil');
    })->name('accueil');

    Route::get('/accueil-patient', function () {
        return view('accueil-patient-page');
    })->name('accueil.patient');

    // Routes pour les rendez-vous
    Route::middleware(['permission:rendez-vous.view'])->group(function () {
        Route::get('/rendez-vous', function () {
            return view('rendez-vous.index');
        })->name('rendez-vous');

        Route::get('/rendez-vous/print/{id}', function ($id) {
            $rendezVous = \App\Models\Rendezvou::with(['patient', 'medecin', 'cabinet'])->findOrFail($id);
            return view('rendez-vous.print', compact('rendezVous'));
        })->name('rendez-vous.print');
    });

    // Routes pour la création de rendez-vous (Secrétaire et Propriétaire)
    Route::middleware(['permission:rendez-vous.create'])->group(function () {
        Route::get('/rendez-vous/create', function () {
            return view('rendez-vous.create');
        })->name('rendez-vous.create');
    });

    // Routes pour les patients (Secrétaire et Propriétaire)
    Route::middleware(['permission:patient.view'])->group(function () {
        Route::get('/patients', function () {
            return view('patients.index');
        })->name('patients.index');
    });

    // Routes pour la création de patients (Secrétaire et Propriétaire)
    Route::middleware(['permission:patient.create'])->group(function () {
        Route::get('/patients/create', function () {
            return view('patients.create');
        })->name('patients.create');
    });

    // Routes pour les finances (Propriétaire uniquement)
    Route::middleware(['permission:finances.view'])->group(function () {
        Route::get('/finances/global', [FinanceController::class, 'index'])->name('finances.global');
        Route::get('/finances/depenses', [FinanceController::class, 'expenses'])->name('finances.expenses');
    });

    // Routes pour les opérations de caisse (Secrétaire et Propriétaire)
    Route::middleware(['permission:caisse-operations.view'])->group(function () {
        Route::get('/caisse-operations', function () {
            return view('caisse-operations');
        })->name('caisse-operations');
    });

    // Routes pour la gestion des utilisateurs (Propriétaire uniquement)
    Route::middleware(['permission:user.view'])->group(function () {
        Route::get('/users', function () {
            return view('users.index');
        })->name('users.index');
    });

    // Routes pour les consultations
    Route::middleware(['permission:patient.view'])->group(function () {
        Route::get('/consultations/search-patient', [ConsultationController::class, 'searchPatient'])->name('consultations.search-patient');
        Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
        Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
        Route::get('/consultations/{id}', [ConsultationController::class, 'show'])->name('consultations.show');
        Route::get('/consultations/{facture}/receipt', [ConsultationController::class, 'showReceipt'])->name('consultations.receipt');
        Route::get('/facture-patient/{facture}', [ConsultationController::class, 'showFacturePatient'])->name('consultations.facture-patient');
    });

    Route::get('/reglement-facture', function () {
        return view('reglement-facture');
    })->name('reglement-facture');

    Route::get('/reglement-facture/recu/{operation}', [ReglementFactureController::class, 'showReceipt'])->name('reglement-facture.receipt');

    Route::get('/historique-paiement/print/{patient}', [PaiementController::class, 'printHistorique'])->name('paiement.print-historique');

    Route::get('/statistiques', StatistiquesManager::class)->name('statistiques');

    Route::get('/caisse/etat-journalier/{date?}', [CaisseController::class, 'printEtatCaisse'])->name('caisse.etat-journalier');
});