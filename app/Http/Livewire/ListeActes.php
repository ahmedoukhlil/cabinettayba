<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Acte;

class ListeActes extends Component
{
    public $actes;
    public $search = '';
    public $selectedTypeActe = '';
    public $typesActes;

    public function mount($actes)
    {
        $this->actes = $actes;
        $this->typesActes = \App\Models\TypeActe::orderBy('TypeActe')->get();
    }

    public function updatedSearch()
    {
        $this->emit('searchUpdated', $this->search);
    }

    public function updatedSelectedTypeActe()
    {
        $this->emit('typeActeUpdated', $this->selectedTypeActe);
    }

    public function selectActe($acteId)
    {
        $this->emit('acteSelected', $acteId);
    }

    public function render()
    {
        $query = Acte::query()
            ->where('Masquer', 0)
            ->orderBy('nordre');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('Acte', 'like', '%' . $this->search . '%')
                  ->orWhere('ActeArab', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedTypeActe) {
            $query->where('fkidTypeActe', $this->selectedTypeActe);
        }

        $actes = $query->get();

        return view('livewire.liste-actes', [
            'actes' => $actes
        ]);
    }
} 