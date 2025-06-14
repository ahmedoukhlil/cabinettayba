<?php

namespace App\Http\Livewire;

use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_MEDECINS = 'caisse_medecins';
    private const CACHE_KEY_OPERATIONS = 'caisse_operations_';

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

    private function getCacheKey()
    {
        $user = Auth::user();
        $key = self::CACHE_KEY_OPERATIONS . $user->fkidcabinet;
        if ($this->medecin_id) $key .= '_m' . $this->medecin_id;
        if ($this->date_debut) $key .= '_d' . $this->date_debut;
        if ($this->date_fin) $key .= '_f' . $this->date_fin;
        return $key;
    }

    private function getMedecins()
    {
        return Cache::remember(self::CACHE_KEY_MEDECINS, self::CACHE_TTL, function () {
            return Medecin::orderBy('Nom')->get();
        });
    }

    private function getOperations()
    {
        $cacheKey = $this->getCacheKey();
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $user = Auth::user();
            $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet);

            if ($this->isDocteur) {
                $query->where('fkidmedecin', $user->fkidmedecin);
            } elseif ($this->isSecretaire) {
                $query->where('fkiduser', $user->id);
                if ($this->medecin_id) {
                    $query->where('fkidmedecin', $this->medecin_id);
                }
            } elseif ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }

            // Filtrer sur la date choisie (ou aujourd'hui par défaut)
            $date = $this->date_debut ?: now()->toDateString();
            $query->whereDate('dateoper', $date);

            return $query->get();
        });
    }

    private function getPaginatedOperations()
    {
        $user = Auth::user();
        $query = CaisseOperation::where('fkidcabinet', $user->fkidcabinet);

        if ($this->isDocteur) {
            $query->where('fkidmedecin', $user->fkidmedecin);
        } elseif ($this->isSecretaire) {
            $query->where('fkiduser', $user->id);
            if ($this->medecin_id) {
                $query->where('fkidmedecin', $this->medecin_id);
            }
        } elseif ($this->medecin_id) {
            $query->where('fkidmedecin', $this->medecin_id);
        }

        // Filtrer sur la date choisie (ou aujourd'hui par défaut)
        $date = $this->date_debut ?: now()->toDateString();
        $query->whereDate('dateoper', $date);

        return $query->orderBy('dateoper', 'desc')
                    ->orderBy('cle', 'desc')
                    ->paginate(10);
    }

    public function render()
    {
        // Récupérer les médecins (avec cache)
        $medecins = $this->getMedecins();

        // Récupérer les opérations (avec cache)
        $operations = $this->getOperations();

        // Charger les médecins et patients en une seule requête
        $medecinIds = $operations->pluck('fkidmedecin')->unique()->filter();
        $patientIds = $operations->pluck('fkidTiers')->map(fn($id) => (int)$id)->unique()->filter();
        
        $medecinsMap = Medecin::whereIn('idMedecin', $medecinIds)->get()->keyBy('idMedecin');
        $patientsMap = Patient::whereIn('ID', $patientIds)->get()->keyBy('ID');

        // Associer les médecins et patients aux opérations
        $operations->each(function($operation) use ($medecinsMap, $patientsMap) {
            $operation->medecin = $medecinsMap->get($operation->fkidmedecin);
            $operation->tiers = $patientsMap->get((int)$operation->fkidTiers);
        });

        // Calculer les totaux
        $totalRecettes = $operations->sum('entreEspece');
        $totalDepenses = $operations->sum('retraitEspece');
        $solde = $totalRecettes - $totalDepenses;

        // Calculer les totaux par mode de paiement
        $typesPaiement = $operations->pluck('TypePAie')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $totauxGenerauxParMoyenPaiement = [];
        foreach ($typesPaiement as $type) {
            $operationsType = $operations->where('TypePAie', $type);
            $totauxGenerauxParMoyenPaiement[$type] = [
                'recettes' => $operationsType->sum('entreEspece'),
                'depenses' => $operationsType->sum('retraitEspece'),
                'solde' => $operationsType->sum('entreEspece') - $operationsType->sum('retraitEspece')
            ];
        }

        // Calculer les totaux par médecin
        $totauxParMedecin = $medecinsMap->map(function($medecin) use ($operations) {
            $operationsMedecin = $operations->where('fkidmedecin', $medecin->idMedecin);
            $recettes = $operationsMedecin->sum('entreEspece');
            $depenses = $operationsMedecin->sum('retraitEspece');
            
            // Calculer les totaux par mode de paiement pour ce médecin
            $modesPaiement = [];
            foreach ($operationsMedecin->pluck('TypePAie')->unique() as $type) {
                $opsType = $operationsMedecin->where('TypePAie', $type);
                $modesPaiement[$type] = [
                    'recettes' => $opsType->sum('entreEspece'),
                    'depenses' => $opsType->sum('retraitEspece'),
                    'solde' => $opsType->sum('entreEspece') - $opsType->sum('retraitEspece')
                ];
            }

            return [
                'nom' => $medecin->Nom,
                'recettes' => $recettes,
                'depenses' => $depenses,
                'solde' => $recettes - $depenses,
                'modes_paiement' => $modesPaiement
            ];
        })->toArray();

        // Récupérer les opérations paginées
        $paginatedOperations = $this->getPaginatedOperations();
        
        // Associer les médecins et patients aux opérations paginées
        $paginatedOperations->getCollection()->each(function($operation) use ($medecinsMap, $patientsMap) {
            $operation->medecin = $medecinsMap->get($operation->fkidmedecin);
            $operation->tiers = $patientsMap->get((int)$operation->fkidTiers);
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