<?php

namespace App\Http\Livewire;

use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use PDF;

class StatistiquesManager extends Component
{
    use WithPagination;

    public $medecin_id;
    public $date_debut;
    public $date_fin;
    public $isDocteurProprietaire = false;
    public $isSecretaire = false;
    public $isDocteur = false;

    protected $queryString = [
        'medecin_id' => ['except' => ''],
        'date_debut' => ['except' => ''],
        'date_fin' => ['except' => '']
    ];

    public function mount()
    {
        $user = Auth::user();
        $this->isSecretaire = ($user->IdClasseUser == 1);
        $this->isDocteur = ($user->IdClasseUser == 2);
        $this->isDocteurProprietaire = ($user->IdClasseUser == 3);
    }

    public function resetFilters()
    {
        $this->reset(['medecin_id', 'date_debut', 'date_fin']);
        $this->resetPage();
    }

    public function exportPdf($par = 'medecin')
    {
        $operations = $this->getOperations();
        $medecins = Medecin::all();
        $periode = [$this->date_debut, $this->date_fin];
        $pdf = PDF::loadView('exports.statistiques', compact('operations', 'medecins', 'periode', 'par'));
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'statistiques.pdf');
    }

    private function getOperations()
    {
        $user = Auth::user();
        $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet);
        if ($this->medecin_id) {
            $query->where('fkidmedecin', $this->medecin_id);
        }
        if ($this->date_debut) {
            $query->whereDate('dateoper', '>=', $this->date_debut);
        }
        if ($this->date_fin) {
            $query->whereDate('dateoper', '<=', $this->date_fin);
        }
        return $query->orderBy('dateoper', 'desc')->orderBy('cle', 'desc')->paginate(10);
    }

    public function render()
    {
        $operations = $this->getOperations();
        $medecins = Medecin::all();

        // Calcul des totaux généraux
        $totalRecettes = $operations->sum('entreEspece');
        $totalDepenses = $operations->sum('retraitEspece');
        $solde = $totalRecettes - $totalDepenses;

        // Totaux par mode de paiement
        $typesPaiement = $operations->pluck('TypePAie')->filter()->unique()->values()->toArray();
        $totauxGenerauxParMoyenPaiement = collect($typesPaiement)->mapWithKeys(function($type) use ($operations) {
            $recettes = $operations->where('TypePAie', $type)->where('entreEspece', '>', 0)->sum('MontantOperation');
            $depenses = $operations->where('TypePAie', $type)->where('retraitEspece', '>', 0)->sum('MontantOperation');
            return [$type => [
                'recettes' => $recettes,
                'depenses' => $depenses,
                'solde' => $recettes - $depenses
            ]];
        })->toArray();

        // Totaux par médecin
        $medecinsMap = $medecins->keyBy('idMedecin');
        $totauxParMedecin = collect($medecinsMap)->mapWithKeys(function($medecin) use ($operations, $typesPaiement) {
            $medecinOperations = $operations->where('fkidmedecin', $medecin->idMedecin);
            $recettes = $medecinOperations->where('entreEspece', '>', 0)->sum('MontantOperation');
            $depenses = $medecinOperations->where('retraitEspece', '>', 0)->sum('MontantOperation');
            $modesPaiement = collect($typesPaiement)->mapWithKeys(function($type) use ($medecinOperations) {
                $recettesType = $medecinOperations->where('TypePAie', $type)->where('entreEspece', '>', 0)->sum('MontantOperation');
                $depensesType = $medecinOperations->where('TypePAie', $type)->where('retraitEspece', '>', 0)->sum('MontantOperation');
                return [$type => [
                    'recettes' => $recettesType,
                    'depenses' => $depensesType,
                    'solde' => $recettesType - $depensesType
                ]];
            })->filter(fn($totals) => $totals['recettes'] > 0 || $totals['depenses'] > 0)->toArray();
            return [$medecin->idMedecin => [
                'nom' => $medecin->Nom,
                'recettes' => $recettes,
                'depenses' => $depenses,
                'solde' => $recettes - $depenses,
                'modes_paiement' => $modesPaiement
            ]];
        })->toArray();

        return view('livewire.statistiques-manager', compact(
            'operations',
            'medecins',
            'totalRecettes',
            'totalDepenses',
            'solde',
            'totauxGenerauxParMoyenPaiement',
            'totauxParMedecin'
        ));
    }
} 