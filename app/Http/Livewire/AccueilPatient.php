<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Patient;
use App\Models\PublicAppointmentRequest;
use Illuminate\Support\Facades\Auth;

class AccueilPatient extends Component
{
    use WithPagination;

    // États des modales
    public $showCreateModal = false;
    public $showCreateRdvModal = false;
    public $showPatientListModal = false;
    public $showGestionPatientsModal = false;
    public $showActeModal = false;
    public $showAssureurModal = false;
    public $showCreatePatientModal = false;
    public $showPatientDetails = false;
    public $showPaymentHistory = false;
    public $showEditModal = false;
    public $showDeleteConfirmation = false;
    public $showCreateActeModal = false;
    public $showListeActesModal = false;
    public $showCaisseOperations = false;
    public $showAppointmentRequests = false;
    public $showUsersModal = false;
    public $showStatistiques = false;
    public $showPatientMenu = false;
    public $showCabinetMenu = false;

    // États des actions et données
    public $action = null;
    public $isDocteurProprietaire = false;
    public $isDocteur = false;
    public $isSecretaire = false;
    public $canManageRdv = false;
    public $canViewAllRdv = false;
    public $pendingAppointmentRequestsCount = 0;
    public $selectedPatient = null;
    public $editingPatient;
    public $patientToDelete;

    protected $listeners = [
        'patientSelected' => 'setPatient',
        'patientCreated' => 'handlePatientCreated',
        'closeCreateModal' => 'closeCreateModal',
        'assureurCreated' => 'handleAssureurCreated',
        'acteCreated' => 'handleActeCreated',
        'openModal' => 'handleOpenModal'
    ];

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $this->initializeRoles();
        $this->refreshPendingAppointmentRequestsCount();

        if (request()->has('patient_id')) {
            $patient = Patient::where('fkidcabinet', Auth::user()->fkidcabinet)
                ->find(request()->patient_id);
            if ($patient) {
                $this->setPatient($patient);
            }
        }
    }

    private function initializeRoles()
    {
        $user = Auth::user();
        
        $this->isDocteurProprietaire = $user->isDocteurProprietaire();
        $this->isDocteur = $user->isDocteur();
        $this->isSecretaire = $user->isSecretaire();

        $this->canManageRdv = $this->isDocteurProprietaire || $this->isSecretaire || $this->isDocteur;
        $this->canViewAllRdv = $this->isDocteurProprietaire || $this->isSecretaire;
    }

    public function refreshPendingAppointmentRequestsCount()
    {
        if (!Auth::check()) {
            $this->pendingAppointmentRequestsCount = 0;
            return;
        }

        $query = PublicAppointmentRequest::where('fkidcabinet', Auth::user()->fkidcabinet)
            ->where('status', 'pending');

        if ($this->isDocteur && !$this->isDocteurProprietaire) {
            $query->where('fkidmedecin', Auth::user()->fkidmedecin);
        }

        $this->pendingAppointmentRequestsCount = $query->count();
    }

    public function handleOpenModal($modalName)
    {
        switch ($modalName) {
            case 'liste-actes':
                $this->showListeActesModal = true;
                break;
            // Ajoutez d'autres cas si nécessaire
        }
    }

    public function ouvrirListeActesModal()
    {
        $this->showListeActesModal = true;
        $this->showCaisseOperations = false;
        $this->showStatistiques = false;
        $this->dispatch('listeActesModalOpened');
    }

    public function fermerListeActesModal()
    {
        $this->showListeActesModal = false;
    }

    public function resetState()
    {
        $this->reset([
            'showCreateModal',
            'showCreateRdvModal',
            'showPatientListModal',
            'showGestionPatientsModal',
            'showActeModal',
            'showAssureurModal',
            'showCreatePatientModal',
            'showPatientDetails',
            'showPaymentHistory',
            'showEditModal',
            'showDeleteConfirmation',
            'showCreateActeModal',
            'showListeActesModal',
            'showCaisseOperations',
            'showAppointmentRequests',
            'action',
            'editingPatient',
            'patientToDelete'
        ]);
    }

    public function setAction($action)
    {
        if (!$this->selectedPatient && in_array($action, ['consultation', 'reglement', 'rendezvous'])) {
            return;
        }
        $this->action = $action;
        $this->showCaisseOperations = false;
        $this->showStatistiques = false;
        $this->showPatientMenu = true;
        $this->dispatch('actionChanged', action: $action);
    }

    public function clearAction()
    {
        $this->action = null;
    }

    public function togglePatientMenu()
    {
        if (!$this->selectedPatient) {
            return;
        }

        $this->showPatientMenu = !$this->showPatientMenu;
        $this->showCabinetMenu = false;
    }

    public function toggleCabinetMenu()
    {
        $this->showCabinetMenu = !$this->showCabinetMenu;
        $this->showPatientMenu = false;
    }

    public function setPatient($patient)
    {
        $this->selectedPatient = $patient;
        $this->action = null;
        $this->resetState();
        $this->showPatientMenu = (bool) $patient;
    }

    // Gestionnaires d'événements
    public function handlePatientCreated($patientId)
    {
        $patient = Patient::where('fkidcabinet', Auth::user()->fkidcabinet)
            ->find($patientId);
        if ($patient) {
            $this->setPatient($patient);
            $this->showCreatePatientModal = false;
        }
    }

    public function handleAssureurCreated()
    {
        $this->showAssureurModal = false;
    }

    public function handleActeCreated()
    {
        $this->showActeModal = false;
    }

    // Méthodes de gestion des modales
    public function openGestionPatientsModal()
    {
        $this->showGestionPatientsModal = true;
    }

    public function closeGestionPatientsModal()
    {
        $this->showGestionPatientsModal = false;
    }

    public function showCreateRdv()
    {
        $this->showCreateRdvModal = true;
    }

    public function closeCreateRdvModal()
    {
        $this->showCreateRdvModal = false;
    }

    public function showCaisseOperations()
    {
        $this->resetState();
        $this->showCaisseOperations = true;
        $this->showStatistiques = false;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
    }

    public function closeCaisseOperations()
    {
        $this->showCaisseOperations = false;
    }

    public function openAppointmentRequests()
    {
        $this->refreshPendingAppointmentRequestsCount();
        $this->resetState();
        $this->showAppointmentRequests = true;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
    }

    public function closeAppointmentRequests()
    {
        $this->showAppointmentRequests = false;
    }

    public function ouvrirAssureurModal()
    {
        $this->showAssureurModal = true;
        $this->showStatistiques = false;
    }

    public function fermerAssureurModal()
    {
        $this->showAssureurModal = false;
    }

    public function showCreatePatient()
    {
        $this->showCreatePatientModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function openPatientListModal()
    {
        $this->showPatientListModal = true;
    }

    public function closePatientListModal()
    {
        $this->showPatientListModal = false;
    }

    public function ouvrirActeModal()
    {
        $this->showActeModal = true;
    }

    public function fermerActeModal()
    {
        $this->showActeModal = false;
    }

    public function openUsersModal()
    {
        $this->showUsersModal = true;
        $this->showStatistiques = false;
    }

    public function closeUsersModal()
    {
        $this->showUsersModal = false;
    }

    public function showStatistiques()
    {
        $this->resetState();
        $this->showStatistiques = true;
        $this->showPatientMenu = false;
        $this->showCabinetMenu = false;
    }

    public function closeStatistiques()
    {
        $this->showStatistiques = false;
    }

    public function render()
    {
        return view('livewire.accueil-patient');
    }
}
