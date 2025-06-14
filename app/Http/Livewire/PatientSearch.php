<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class PatientSearch extends Component
{
    public $search = '';
    public $searchBy = 'telephone';
    public $patients = [];
    public $selectedPatient = null;
    public $isSearching = false;

    protected $listeners = ['clearPatient' => 'clearPatient'];

    public function mount()
    {
    }

    public function updatedSearch()
    {
        if (strlen($this->search) < 1) {
            $this->patients = [];
            return;
        }

        $this->isSearching = true;

        try {
            $query = Patient::query();
            
            switch ($this->searchBy) {
                case 'nom':
                    $query->where('Nom', 'like', '%' . $this->search . '%');
                    break;
                case 'identifiant':
                    $query->where('IdentifiantPatient', 'like', '%' . $this->search . '%');
                    break;
                case 'telephone':
                    $searchPhone = preg_replace('/[^0-9]/', '', $this->search);
                    if (!empty($searchPhone)) {
                        $query->where(function($q) use ($searchPhone) {
                            $q->whereRaw("REPLACE(REPLACE(REPLACE(Telephone1, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $searchPhone . '%'])
                                 ->orWhereRaw("REPLACE(REPLACE(REPLACE(Telephone2, ' ', ''), '-', ''), '+', '') LIKE ?", ['%' . $searchPhone . '%']);
                        });
                    }
                    break;
            }
            
            $query->where('fkidcabinet', Auth::user()->fkidcabinet);
            
            $this->patients = $query->select(
                'patients.ID',
                'patients.Nom as NomPatient',
                'patients.Prenom',
                'patients.IdentifiantPatient',
                'patients.Telephone1',
                'patients.Telephone2',
                'patients.Adresse',
                'patients.Genre',
                'patients.DtNaissance',
                'patients.Assureur',
                'assureurs.TauxdePEC',
                'assureurs.LibAssurance'
            )
            ->leftJoin('assureurs', 'patients.Assureur', '=', 'assureurs.IDAssureur')
            ->limit(10)
            ->get()
            ->map(function($patient) {
                return [
                    'ID' => $patient->ID,
                    'NomPatient' => $patient->NomPatient,
                    'Prenom' => $patient->Prenom,
                    'IdentifiantPatient' => $patient->IdentifiantPatient,
                    'Telephone1' => $patient->Telephone1,
                    'Telephone2' => $patient->Telephone2,
                    'Adresse' => $patient->Adresse,
                    'Genre' => $patient->Genre,
                    'DtNaissance' => $patient->DtNaissance,
                    'Assureur' => $patient->Assureur,
                    'TauxPEC' => $patient->TauxdePEC ?? 0,
                    'NomAssureur' => $patient->LibAssurance ?? ''
                ];
            })
            ->toArray();
        } catch (\Exception $e) {
            $this->patients = [];
            session()->flash('error', 'Une erreur est survenue lors de la recherche du patient.');
        }

        $this->isSearching = false;
    }

    public function selectPatient($patientId)
    {
        try {
            $patient = Patient::find($patientId);
            
            if ($patient) {
                $assureur = $patient->assureur;
                
                $this->selectedPatient = [
                    'ID' => $patient->ID,
                    'NomPatient' => $patient->Nom,
                    'Prenom' => $patient->Prenom,
                    'IdentifiantPatient' => $patient->IdentifiantPatient,
                    'Telephone1' => $patient->Telephone1,
                    'Telephone2' => $patient->Telephone2,
                    'Adresse' => $patient->Adresse,
                    'Genre' => $patient->Genre,
                    'DtNaissance' => $patient->DtNaissance,
                    'Assureur' => $patient->Assureur,
                    'TauxPEC' => $assureur ? $assureur->TauxdePEC : 0,
                    'NomAssureur' => $assureur ? $assureur->LibAssurance : ''
                ];
                
                // Effacer la recherche pour fermer la liste déroulante
                $this->search = '';
                $this->patients = [];
                
                $this->emit('patientSelected', $this->selectedPatient);
            } else {
                session()->flash('error', 'Patient non trouvé.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la sélection du patient.');
        }
    }

    public function clearPatient()
    {
        $this->search = '';
        $this->patients = [];
        $this->selectedPatient = null;
        $this->emit('patientCleared');
    }

    public function render()
    {
        return view('livewire.patient-search');
    }
} 