<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Facture;
use App\Models\Patient;
use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\RefTypePaiement;
use Illuminate\Support\Facades\DB;
use App\Models\Detailfacturepatient;

class ReglementFacture extends Component
{
    public $selectedPatient = null;
    public $factures;
    public $factureSelectionnee;
    public $montantReglement;
    public $modePaiement;
    public $modesPaiement;
    public $dernierReglement = null;
    public $pourQui;
    public $showAddActeForm = false;
    public $selectedActeId = '';
    public $prixReference;
    public $prixFacture;
    public $quantite = 1;
    public $seance;
    public $factureIdForActe = null;
    public $actes = [];
    public $searchActe = '';
    public $filteredActes = [];
    public $acteSelectionne = false;
    public $showReglementModal = false;
    protected $facturesEnAttente;
    public $numberOfPaginatorsRendered = [];

    protected $listeners = [
        'patientSelected' => 'handlePatientSelected',
        'acteSelected' => 'handleActeSelected',
        'closeModal' => 'closeAddActeForm',
    ];

    public function mount($selectedPatient = null)
    {
        $this->modesPaiement = RefTypePaiement::all();
        $this->actes = \App\Models\Acte::all();
        $this->seance = 'Dent';
        
        if ($selectedPatient) {
            if (is_object($selectedPatient)) {
                $selectedPatient = (array) $selectedPatient;
            }
            $this->selectedPatient = $selectedPatient;
            $this->loadFactures();
        }
        $this->loadFacturesEnAttente();
    }

    public function handlePatientSelected($patient)
    {
        $this->selectedPatient = $patient;
        $this->loadFactures();
    }

    public function handleActeSelected($id, $prixReference)
    {
        $this->selectedActeId = $id;
        $this->prixReference = $prixReference;
        $this->prixFacture = $prixReference;
    }

    public function loadFactures()
    {
        if ($this->selectedPatient) {
            $this->factures = Facture::where('IDPatient', $this->selectedPatient['ID'])
                ->with(['medecin:idMedecin,Nom,Contact,DtAjout,fkidcabinet'])
                ->get()
                ->map(function ($facture) {
                    $isAssure = $facture->ISTP > 0;
                    $resteAPayerPatient = ($isAssure ? ($facture->TotalfactPatient ?? 0) : ($facture->TotFacture ?? 0)) - ($facture->TotReglPatient ?? 0);
                    $resteAPayerPEC = $isAssure ? (($facture->TotalPEC ?? 0) - ($facture->ReglementPEC ?? 0)) : 0;

                    return [
                        'id' => $facture->Idfacture,
                        'numero' => $facture->Nfacture,
                        'medecin' => $facture->medecin,
                        'montant_total' => $facture->TotFacture ?? 0,
                        'montant_pec' => floatval($facture->TotalPEC ?? 0),
                        'part_patient' => $facture->TotalfactPatient ?? 0,
                        'montant_reglements_patient' => $facture->TotReglPatient ?? 0,
                        'montant_reglements_pec' => $facture->ReglementPEC ?? 0,
                        'reste_a_payer' => $resteAPayerPatient,
                        'reste_a_payer_pec' => $resteAPayerPEC,
                        'TXPEC' => $facture->TXPEC ?? 0,
                        'ISTP' => $facture->ISTP ?? 0,
                        'est_reglee' => $resteAPayerPatient <= 0 && $resteAPayerPEC <= 0
                    ];
                });
        } else {
            $this->factures = null;
        }
    }

    public function selectionnerFacture($factureId)
    {
        $this->factureSelectionnee = collect($this->factures)->firstWhere('id', $factureId);
        if ($this->factureSelectionnee) {
            // Si la facture est déjà réglée, on permet d'ajouter un montant positif
            if ($this->factureSelectionnee['est_reglee']) {
                $this->montantReglement = 0;
            } else {
                $this->montantReglement = $this->factureSelectionnee['reste_a_payer'];
            }
            // Détection assuré ou non
            $facture = Facture::find($factureId);
            if ($facture && $facture->ISTP == 1) {
                $this->pourQui = 'patient'; // valeur par défaut
            } else {
                $this->pourQui = null;
            }
        }
    }

