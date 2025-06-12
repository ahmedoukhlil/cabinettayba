<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Medecin;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Patient;
use App\Models\Facture;
use App\Models\DetailFacturePatient;
use App\Models\CaisseOperation;
use App\Models\Rendezvou;
use App\Models\RefTypePaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConsultationForm extends Component
{
    // Propriétés pour les permissions
    public $isDocteurProprietaire = false;
    public $isSecretaire = false;
    public $isDocteur = false;
    public $canManageRdv = false;
    public $canViewAllRdv = false;

    // Propriétés pour le modal
    public $showReceipt = false;
    public $receiptUrl = '';

    // Propriétés du formulaire
    public $selectedPatient = null;
    public $patient_id = null;
    public $medecin_id = '';
    public $assureur_id = '';
    public $istp = '';
    public $txpec = '';
    public $montant = 0;
    public $mode_paiement = '';
    public $acte_id = '';
    public $acte_nom = '';
    public $date_consultation;
    public $medecins;
    public $search = '';
    public $searchBy = 'telephone';
    public $isSearching = false;

    // Propriétés pour l'assurance
    public $assuranceInfo = null;
    public $montantAssurance = 0;
    public $montantPatient = 0;
    public $tauxPEC = 0;
    public $nomAssureur = '';

    public $patient = null;

    protected $listeners = [
        'patientSelected' => 'handlePatientSelected',
        'patientCleared' => 'handlePatientCleared',
        'refresh' => '$refresh',
        'closeReceiptModal' => 'closeReceiptModal'
    ];

    protected $rules = [
        'selectedPatient' => 'required',
        'medecin_id' => 'required',
        'mode_paiement' => 'required',
        'montant' => 'required|numeric|min:0',
        'txpec' => 'nullable|numeric|min:0|max:100',
    ];

    protected $messages = [
        'selectedPatient.required' => 'Veuillez sélectionner un patient',
        'medecin_id.required' => 'Veuillez sélectionner un médecin',
        'mode_paiement.required' => 'Veuillez sélectionner un mode de paiement',
        'montant.required' => 'Le montant est requis',
        'montant.numeric' => 'Le montant doit être un nombre',
        'montant.min' => 'Le montant doit être supérieur à 0',
        'txpec.numeric' => 'Le taux de prise en charge doit être un nombre',
        'txpec.min' => 'Le taux de prise en charge doit être supérieur à 0',
        'txpec.max' => 'Le taux de prise en charge ne peut pas dépasser 100'
    ];

    public function mount($patient = null)
    {
        $this->initializePermissions();
        $this->initializeForm();
        $this->loadMedecins();
        $this->loadConsultationAct();
        $this->showReceipt = false;
        $this->receiptUrl = '';
        if ($patient) {
            $this->patient = $patient;
            $this->selectedPatient = $patient;
            $this->patient_id = is_array($patient) ? $patient['ID'] : $patient->ID;
        }
    }

    protected function initializePermissions()
    {
        $user = Auth::user();
        $this->isDocteurProprietaire = $user->isDocteurProprietaire();
        $this->isSecretaire = $user->isSecretaire();
        $this->isDocteur = $user->isDocteur() && !$user->isDocteurProprietaire();
        $this->canManageRdv = $this->isDocteurProprietaire || $this->isSecretaire || $this->isDocteur;
        $this->canViewAllRdv = $this->isDocteurProprietaire || $this->isSecretaire;
        
        if ($this->isDocteur) {
            $this->medecin_id = $user->fkidmedecin;
        }
    }

    protected function initializeForm()
    {
        $this->date_consultation = today()->toDateString();
        $this->patients = collect();
        
        // Restaurer l'état depuis la session si disponible
        if ($patientData = session('consultation_patient')) {
            $this->patient_id = $patientData['id'];
            $this->selectedPatient = $patientData['data'];
        }
    }

    protected function loadMedecins()
    {
        // Utiliser le cache pour les médecins
        $this->medecins = cache()->remember('active_medecins', 3600, function() {
            return Medecin::where('Masquer', 0)
                         ->orderBy('Nom')
                         ->get();
        });
    }

    protected function loadConsultationAct()
    {
        // Utiliser le cache pour l'acte de consultation
        $acte = cache()->remember('consultation_act', 3600, function() {
            return Acte::where('Acte', 'like', '%consultation%')
                      ->orWhere('Acte', 'like', '%CONSULTATION%')
                      ->first();
        });
        
        if ($acte) {
            $this->acte_id = $acte->ID;
            $this->acte_nom = $acte->Acte;
            $this->montant = floatval($acte->PrixRef);
        } else {
            throw new \Exception('Aucun acte de consultation trouvé dans la base de données');
        }
    }

    public function handlePatientSelected($patient)
    {
        // \Log::info('Patient reçu dans ConsultationForm', $patient); // Suppression du debug
        $this->patient_id = $patient['ID'];
        $this->selectedPatient = $patient;
        
        // Charger les informations d'assurance si le patient est assuré
        if (isset($patient['Assureur']) && $patient['Assureur'] > 0) {
            $this->tauxPEC = isset($patient['TauxPEC']) ? floatval($patient['TauxPEC']) : 0;
            $this->montantAssurance = ($this->montant * $this->tauxPEC);
            $this->montantPatient = ($this->montant * (1 - $this->tauxPEC));
            $this->nomAssureur = isset($patient['NomAssureur']) ? $patient['NomAssureur'] : '';
            $this->selectedPatient['TauxPEC'] = $this->tauxPEC;
            $this->selectedPatient['NomAssureur'] = $this->nomAssureur;
            $this->selectedPatient['MontantAssurance'] = $this->montantAssurance;
            $this->selectedPatient['MontantPatient'] = $this->montantPatient;
            $this->recalculerMontants();
        } else {
            $this->resetAssuranceInfo();
        }
        
        // Sauvegarder l'état dans la session
        session(['consultation_patient' => [
            'id' => $patient['ID'],
            'data' => $this->selectedPatient
        ]]);

        // Forcer la mise à jour des propriétés
        $this->emit('refresh');
    }

    protected function resetAssuranceInfo()
    {
        $this->assuranceInfo = null;
        $this->montantAssurance = 0;
        $this->montantPatient = $this->montant;
        $this->tauxPEC = 0;
        $this->nomAssureur = '';
    }

    public function handlePatientCleared()
    {
        $this->patient_id = null;
        $this->selectedPatient = null;
        $this->assuranceInfo = null;
        $this->montantAssurance = 0;
        $this->montantPatient = $this->montant;
        $this->tauxPEC = 0;
        $this->nomAssureur = '';
        session()->forget('consultation_patient');
        
        // Empêcher le re-rendu complet du composant
        $this->skipRender();
    }

    public function updatedMedecinId($value)
    {
        $this->skipRender();
    }

    public function updatedModePaiement($value)
    {
        $this->skipRender();
    }

    public function updatedMontant($value)
    {
        // Empêcher la modification du montant
        $this->loadConsultationAct();
        $this->recalculerMontants();
    }

    public function updatedSelectedPatient()
    {
        $this->recalculerMontants();
    }

    public function recalculerMontants()
    {
        $taux = isset($this->selectedPatient['TauxPEC']) ? floatval($this->selectedPatient['TauxPEC']) : 0;
        $this->selectedPatient['MontantAssurance'] = $this->montant * $taux;
        $this->selectedPatient['MontantPatient'] = $this->montant * (1 - $taux);
    }

    public function save()
    {
        try {
            $this->validate();

            if (!$this->selectedPatient) {
                throw new \Exception('Veuillez sélectionner un patient');
            }

            DB::beginTransaction();
            try {
                // Créer la facture
                $facture = $this->createFacture();
                
                // Créer le détail de la facture
                $this->createDetailFacture($facture);
                
                // Créer l'opération de caisse
                $this->createCaisseOperation($facture);

                // Créer le rendez-vous
                $this->createRendezVous($facture);
                
                DB::commit();
                
                // Stocker l'ID de la facture dans la session
                session(['last_facture_id' => $facture->Idfacture]);
                
                // Ajouter un message de succès
                session()->flash('success', 'La consultation a été enregistrée avec succès.');
                
                // Réinitialiser le formulaire
                $this->resetForm();
                
                // Rediriger vers la page de prévisualisation du reçu
                return redirect()->to("/consultations/{$facture->Idfacture}/receipt");
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            $this->addError('general', 'Une erreur est survenue lors de la sauvegarde de la consultation: ' . $e->getMessage());
        }
    }

    protected function createFacture()
    {
        $annee = Carbon::now()->year;
        $derniereFacture = Facture::where('anneeFacture', $annee)
                                ->orderBy('Nfacture', 'desc')
                                ->first();
        
        $numero = $derniereFacture ? intval(explode('-', $derniereFacture->Nfacture)[0]) + 1 : 1;
        $nfacture = $numero . '-' . $annee;
        $nordre = (Facture::where('anneeFacture', $annee)->max('nordre') ?? 0) + 1;

        $montant = floatval($this->montant);
        
        // Récupérer le taux de prise en charge et l'assureur depuis le patient
        $txpec = 0;
        $fkidEtsAssurance = null;
        $totalPEC = 0;
        $totalPatient = $montant;
        
        if (isset($this->selectedPatient['Assureur']) && $this->selectedPatient['Assureur'] > 0) {
            $patient = Patient::find($this->selectedPatient['ID']);

            if ($patient && $patient->Assureur) {
                $fkidEtsAssurance = $patient->Assureur;
                $assureur = Assureur::find($fkidEtsAssurance);

                if ($assureur) {
                    $txpec = floatval($assureur->TauxdePEC);
                    $totalPEC = ($montant * $txpec);
                    $totalPatient = ($montant * (1 - $txpec));
                }
            }
        }

        // Récupérer l'utilisateur connecté depuis la base de données
        $userId = Auth::id();
        $user = DB::table('t_user')->where('Iduser', $userId)->first();

        return Facture::create([
            'Nfacture' => $nfacture,
            'anneeFacture' => $annee,
            'nordre' => $nordre,
            'DtFacture' => Carbon::now(),
            'IDPatient' => $this->selectedPatient['ID'],
            'ISTP' => $this->istp ? intval($this->istp) : 0,
            'fkidEtsAssurance' => $fkidEtsAssurance,
            'TXPEC' => $txpec,
            'TotFacture' => $montant,
            'TotalPEC' => $totalPEC,
            'TotalfactPatient' => $totalPatient,
            'ModeReglement' => $this->mode_paiement,
            'DtReglement' => Carbon::now(),
            'Type' => 'PAYEE',
            'FkidMedecinInitiateur' => $this->medecin_id,
            'fkidCabinet' => Auth::user()->fkidcabinet,
            'estfacturer' => 1,
            'ispayerAssureur' => 0,
            'user' => Auth::user() ? (Auth::user()->NomComplet ?? Auth::user()->name) : null,
            'TotReglPatient' => $totalPatient,
            'ReglementPEC' => 0,
            'PartLaboratoire' => 0,
            'MontantAffectation' => 0,
            'IDRdv' => null // Sera mis à jour après la création du rendez-vous
        ]);
    }

    protected function createDetailFacture($facture)
    {
        return DetailFacturePatient::create([
            'fkidfacture' => $facture->Idfacture,
            'DtAjout' => Carbon::now(),
            'Actes' => $this->acte_nom,
            'PrixFacture' => $this->montant,
            'PrixRef' => $this->montant,
            'Quantite' => 1,
            'fkidMedecin' => $this->medecin_id,
            'fkidacte' => $this->acte_id,
            'IsAct' => 1,
            'fkidcabinet' => Auth::user()->fkidcabinet,
            'ActesArab' => 'NR',
            'user' => Auth::user()->name,
            'TauxPEC' => $facture->TXPEC,
            'MontantPEC' => $facture->TotalPEC,
            'MontantPatient' => $facture->TotalfactPatient,
            'Dents' => 'Cons'
        ]);
    }

    protected function createCaisseOperation($facture)
    {
        $medecin = Medecin::find($this->medecin_id);
        if (!$medecin) {
            throw new \Exception('Médecin non trouvé');
        }

        return CaisseOperation::create([
            'dateoper' => Carbon::now(),
            'MontantOperation' => $this->montant,
            'designation' => 'Consultation N°' . $facture->Nfacture . ' chez Dr. ' . $medecin->Nom,
            'fkidTiers' => $this->selectedPatient['ID'],
            'entreEspece' => $facture->TotalfactPatient, // Montant payé par le patient
            'retraitEspece' => 0,
            'pourPatFournisseur' => 0,
            'pourCabinet' => 1,
            'fkiduser' => Auth::id(),
            'exercice' => Carbon::now()->year,
            'fkIdTypeTiers' => 1,
            'fkidfacturebord' => $facture->Idfacture,
            'DtCr' => Carbon::now(),
            'fkidcabinet' => Auth::user()->fkidcabinet,
            'fkidtypePaie' => 1,
            'TypePAie' => $this->mode_paiement,
            'fkidmedecin' => $this->medecin_id,
            'medecin' => $medecin->Nom,
            'TauxPEC' => $facture->TXPEC,
            'MontantPEC' => $facture->TotalPEC
        ]);
    }

    protected function createRendezVous($facture)
    {
        $date = Carbon::now();
        $ordreRDV = Rendezvou::generateNextOrderNumber($date);
        
        $rendezVous = Rendezvou::create([
            'fkidPatient' => $this->selectedPatient['ID'],
            'fkidMedecin' => $this->medecin_id,
            'dtPrevuRDV' => $date,
            'HeureRdv' => $date->setHour(0)->setMinute(0),
            'ActePrevu' => 'Consultation',
            'rdvConfirmer' => 'Confirmé',
            'DtAjRdv' => now(),
            'user' => Auth::user()->name,
            'fkidcabinet' => Auth::user()->fkidcabinet,
            'OrdreRDV' => $ordreRDV,
            'fkidFacture' => $facture->Idfacture
        ]);

        // Mettre à jour la facture avec l'ID du rendez-vous
        $facture->update(['IDRdv' => $rendezVous->IDRdv]);

        return $rendezVous;
    }

    protected function resetForm()
    {
        $this->reset([
            'patient_id',
            'selectedPatient',
            'medecin_id',
            'mode_paiement',
            'istp',
            'assureur_id'
        ]);
        
        // Nettoyer la session
        session()->forget('consultation_patient');
        
        // Réinitialiser le montant avec l'acte de consultation
        $this->loadConsultationAct();
        
        // Émettre un événement pour réinitialiser le composant de recherche de patient
        $this->emit('patientCleared');
    }

    public function hydrate()
    {
        // Restaurer l'état depuis la session si nécessaire
        if ($this->patient_id && !$this->selectedPatient) {
            if ($patientData = session('consultation_patient')) {
                $this->selectedPatient = $patientData['data'];
                
                // Restaurer les informations d'assurance si le patient est assuré
                if (isset($this->selectedPatient['Assureur']) && $this->selectedPatient['Assureur'] > 0) {
                    $patient = Patient::find($this->selectedPatient['ID']);
                    if ($patient && $patient->Assureur) {
                        $assureur = Assureur::find($patient->Assureur);
                        if ($assureur) {
                            $this->tauxPEC = floatval($assureur->TauxdePEC);
                            $this->montantAssurance = ($this->montant * $this->tauxPEC);
                            $this->montantPatient = ($this->montant * (1 - $this->tauxPEC));
                            $this->nomAssureur = $assureur->LibAssurance;
                            $this->assuranceInfo = [
                                'nom' => $assureur->LibAssurance,
                                'taux' => $this->tauxPEC,
                                'montant' => floatval($this->montant)
                            ];
                        }
                    }
                }
            }
        }
    }

    public function dehydrate()
    {
        // Sauvegarder l'état dans la session si un patient est sélectionné
        if ($this->patient_id && $this->selectedPatient) {
            session(['consultation_patient' => [
                'id' => $this->patient_id,
                'data' => $this->selectedPatient
            ]]);
        }
    }

    public function render()
    {
        return view('livewire.consultation-form', [
            'medecins' => $this->isDocteurProprietaire || $this->isSecretaire 
                ? $this->medecins
                : ($this->isDocteur ? $this->medecins->where('idMedecin', Auth::user()->fkidmedecin) : collect()),
            'assureurs' => cache()->remember('all_assureurs', 3600, function() {
                return Assureur::all();
            }),
            'typesPaiement' => cache()->remember('payment_types', 3600, function() {
                return RefTypePaiement::pluck('LibPaie')->toArray();
            })
        ]);
    }

    public function closeReceiptModal()
    {
        $this->showReceipt = false;
        $this->receiptUrl = '';
    }
} 