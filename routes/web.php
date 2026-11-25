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
use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\PublicAppointmentController;
use App\Http\Controllers\PushSubscriptionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Réservation publique (aucune authentification requise)
Route::get('/reservation', [PublicAppointmentController::class, 'create'])
    ->middleware('throttle:60,1')
    ->name('public.appointment.create');
Route::get('/reservation/creneaux', [PublicAppointmentController::class, 'availability'])
    ->middleware('throttle:60,1')
    ->name('public.appointment.availability');
Route::post('/reservation/patient', [PublicAppointmentController::class, 'lookupPatient'])
    ->middleware('throttle:10,1')
    ->name('public.appointment.patient.lookup');
Route::post('/reservation', [PublicAppointmentController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public.appointment.store');
Route::get('/reservation-suivi/{reference}/{token}', [PublicAppointmentController::class, 'show'])
    ->middleware('throttle:60,1')
    ->where(['reference' => '[A-Z0-9-]+', 'token' => '[A-Za-z0-9]+'])
    ->name('public.appointment.show');
Route::get('/reservation-suivi/{reference}/{token}/statut', [PublicAppointmentController::class, 'status'])
    ->middleware('throttle:120,1')
    ->where(['reference' => '[A-Z0-9-]+', 'token' => '[A-Za-z0-9]+'])
    ->name('public.appointment.status');
Route::post('/reservation-suivi/{reference}/{token}/notifications', [PushSubscriptionController::class, 'storeForAppointment'])
    ->middleware('throttle:10,1')
    ->where(['reference' => '[A-Z0-9-]+', 'token' => '[A-Za-z0-9]+'])
    ->name('public.appointment.notifications.store');
Route::delete('/reservation-suivi/{reference}/{token}/notifications', [PushSubscriptionController::class, 'destroyForAppointment'])
    ->middleware('throttle:10,1')
    ->where(['reference' => '[A-Z0-9-]+', 'token' => '[A-Za-z0-9]+'])
    ->name('public.appointment.notifications.destroy');

// Authentification
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {
    Route::post('/pwa/notifications', [PushSubscriptionController::class, 'storeForUser'])
        ->name('pwa.notifications.store');
    Route::delete('/pwa/notifications', [PushSubscriptionController::class, 'destroyForUser'])
        ->name('pwa.notifications.destroy');

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
            $rendezVous = \App\Models\Rendezvou::with(['patient', 'medecin', 'cabinet'])
                ->where('fkidcabinet', auth()->user()->fkidcabinet)
                ->findOrFail($id);
            return view('rendez-vous.print', compact('rendezVous'));
        })->name('rendez-vous.print');
    });

    // Routes pour la création de rendez-vous (Secrétaire et Propriétaire)
    Route::middleware(['permission:rendez-vous.create'])->group(function () {
        Route::get('/rendez-vous/create', function () {
            return view('rendez-vous.create');
        })->name('rendez-vous.create');

    });

    Route::middleware(['permission:rendez-vous.view'])->group(function () {
        Route::get('/demandes-rendez-vous', [AppointmentRequestController::class, 'index'])
            ->name('appointment-requests.index');
        Route::get('/demandes-rendez-vous/{appointmentRequest}', [AppointmentRequestController::class, 'show'])
            ->name('appointment-requests.show');
        Route::post('/demandes-rendez-vous/{appointmentRequest}/confirmer', [AppointmentRequestController::class, 'confirm'])
            ->name('appointment-requests.confirm');
        Route::post('/demandes-rendez-vous/{appointmentRequest}/refuser', [AppointmentRequestController::class, 'reject'])
            ->name('appointment-requests.reject');
        Route::post('/demandes-rendez-vous/{appointmentRequest}/annuler', [AppointmentRequestController::class, 'cancel'])
            ->name('appointment-requests.cancel');
        Route::post('/demandes-rendez-vous/{appointmentRequest}/file', [AppointmentRequestController::class, 'updateQueueStatus'])
            ->name('appointment-requests.queue-status');
        Route::post('/demandes-rendez-vous/{appointmentRequest}/reprogrammer', [AppointmentRequestController::class, 'reschedule'])
            ->name('appointment-requests.reschedule');
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
        Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
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