    public function enregistrerReglement()
    {
        $this->validate([
            'montantReglement' => 'required|numeric',
            'modePaiement' => 'required|exists:ref_type_paiement,idtypepaie',
        ]);
        // Si assuré, on doit avoir pourQui
        if ($this->pourQui === null && $this->factureSelectionnee && ($facture = Facture::find($this->factureSelectionnee['id'])) && $facture->ISTP == 1) {
            throw new \Exception('Veuillez préciser pour qui est le règlement (Patient ou PEC).');
        }
        try {
            DB::beginTransaction();

            $facture = Facture::find($this->factureSelectionnee['id']);
            if (!$facture) {
                throw new \Exception('Facture non trouvée');
            }

            $medecin = Medecin::find($facture->FkidMedecinInitiateur);
            if (!$medecin) {
                throw new \Exception('Médecin non trouvé');
            }

            $typePaiement = RefTypePaiement::find($this->modePaiement);
            if (!$typePaiement) {
                throw new \Exception('Mode de paiement non trouvé');
            }

            $isRemboursement = $this->montantReglement < 0;
            $isAcompte = $this->montantReglement > $this->factureSelectionnee['reste_a_payer'];
            $montantOperation = $this->montantReglement;
            $montantAbsolu = abs($montantOperation);

            $operation = CaisseOperation::create([
                'dateoper' => now(),
                'MontantOperation' => $montantOperation,
                'designation' => ($isRemboursement ? 'Remboursement' : ($isAcompte ? 'Acompte' : 'Règlement')) . ' facture N°' . $facture->Nfacture,
                'fkidTiers' => $this->selectedPatient['ID'],
                'entreEspece' => $isRemboursement ? 0 : $montantAbsolu,
                'retraitEspece' => $isRemboursement ? $montantAbsolu : 0,
                'pourPatFournisseur' => 0,
                'pourCabinet' => 1,
                'fkiduser' => auth()->id(),
                'exercice' => now()->year,
                'fkIdTypeTiers' => 1,
                'fkidfacturebord' => $facture->Idfacture,
                'DtCr' => now(),
                'fkidcabinet' => auth()->user()->fkidcabinet,
                'fkidtypePaie' => $this->modePaiement,
                'TypePAie' => $typePaiement->LibPaie,
                'fkidmedecin' => $facture->FkidMedecinInitiateur,
                'medecin' => $medecin->Nom
            ]);

            // Mise à jour de la facture selon le type de règlement
            if ($facture->ISTP == 1 && $this->pourQui === 'pec') {
                $facture->ReglementPEC = ($facture->ReglementPEC ?? 0) + $montantOperation;
            } else {
                $facture->TotReglPatient = ($facture->TotReglPatient ?? 0) + $montantOperation;
            }
            $facture->save();

            DB::commit();

            $this->dernierReglement = [
                'facture' => $facture,
                'patient' => $this->selectedPatient,
                'montant' => $montantOperation,
                'mode' => $typePaiement->LibPaie,
                'date' => now()->format('d/m/Y H:i'),
                'medecin' => $medecin->Nom,
                'operation' => $operation,
                'isRemboursement' => $isRemboursement,
                'isAcompte' => $isAcompte
            ];

            $this->reset(['montantReglement', 'modePaiement', 'factureSelectionnee', 'pourQui']);
            $this->loadFactures();
            session()->flash('message', ($isRemboursement ? 'Remboursement' : ($isAcompte ? 'Acompte' : 'Règlement')) . ' enregistré avec succès.');

            $receiptUrl = route('reglement-facture.receipt', $operation->getKey());
            $this->dispatchBrowserEvent('open-receipt', ['url' => $receiptUrl]);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage());
        }
    }

    public function resetAddActeForm()
    {
        $this->selectedActeId = '';
        $this->prixReference = null;
        $this->prixFacture = null;
        $this->quantite = 1;
        $this->seance = 'Dent';
        $this->acteSelectionne = false;
    }

    public function updatedSelectedActeId($value)
    {
        if (empty($value)) {
            $this->prixReference = null;
            $this->prixFacture = null;
            return;
        }

        $acteId = (int) $value;
        $acte = \App\Models\Acte::find($acteId);

        if ($acte) {
            $this->prixReference = $acte->PrixRef;
            $this->prixFacture = $acte->PrixRef;
        } else {
            $this->prixReference = null;
            $this->prixFacture = null;
        }
    }

    public function updatedSearchActe($value)
    {
        if (!$this->acteSelectionne) {
            $this->filteredActes = \App\Models\Acte::where('Acte', 'like', '%' . $value . '%')->get();
        }
    }

    public function selectActe($id = null)
    {
        if (!$id) {
            $this->resetAddActeForm();
            return;
        }

        $acte = \App\Models\Acte::find($id);

        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->prixReference = $acte->PrixRef;
            $this->prixFacture = $acte->PrixRef;
            $this->acteSelectionne = true;
        } else {
            $this->resetAddActeForm();
        }
    }

    public function saveActeToFacture()
    {
        $this->validate([
            'selectedActeId' => 'required|exists:actes,ID',
            'prixFacture' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:1',
            'seance' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $acte = \App\Models\Acte::find($this->selectedActeId);

            \App\Models\Detailfacturepatient::create([
                'fkidfacture' => $this->factureIdForActe,
                'DtAjout' => now(),
                'Actes' => $acte ? $acte->Acte : null,
                'PrixRef' => $this->prixReference,
                'PrixFacture' => $this->prixFacture,
                'Quantite' => $this->quantite,
                'fkidacte' => $this->selectedActeId,
                'Dents' => $this->seance ?: 'Dent',
            ]);

            // Mise à jour de la facture uniquement pour l'acte sélectionné
            $facture = \App\Models\Facture::find($this->factureIdForActe);
            $prixFactureActe = $this->prixFacture;
            $txpec = $facture->TXPEC ?? 0;
            $nouveauTotFacture = ($facture->TotFacture ?? 0) + $prixFactureActe;
            $montantPEC = $prixFactureActe * $txpec;
            $totalPEC = ($facture->TotalPEC ?? 0) + $montantPEC;
            $totalfactPatient = $nouveauTotFacture - $totalPEC;
            $facture->TotFacture = $nouveauTotFacture;
            $facture->TotalPEC = $totalPEC;
            $facture->TotalfactPatient = $totalfactPatient;
            // Ne pas toucher à TotReglPatient
            $facture->save();

            DB::commit();
            $this->showAddActeForm = false;
            session()->flash('message', 'Acte ajouté avec succès.');
            $this->loadFactures(); // Recharger les factures pour voir les changements

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de l\'ajout de l\'acte : ' . $e->getMessage());
        }
    }

    public function openAddActeForm($factureId)
    {
        $this->factureIdForActe = $factureId;
        $this->resetAddActeForm();
        $this->showAddActeForm = true;
    }

    public function closeAddActeForm()
    {
        $this->showAddActeForm = false;
        $this->resetAddActeForm();
    }

    public function closeReglementForm()
    {
        $this->factureSelectionnee = null;
        $this->montantReglement = null;
        $this->modePaiement = null;
        $this->pourQui = null;
        $this->showReglementModal = false;
    }

    public function setConsultationActe()
    {
        $acte = \App\Models\Acte::where('Acte', 'like', '%consultation%')
            ->orWhere('Acte', 'like', '%CONSULTATION%')
            ->first();

        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->prixReference = $acte->PrixRef;
            $this->prixFacture = $acte->PrixRef;
        }
    }

    public function ouvrirReglementFacture($factureId)
    {
        $this->selectionnerFacture($factureId);
        $this->showReglementModal = true;
    }

    public function removeActe($detailId)
    {
        try {
            DB::beginTransaction();

            // Récupérer le détail de l'acte
            $detail = Detailfacturepatient::find($detailId);
            if (!$detail) {
                throw new \Exception('Acte non trouvé');
            }

            // Récupérer la facture
            $facture = Facture::find($detail->fkidfacture);
            if (!$facture) {
                throw new \Exception('Facture non trouvée');
            }

            // Calculer le montant à soustraire
            $montantActe = $detail->PrixFacture * $detail->Quantite;
            $txpec = $facture->TXPEC ?? 0;
            $montantPEC = $montantActe * $txpec;

            // Mettre à jour les montants de la facture
            $facture->TotFacture = max(0, ($facture->TotFacture ?? 0) - $montantActe);
            $facture->TotalPEC = max(0, ($facture->TotalPEC ?? 0) - $montantPEC);
            $facture->TotalfactPatient = max(0, $facture->TotFacture - $facture->TotalPEC);
            $facture->save();

            // Supprimer le détail de l'acte
            $detail->delete();

            DB::commit();
            session()->flash('message', 'Acte supprimé avec succès.');
            $this->loadFactures(); // Recharger les factures pour voir les changements

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de la suppression de l\'acte : ' . $e->getMessage());
        }
    }

    public function loadFacturesEnAttente()
    {
        $this->facturesEnAttente = Facture::with(['patient', 'medecin'])
            ->where('estfacturer', 0)
            ->orderBy('DtFacture', 'desc')
            ->get();
    }

    public function render()
    {
        $user = auth()->user();
        $isDocteur = ($user->IdClasseUser ?? null) == 2;
        $isDocteurProprietaire = ($user->IdClasseUser ?? null) == 3;

        if (!is_array($this->selectedPatient)) {
            // Logique de gestion des erreurs
        }

        if ($this->factures) {
            // Logique de gestion des logs
        }

        if ($this->factureSelectionnee) {
            // Logique de gestion des logs
        }

        return view('livewire.reglement-facture', [
            'isDocteur' => $isDocteur,
            'isDocteurProprietaire' => $isDocteurProprietaire,
            'facturesEnAttente' => $this->facturesEnAttente ?? Facture::with(['patient', 'medecin'])
                ->where('estfacturer', 0)
                ->orderBy('DtFacture', 'desc')
                ->get()
        ]);
    }
} 