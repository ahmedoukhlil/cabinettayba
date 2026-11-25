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
    public $isFocused = false;

    private const PHONE_MIN_LENGTH = 3;

    protected $listeners = ['clearPatient' => 'clearPatient'];

    public function mount()
    {
    }

    public function updatedSearch()
    {
        $search = trim((string) $this->search);
        $searchLength = $this->searchBy === 'telephone'
            ? strlen(preg_replace('/[^0-9]/', '', $search))
            : mb_strlen($search);

        if ($searchLength < $this->minimumSearchLength()) {
            $this->patients = [];
            $this->isSearching = false;
            return;
        }

        $this->isSearching = true;
        $this->isFocused = true;

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
                'patients.IdentifiantAssurance',
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
                    'IdentifiantAssurance' => $patient->IdentifiantAssurance,
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
        
        // Maintenir le focus après la recherche
        $this->dispatch('maintain-focus');
    }

    public function updatedSearchBy()
    {
        $this->patients = [];
        $this->updatedSearch();
    }

    public function selectPatient($patientId)
    {
        try {
            $patient = Patient::where('fkidcabinet', Auth::user()->fkidcabinet)
                ->find($patientId);
            
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
                    'IdentifiantAssurance' => $patient->IdentifiantAssurance,
                    'TauxPEC' => $assureur ? $assureur->TauxdePEC : 0,
                    'NomAssureur' => $assureur ? $assureur->LibAssurance : ''
                ];
                
                // Fermer la liste après sélection mais garder le champ ouvert
                $this->isFocused = false;
                $this->patients = [];
                
                $this->dispatch('patientSelected', patient: $this->selectedPatient);
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
        $this->dispatch('patientCleared');
    }

    /**
     * Sélectionner le premier patient de la liste avec la touche Entrée
     */
    public function selectFirstPatient()
    {
        if (!empty($this->patients)) {
            $firstPatient = $this->patients[0];
            $this->selectPatient($firstPatient['ID']);
        }
    }

    /**
     * Gérer le focus sur le champ de recherche
     */
    public function onFocus()
    {
        $this->isFocused = true;
        // Si il y a déjà une recherche, afficher les résultats
        if ($this->hasEnoughCharacters() && empty($this->patients)) {
            $this->updatedSearch();
        }
    }

    /**
     * Gérer la perte de focus sur le champ de recherche
     */
    public function onBlur()
    {
        $this->closeResults();
    }

    /**
     * Fermer la liste après un délai
     */
    public function closeResults()
    {
        $this->isFocused = false;
        $this->patients = [];
    }

    /**
     * Maintenir la liste ouverte
     */
    public function keepOpen()
    {
        $this->isFocused = true;
    }

    /**
     * Fermer explicitement la liste
     */
    public function closeList()
    {
        $this->isFocused = false;
        $this->patients = [];
    }

    public function render()
    {
        return view('livewire.patient-search', [
            'minimumSearchLength' => $this->minimumSearchLength(),
        ]);
    }

    private function minimumSearchLength(): int
    {
        return $this->searchBy === 'telephone' ? self::PHONE_MIN_LENGTH : 1;
    }

    private function hasEnoughCharacters(): bool
    {
        $value = trim((string) $this->search);
        $length = $this->searchBy === 'telephone'
            ? strlen(preg_replace('/[^0-9]/', '', $value))
            : mb_strlen($value);

        return $length >= $this->minimumSearchLength();
    }
}
