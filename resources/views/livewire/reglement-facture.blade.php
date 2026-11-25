<div class="space-y-6">
    <div class="z-30 rounded-xl bg-primary p-6 text-white shadow-lg">
        <h2 class="text-2xl font-bold">Factures / Devis</h2>
        <p class="mt-1 text-white/80">Gérez les factures, les actes et les règlements du patient.</p>
    </div>

    <div class="bg-white rounded-lg shadow-xl overflow-hidden">
        <div class="p-6">
    <!-- Messages de succès/erreur -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Recherche du patient -->
    @if($selectedPatient)
        <div class="mb-6">
            <div class="rounded border border-primary/20 bg-primary-light p-3">
                <span class="font-medium text-primary">
                    {{ is_array($selectedPatient) ? ($selectedPatient['Prenom'] ?? '') : $selectedPatient->Prenom }}
                </span>
                @if(is_array($selectedPatient) ? ($selectedPatient['Telephone1'] ?? null) : ($selectedPatient->Telephone1 ?? null))
                    <span class="ml-2 text-sm text-gray-600">Tél. {{ is_array($selectedPatient) ? $selectedPatient['Telephone1'] : $selectedPatient->Telephone1 }}</span>
                @endif
            </div>
        </div>
    @else
        <div class="mb-6">
            <livewire:patient-search />
        </div>
    @endif

    <!-- Liste des factures -->
    @if($factures)
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Factures du patient</h2>
            <button type="button" wire:click="openMedecinModal" class="btn-primary">
                <i class="fas fa-plus"></i> Nouvelle facture
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Facture</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médecin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant facturé</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant PEC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total règlements patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reste à payer patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($factures as $facture)
                    @php
                        $isAssure = $facture->ISTP > 0;
                        $resteAPayerPatient = ($isAssure ? ($facture->TotalfactPatient ?? 0) : ($facture->TotFacture ?? 0)) - ($facture->TotReglPatient ?? 0);
                        $resteAPayerPEC = $isAssure ? (($facture->TotalPEC ?? 0) - ($facture->ReglementPEC ?? 0)) : 0;
                    @endphp
                    <tr @if($factureSelectionnee && $factureSelectionnee['id'] == $facture->Idfacture) class="bg-primary-light" @endif
                        wire:key="facture-{{ $facture->Idfacture }}"
                        style="cursor:pointer" 
                        wire:click.stop="selectionnerFacture({{ $facture->Idfacture }})">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $facture->Nfacture }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">Dr. {{ $facture->medecin->Nom ?? 'Non spécifié' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($facture->TotFacture ?? 0, 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $txpec = $facture->TXPEC ?? $selectedPatient['TauxPEC'] ?? 0;
                                $montantPEC = ($facture->ISTP > 0) ? ($facture->TotFacture * $txpec) : 0;
                            @endphp
                            @if($facture->ISTP > 0)
                                {{ number_format($montantPEC, 0, '', ' ') }} MRU
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($facture->TotReglPatient ?? 0, 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format(($facture->TotalfactPatient ?? 0) - ($facture->TotReglPatient ?? 0), 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($resteAPayerPatient > 0)
                                <span class="inline-block px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Non réglée</span>
                            @elseif($resteAPayerPatient < 0)
                                <span class="inline-block px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-semibold">À rembourser</span>
                            @else
                                <span class="inline-block px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Réglée</span>
                            @endif
                        </td>
                    </tr>
                    @if($factureSelectionnee && $factureSelectionnee['id'] == $facture->Idfacture)
                        <tr wire:key="details-{{ $facture->Idfacture }}">
                            <td colspan="8" class="bg-primary-light px-6 py-4">
                                <div class="mb-2 font-semibold text-gray-700">Actes de la facture :</div>
                                <div wire:loading.remove wire:target="selectionnerFacture">
                                    <table class="min-w-full mb-2">
                                        <thead>
                                            <tr class="text-xs text-gray-500 uppercase">
                                                <th class="px-2 py-1 text-left"></th>
                                                <th class="px-2 py-1 text-left">Acte</th>
                                                <th class="px-2 py-1 text-left">Quantité</th>
                                                <th class="px-2 py-1 text-left">Prix unitaire</th>
                                                <th class="px-2 py-1 text-left">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $details = $facture->details;
                                                $totalPrix = 0;
                                            @endphp
                                            @foreach($details as $detail)
                                                @php
                                                    $totalPrix += $detail->PrixFacture * $detail->Quantite;
                                                @endphp
                                                <tr wire:key="detail-{{ $detail->idDetfacture }}">
                                                    <td class="px-2 py-1">
                                                        @if(in_array(Auth::user()->IdClasseUser ?? null, [2, 3]))
                                                            <button type="button" wire:click.stop="removeActe({{ $detail->idDetfacture }})" wire:confirm="Supprimer cet acte de la facture ?" class="rounded p-2 text-red-600 hover:bg-red-50 hover:text-red-800" aria-label="Supprimer {{ $detail->Actes }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                    <td class="px-2 py-1">{{ $detail->Actes }}</td>
                                                    <td class="px-2 py-1">{{ $detail->Quantite }}</td>
                                                    <td class="px-2 py-1">{{ number_format($detail->PrixFacture, 2) }}</td>
                                                    <td class="px-2 py-1">{{ number_format($detail->PrixFacture * $detail->Quantite, 2) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Total&nbsp;:</strong></td>
                                                <td><strong>{{ number_format($totalPrix, 0, '', ' ') }} MRU</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="flex flex-row gap-2 justify-end mt-4">
                                        <button type="button" wire:click.stop="ouvrirReglementFacture({{ $facture->Idfacture }})" class="btn-primary min-w-[120px] justify-center">
                                            Payer
                                        </button>
                                        <button type="button" wire:click.stop="openAddActeForm({{ $facture->Idfacture }})" class="btn-secondary min-w-[150px] justify-center">
                                            <i class="fas fa-plus"></i> Ajouter un acte
                                        </button>
                                        <a href="{{ route('consultations.facture-patient', $facture->Idfacture) }}" target="_blank" class="min-w-[120px] px-4 py-2 text-sm font-semibold bg-gray-700 text-white rounded hover:bg-gray-800 flex items-center justify-center gap-2">
                                            <i class="fas fa-print"></i> Imprimer
                                        </a>
                                    </div>
                                </div>
                                <div wire:loading wire:target="selectionnerFacture" class="text-center py-4">
                                    <span class="spinner spinner-dark" aria-label="Chargement"></span>
                                </div>
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-4" wire:key="pagination">
            {{ $factures->links() }}
        </div>
    </div>
    @endif

    <!-- Modal de règlement de facture -->
    @if($showReglementModal && $factureSelectionnee)
        <x-modal id="payment" title="Régler la facture" :subtitle="'Facture N° '.($factureSelectionnee['numero'] ?? '---')" icon="fa-credit-card" close="closeReglementForm" level="nested" max-width="max-w-2xl">
                    @php
                        $txpec = $factureSelectionnee['TXPEC'] ?? 0;
                        $totalPEC = $factureSelectionnee['montant_pec'] ?? 0;
                        $totalPatient = $factureSelectionnee['part_patient'] ?? 0;
                        $reglPEC = $factureSelectionnee['montant_reglements_pec'] ?? 0;
                        $reglPatient = $factureSelectionnee['montant_reglements_patient'] ?? 0;
                        $restePEC = $totalPEC - $reglPEC;
                        $restePatient = $totalPatient - $reglPatient;
                    @endphp
                    <div class="mb-4 p-4 rounded border border-green-300 bg-green-50 text-green-900">
                        <div class="font-semibold mb-2">Détails de la prise en charge</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <div><strong>Montant facturé :</strong> {{ number_format($factureSelectionnee['montant_total'] ?? 0, 0, '', ' ') }} MRU</div>
                            <div><strong>Part assurance :</strong> {{ number_format($totalPEC, 0, '', ' ') }} MRU</div>
                            <div><strong>Part patient :</strong> {{ number_format($totalPatient, 0, '', ' ') }} MRU</div>
                            <div>
                                <strong>Reste à payer patient :</strong> 
                                <span class="{{ $restePatient < 0 ? 'text-green-600' : '' }}">
                                    {{ number_format($restePatient, 0, '', ' ') }} MRU
                                    @if($restePatient < 0)
                                        (Acompte)
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    @if(($factureSelectionnee['ISTP'] ?? 0) > 0)
                        <fieldset class="mt-4 rounded-lg border border-gray-200 p-4">
                            <legend class="px-1 text-sm font-semibold text-gray-800">Affecter ce règlement à</legend>
                            <div class="mt-2 flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2"><input type="radio" wire:model="pourQui" value="patient"> Patient</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" wire:model="pourQui" value="pec"> Prise en charge (PEC)</label>
                            </div>
                            @error('pourQui') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </fieldset>
                    @endif

                    <!-- Formulaire de règlement -->
                    <form wire:submit="enregistrerReglement">
                        <div class="mt-4">
                            <label for="montantReglement" class="block text-sm font-medium text-gray-700">
                                Montant du paiement
                            </label>
                            <div class="mt-1">
                                <input type="number" step="0.01" wire:model="montantReglement" id="montantReglement"
                                    class="form-input"
                                    placeholder="Entrez un montant positif pour un paiement/acompte, négatif pour un remboursement">
                            </div>
                            @error('montantReglement') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @if(($factureSelectionnee['est_reglee'] ?? false))
                                <p class="mt-2 text-sm text-gray-500">
                                    Cette facture est déjà réglée. Vous pouvez ajouter un nouveau paiement ou un remboursement.
                                </p>
                            @else
                                <p class="mt-2 text-sm text-gray-500">
                                    Montant restant à payer : <span class="font-semibold text-primary">{{ number_format($factureSelectionnee['reste_a_payer'] ?? 0, 2) }} MRU</span>
                                </p>
                            @endif
                        </div>

                        <div class="mt-4">
                            <label for="modePaiement" class="block text-sm font-medium text-gray-700">
                                Mode de paiement
                            </label>
                            <div class="mt-1">
                                <select wire:model="modePaiement" id="modePaiement" required
                                    class="form-select">
                                    <option value="">Sélectionnez un mode de paiement</option>
                                    @foreach($modesPaiement as $mode)
                                        <option value="{{ $mode->idtypepaie }}">{{ $mode->LibPaie }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('modePaiement') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-6 flex justify-end">
                            <div class="flex justify-end mt-4">
                                <button type="button" wire:click="closeReglementForm" class="btn-secondary">
                                    Annuler
                                </button>
                                <button type="submit" class="btn-primary ml-3" wire:loading.attr="disabled" wire:target="enregistrerReglement">
                                    <span wire:loading.remove wire:target="enregistrerReglement">Enregistrer le règlement</span>
                                    <span wire:loading wire:target="enregistrerReglement">Enregistrement…</span>
                                </button>
                            </div>
                        </div>
                    </form>
        </x-modal>
    @endif

    <!-- Formulaire d'ajout d'acte sous la facture sélectionnée -->
    @if($showAddActeForm && $factureIdForActe)
        <x-modal id="add-act" title="Ajouter un acte à la facture" :subtitle="'Facture N° '.($factureSelectionnee['numero'] ?? $factureIdForActe)" icon="fa-plus" close="closeAddActeForm" level="nested" max-width="max-w-2xl">
                        <form wire:submit.prevent="saveActeToFacture" wire:key="form-add-acte-{{ $factureIdForActe }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Acte</label>
                                    <livewire:acte-search :fkidassureur="$selectedPatient['Assureur'] ?? null" :key="'acte-search-'.$factureIdForActe" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence</label>
                                    <input type="number" wire:model="prixReference" class="form-input bg-gray-50" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix facturé</label>
                                    <input type="number" min="0" step="0.01" wire:model="prixFacture" class="form-input">
                                </div>
                                @php
                                    $isAssure = $selectedPatient['Assureur'] ?? 0;
                                    $txpec = $selectedPatient['TauxPEC'] ?? 0;
                                    $prix = $prixFacture ?? 0;
                                    $partPEC = ($isAssure && $txpec > 0) ? $prix * $txpec : 0;
                                    $partPatient = ($isAssure && $txpec > 0) ? $prix * (1 - $txpec) : $prix;
                                @endphp
                                @if($isAssure && $txpec > 0)
                                    <div class="flex gap-4">
                                        <div class="w-1/2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Part PEC (assureur)</label>
                                            <input type="text" value="{{ number_format($partPEC, 0, '', ' ') }} MRU" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 text-gray-900" readonly>
                                        </div>
                                        <div class="w-1/2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Part patient</label>
                                            <input type="text" value="{{ number_format($partPatient, 0, '', ' ') }} MRU" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 text-gray-900" readonly>
                                        </div>
                                    </div>
                                @endif
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité</label>
                                    <input type="number" wire:model="quantite" class="form-input" min="1">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Séance (Dent)</label>
                                    <input type="text" wire:model="seance" class="form-input">
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="button" wire:click="closeAddActeForm" class="btn-secondary">Annuler</button>
                                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveActeToFacture">
                                    Ajouter l’acte
                                </button>
                            </div>
                        </form>
        </x-modal>
    @endif

    <!-- Modal de sélection du médecin -->
    @if($showMedecinModal)
        <x-modal id="doctor-select" title="Sélectionner un médecin" subtitle="Le médecin sera associé à la nouvelle facture." icon="fa-user-doctor" close="$set('showMedecinModal', false)" level="nested" max-width="max-w-lg">
                        <div class="mb-4">
                            <input type="text" wire:model.live.debounce.300ms="searchMedecin"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                                placeholder="Rechercher un médecin...">
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($medecins as $medecin)
                                <button type="button" wire:click="selectMedecin({{ $medecin->idMedecin }})"
                                    class="w-full rounded-md px-4 py-3 text-left transition-colors duration-150 hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-primary">
                                    <div class="font-medium">Dr. {{ $medecin->Nom }}</div>
                                    @if($medecin->Contact)
                                    <div class="text-sm text-gray-500">{{ $medecin->Contact }}</div>
                                    @endif
                                </button>
                                @endforeach
                                @if(count($medecins) === 0)
                                    <div class="text-center py-4 text-gray-500">Aucun médecin trouvé</div>
                                @endif
                            </div>
                        </div>
        </x-modal>
    @endif
</div>

<script>
    window.addEventListener('open-receipt', function(e) {
        if (e.detail && e.detail.url) {
            window.open(e.detail.url, '_blank');
        }
    });
</script>
