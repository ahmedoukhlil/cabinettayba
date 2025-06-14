<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Rendezvou;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Acte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateRendezVous extends Component
{
    use WithPagination;

    // Propriétés du formulaire
    public $patient_id = '';
    public $medecin_id = '';
    public $date_rdv = '';
    public $heure_rdv;
    public $acte_prevu = 'Consultation';
    public $rdv_confirmer = 'confirmé';
    public $patient = null;

    // Propriétés pour la recherche de patients
    public $search = '';
    public $searchBy = 'phone'; // name, nni, phone
    public $isSearching = false;
    public $patients = [];
    public $medecins = [];
    public $actes = [];

    // Propriétés pour les permissions
    public $canManageRdv = false;
    public $canViewAllRdv = false;
    public $isDocteurProprietaire = false;
    public $isSecretaire = false;
    public $isDocteur = false;

    protected $rules = [
        'patient_id' => 'required',
        'medecin_id' => 'required',
        'date_rdv' => 'required|date',
        'heure_rdv' => 'required',
        'acte_prevu' => 'required|min:3',
        'rdv_confirmer' => 'required'
    ];

    protected $messages = [
        'patient_id.required' => 'Le patient est requis',
        'medecin_id.required' => 'Le médecin est requis',
        'date_rdv.required' => 'La date est requise',
        'date_rdv.date' => 'La date n\'est pas valide',
        'heure_rdv.required' => 'L\'heure est requise',
        'acte_prevu.required' => 'L\'acte prévu est requis',
        'acte_prevu.min' => 'L\'acte prévu doit contenir au moins 3 caractères',
        'rdv_confirmer.required' => 'Le statut est requis'
    ];

    protected $listeners = [
        'patientSelected' => 'handlePatientSelected',
        'patientCleared' => 'handlePatientCleared',
    ];

    public function mount($patient = null)
    {
        $this->patients = collect();
        $this->medecins = Medecin::where('Masquer', 0)->orderBy('Nom')->get();
        $this->actes = Acte::where('Masquer', 0)->orderBy('Acte')->get();
        $this->initializePermissions();
        if ($patient) {
            $this->patient = $patient;
            $this->patient_id = is_array($patient) ? $patient['ID'] : $patient->ID;
        }
    }

    protected function initializePermissions()
    {
        $user = Auth::user();
        
        // Définir les rôles
        $this->isDocteurProprietaire = $user->isDocteurProprietaire();
        $this->isSecretaire = $user->isSecretaire();
        $this->isDocteur = $user->isDocteur() && !$user->isDocteurProprietaire();

        // Définir les permissions
        $this->canManageRdv = $this->isDocteurProprietaire || $this->isSecretaire || $this->isDocteur;
        $this->canViewAllRdv = $this->isDocteurProprietaire || $this->isSecretaire;
        
        // Si c'est un docteur simple, on force son ID
        if ($this->isDocteur) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    public function createRendezVous()
    {
        $this->validate();

        // Vérification supplémentaire des permissions
        $user = Auth::user();
        if ($this->isDocteur && $this->medecin_id != $user->fkidmedecin) {
            session()->flash('error', 'Vous ne pouvez créer des rendez-vous que pour vous-même.');
            return;
        }

        try {
            $rendezVous = Rendezvou::create([
                'fkidPatient' => $this->patient_id,
                'fkidMedecin' => $this->medecin_id,
                'dtPrevuRDV' => $this->date_rdv,
                'HeureRdv' => $this->heure_rdv,
                'ActePrevu' => $this->acte_prevu,
                'rdvConfirmer' => $this->rdv_confirmer,
                'DtAjRdv' => now(),
                'user' => Auth::id(),
                'fkidcabinet' => Auth::user()->fkidcabinet,
                'OrdreRDV' => Rendezvou::generateNextOrderNumber($this->date_rdv)
            ]);

            $this->reset(['patient_id', 'heure_rdv', 'acte_prevu']);
            $this->resetPage();
            session()->flash('message', 'Rendez-vous créé avec succès.');
            
            // Rediriger vers l'impression du reçu
            return redirect()->route('rendez-vous.print', ['id' => $rendezVous->IDRdv]);
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la création du rendez-vous.');
        }
    }

    public function updatedDateRdv($value)
    {
        $this->resetPage();
    }

    public function updatedMedecinId($value)
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        if (strlen(trim($this->search)) < 2) {
            $this->patients = collect();
            return;
        }

        $this->performSearch();
    }

    public function updatedSearchBy()
    {
        if (strlen(trim($this->search)) >= 2) {
            $this->performSearch();
        }
    }

    protected function performSearch()
    {
        $this->isSearching = true;

        try {
            $query = Patient::query();

            switch ($this->searchBy) {
                case 'name':
                    $query->where(function($q) {
                        $q->where('Nom', 'like', '%' . $this->search . '%')
                          ->orWhere('Prenom', 'like', '%' . $this->search . '%');
                    });
                    break;
                case 'nni':
                    $query->where('NNI', 'like', '%' . $this->search . '%');
                    break;
                case 'phone':
                    $searchPhone = preg_replace('/[^0-9]/', '', $this->search);
                    if (!empty($searchPhone)) {
                        $query->where(function($q) use ($searchPhone) {
                            $q->where(function($subQ) use ($searchPhone) {
                                $subQ->whereRaw("REPLACE(REPLACE(REPLACE(Telephone1, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $searchPhone . '%'])
                                     ->orWhereRaw("REPLACE(REPLACE(REPLACE(Telephone2, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $searchPhone . '%']);
                            });
                        });
                    }
                    break;
            }

            $query->where('fkidcabinet', 1);

            $this->patients = $query->select('ID', 'Nom', 'Prenom', 'NNI', 'Telephone1', 'Telephone2', 'Assureur')
                                  ->orderBy('Nom')
                                  ->limit(10)
                                  ->get();

        } catch (\Exception $e) {
            $this->patients = collect();
        }

        $this->isSearching = false;
    }

    public function selectPatient($patientId)
    {
        try {
            $patient = $this->patients->firstWhere('ID', $patientId);
            if ($patient) {
                $this->patient_id = $patientId;
                $this->search = '';
                $this->patients = collect();
            }
        } catch (\Exception $e) {
            // logger('Erreur lors de la sélection du patient', [
            //     'error' => $e->getMessage()
            // ]);
        }
    }

    public function clearPatient()
    {
        $this->patient_id = null;
        $this->search = '';
        $this->patients = collect();
    }

    public function handlePatientSelected($patient)
    {
        $this->patient = $patient;
        $this->patient_id = is_array($patient) ? $patient['ID'] : $patient->ID;
    }

    public function handlePatientCleared()
    {
        $this->patient = null;
        $this->patient_id = null;
    }

    public function getTotalRdvJourProperty()
    {
        return \App\Models\Rendezvou::whereDate('dtPrevuRDV', now()->toDateString())->count();
    }

    public function render()
    {
        $query = Rendezvou::query()
            ->with(['patient', 'medecin'])
            ->orderBy('dtPrevuRDV', 'desc')
            ->orderBy('HeureRdv', 'desc');

        // Si un médecin est sélectionné, filtrer par ce médecin
        if ($this->medecin_id) {
            $query->where('fkidMedecin', $this->medecin_id);
        }

        // Si une date est sélectionnée, filtrer par cette date
        if ($this->date_rdv) {
            $query->whereDate('dtPrevuRDV', $this->date_rdv);
        }

        // Si c'est un docteur simple, ne montrer que ses rendez-vous
        if ($this->isDocteur) {
            $query->where('fkidMedecin', Auth::user()->fkidmedecin);
        }

        $rendezVous = $query->paginate(10);

        return view('livewire.create-rendez-vous', [
            'rendezVous' => $rendezVous,
            'totalRdvJour' => $this->totalRdvJour,
        ]);
    }
}