<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Acte;

class ActeSearch extends Component
{
    public $search = '';
    public $actes = [];
    public $selectedActeId = null;
    public $showDropdown = false;
    public $fkidassureur = null;

    protected $updatesQueryString = ['fkidassureur'];

    public function mount($fkidassureur = null)
    {
        $this->fkidassureur = $fkidassureur;
    }

    public function updatedSearch($value)
    {
        $this->showDropdown = true;
        $query = Acte::where('Acte', 'like', '%' . $value . '%');
        if ($this->fkidassureur) {
            $query->where('fkidassureur', $this->fkidassureur);
        }
        $this->actes = $query
            ->select('ID', 'Acte', 'PrixRef')
            ->orderBy('Acte')
            ->limit(30)
            ->get()
            ->unique('Acte')
            ->values();
    }

    public function selectActe($id)
    {
        if (!$id) {
            return;
        }

        $id = is_string($id) ? (int)$id : $id;
        $this->selectedActeId = $id;
        $acte = Acte::find($id);

        if ($acte) {
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
