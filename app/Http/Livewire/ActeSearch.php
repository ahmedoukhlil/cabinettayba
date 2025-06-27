<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\ActeService;

class ActeSearch extends Component
{
    public $search = '';
    public $actes = [];
    public $selectedActeId = null;
    public $showDropdown = false;
    public $fkidassureur = null;
    public $popularActes = [];

    protected $updatesQueryString = ['fkidassureur'];

    protected $acteService;

    public function boot(ActeService $acteService)
    {
        $this->acteService = $acteService;
    }

    public function mount($fkidassureur = null)
    {
        $this->fkidassureur = $fkidassureur;
        $this->loadPopularActes();
    }

    /**
     * Charge les actes populaires
     */
    public function loadPopularActes()
    {
        $this->popularActes = $this->acteService->getPopularActes($this->fkidassureur);
    }

    /**
     * Recherche optimisée avec debouncing
     */
    public function updatedSearch($value)
    {
        if (strlen($value) < 2) {
            $this->showDropdown = false;
            $this->actes = collect();
            return;
        }

        $this->actes = $this->acteService->searchActes($value, $this->fkidassureur);
        $this->showDropdown = true;
    }

    /**
     * Sélection d'un acte avec validation
     */
    public function selectActe($id)
    {
        if (!$id) {
            return;
        }

        $id = is_string($id) ? (int)$id : $id;
        
        // Vérifier d'abord dans les résultats actuels
        $acte = $this->actes->firstWhere('ID', $id);
        
        if (!$acte) {
            // Si pas trouvé, chercher dans la base de données avec cache
            $acte = $this->acteService->getActeById($id);
        }

        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->search = $acte->Acte;
            $this->showDropdown = false;
            $this->emitUp('acteSelected', $acte->ID, $acte->PrixRef);
        }
    }

    public function render()
    {
        return view('livewire.acte-search');
    }
}
