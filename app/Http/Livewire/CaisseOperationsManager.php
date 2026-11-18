<?php

namespace App\Http\Livewire;

use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CaisseOperationsManager extends Component
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

        // Par défaut, filtrer sur la journée courante
        $today = now()->toDateString();
        $this->date_debut = $today;
        
        if ($this->isDocteur) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    public function resetFilters()
    {
        $this->reset(['medecin_id', 'date_debut', 'date_fin']);
        $this->resetPage();
    }

    private function getMedecins()
    {
        return Medecin::where('fkidcabinet', Auth::user()->fkidcabinet)
            ->orderBy('Nom')
            ->get(['idMedecin', 'Nom']);
    }

    private function operationsQuery()
    {
        $user = Auth::user();
        $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet);

        if ($this->isDocteur) {
            $query->where('fkidmedecin', $user->fkidmedecin);
        } elseif ($this->isSecretaire) {
            $query->where('fkiduser', Auth::id());
            if ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }
        } elseif ($this->medecin_id) {
            $query->where('fkidmedecin', $this->medecin_id);
        }

        $date = $this->date_debut ?: now()->toDateString();
        $query->whereDate('dateoper', $date);

        return $query;
    }

    private function getPaginatedOperations()
    {
        return $this->operationsQuery()
            ->orderBy('dateoper', 'desc')
            ->orderBy('cle', 'desc')
            ->paginate(10);
    }

    public function render()
    {
        $medecins = $this->getMedecins();

        $totaux = (clone $this->operationsQuery())
            ->selectRaw('COALESCE(SUM(entreEspece), 0) as recettes')
            ->selectRaw('COALESCE(SUM(retraitEspece), 0) as depenses')
            ->first();

        $totalRecettes = (float) $totaux->recettes;
        $totalDepenses = (float) $totaux->depenses;
        $solde = $totalRecettes - $totalDepenses;

        $totauxGenerauxParMoyenPaiement = (clone $this->operationsQuery())
            ->whereNotNull('TypePAie')
            ->select('TypePAie')
            // Placer COALESCE dans SUM permet à MySQL/MariaDB de reconnaître
            // sans ambiguïté chaque expression comme une agrégation avec
            // ONLY_FULL_GROUP_BY activé.
            ->selectRaw('SUM(COALESCE(entreEspece, 0)) as total_entrees')
            ->selectRaw('SUM(COALESCE(retraitEspece, 0)) as total_retraits')
            ->groupBy('TypePAie')
            ->get()
            ->mapWithKeys(function ($row) {
                $recettes = (float) $row->total_entrees;
                $depenses = (float) $row->total_retraits;

                return [$row->TypePAie => [
                    'recettes' => $recettes,
                    'depenses' => $depenses,
                    'solde' => $recettes - $depenses,
                ]];
            })
            ->all();

        $totauxParMedecinEtMode = (clone $this->operationsQuery())
            ->select(['fkidmedecin', 'TypePAie'])
            ->selectRaw('SUM(COALESCE(entreEspece, 0)) as total_entrees')
            ->selectRaw('SUM(COALESCE(retraitEspece, 0)) as total_retraits')
            ->groupBy('fkidmedecin', 'TypePAie')
            ->get();

        $medecinsMap = Medecin::whereIn(
            'idMedecin',
            $totauxParMedecinEtMode->pluck('fkidmedecin')->filter()->unique()
        )->get(['idMedecin', 'Nom'])->keyBy('idMedecin');

        $totauxParMedecin = $totauxParMedecinEtMode
            ->groupBy('fkidmedecin')
            ->map(function ($rows, $medecinId) use ($medecinsMap) {
                $recettes = (float) $rows->sum('total_entrees');
                $depenses = (float) $rows->sum('total_retraits');
                $modesPaiement = $rows->filter(fn ($row) => $row->TypePAie)
                    ->mapWithKeys(function ($row) {
                        $recettes = (float) $row->total_entrees;
                        $depenses = (float) $row->total_retraits;

                        return [$row->TypePAie => [
                            'recettes' => $recettes,
                            'depenses' => $depenses,
                            'solde' => $recettes - $depenses,
                        ]];
                    })->all();

                return [
                    'nom' => optional($medecinsMap->get($medecinId))->Nom ?? 'Non renseigné',
                    'recettes' => $recettes,
                    'depenses' => $depenses,
                    'solde' => $recettes - $depenses,
                    'modes_paiement' => $modesPaiement,
                ];
            })->all();

        $paginatedOperations = $this->getPaginatedOperations();
        $pageOperations = $paginatedOperations->getCollection();
        $pageMedecins = Medecin::whereIn('idMedecin', $pageOperations->pluck('fkidmedecin')->filter()->unique())
            ->get(['idMedecin', 'Nom'])
            ->keyBy('idMedecin');
        $patientsMap = Patient::whereIn('ID', $pageOperations->pluck('fkidTiers')->map(fn ($id) => (int) $id)->filter()->unique())
            ->get(['ID', 'Nom', 'Prenom'])
            ->keyBy('ID');

        $pageOperations->each(function ($operation) use ($pageMedecins, $patientsMap) {
            $operation->medecin = $pageMedecins->get($operation->fkidmedecin);
            $operation->tiers = $patientsMap->get((int) $operation->fkidTiers);
        });

        return view('livewire.caisse-operations-manager', [
            'operations' => $paginatedOperations,
            'medecins' => $medecins,
            'isSecretaire' => $this->isSecretaire,
            'isDocteur' => $this->isDocteur,
            'isDocteurProprietaire' => $this->isDocteurProprietaire,
            'totalRecettes' => $totalRecettes,
            'totalDepenses' => $totalDepenses,
            'solde' => $solde,
            'totauxParMedecin' => $totauxParMedecin,
            'totauxGenerauxParMoyenPaiement' => $totauxGenerauxParMoyenPaiement
        ]);
    }
}
