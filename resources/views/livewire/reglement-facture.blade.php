<div class="space-y-6">
    <div class="p-6 rounded-xl text-white shadow-lg z-30" style="background: linear-gradient(90deg, #06b6d4 0%, #0e7490 100%);">
        <h2 class="text-2xl font-bold">Facture/DEVIS</h2>
        <p class="text-white mt-1">Sélectionnez un patient pour gérer ses paiements</p>
        <div class="text-xs mt-2">isDocteurProprietaire = {{ var_export($isDocteurProprietaire, true) }}</div>
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
            <div class="p-2 bg-blue-50 rounded border border-blue-200">
                <span class="font-medium text-blue-800">
                    {{ is_array($selectedPatient) ? ($selectedPatient['Prenom'] ?? '') : $selectedPatient->Prenom }}
                </span>
                @if(is_array($selectedPatient) ? ($selectedPatient['Telephone1'] ?? null) : ($selectedPatient->Telephone1 ?? null))
                    <span class="text-sm text-blue-600 ml-2">Tél: {{ is_array($selectedPatient) ? $selectedPatient['Telephone1'] : $selectedPatient->Telephone1 }}</span>
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
            <h3 class="text-lg font-semibold">Factures en attente de règlement</h3>
            <button wire:click="openMedecinModal" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 flex items-center gap-2">
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
                    <tr @if($factureSelectionnee && $factureSelectionnee['id'] == $facture['id']) class="bg-yellow-50" @endif style="cursor:pointer" wire:click="selectionnerFacture({{ $facture['id'] }})">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $facture['numero'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">Dr. {{ $facture['medecin']['Nom'] ?? 'Non spécifié' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($facture['montant_total'], 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $txpec = $facture['TXPEC'] ?? $selectedPatient['TauxPEC'] ?? 0;
                                $montantPEC = (($facture['istp'] ?? $facture['ISTP'] ?? 0) > 0) ? ($facture['montant_total'] * $txpec) : 0;
                            @endphp
                            @if(($facture['istp'] ?? $facture['ISTP'] ?? 0) > 0)
                                {{ number_format($montantPEC, 0, '', ' ') }} MRU
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($facture['montant_reglements_patient'] ?? 0, 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format(($facture['part_patient'] ?? 0) - ($facture['montant_reglements_patient'] ?? 0), 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $reste = ($facture['part_patient'] ?? 0) - ($facture['montant_reglements_patient'] ?? 0);
                            @endphp
                            @if($reste > 0)
                                <span class="inline-block px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Non réglée</span>
                            @elseif($reste < 0)
                                <span class="inline-block px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-semibold">À rembourser</span>
                            @else
                                <span class="inline-block px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Réglée</span>
                            @endif
                        </td>
                    </tr>
                    @if($factureSelectionnee && $factureSelectionnee['id'] == $facture['id'])
                        <tr>
                            <td colspan="8" class="bg-yellow-50 px-6 py-4">
                                <div class="mb-2 font-semibold text-gray-700">Actes de la facture :</div>
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
                                $totalPrix = 0;
                                $details = \App\Models\Detailfacturepatient::where('fkidfacture', $facture['id'])->get();
                            @endphp
                                        @foreach($details as $detail)
                                            @php
                                                $totalPrix += $detail->PrixFacture * $detail->Quantite;
                                            @endphp
                                            <tr>
                                                <td class="px-2 py-1">
                                                    @if(in_array(Auth::user()->IdClasseUser ?? null, [2, 3]))
                                                        <button onclick="event.stopPropagation(); if(confirm('Êtes-vous sûr de vouloir supprimer cet acte ?')) { @this.removeActe({{ $detail->idDetfacture }}) }" class="text-red-600 hover:text-red-800">
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
                                    <button wire:click="ouvrirReglementFacture({{ $facture['id'] }})" class="min-w-[120px] px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded hover:bg-indigo-700 flex items-center justify-center">
                                        Payer
                                    </button>
                                    <button wire:click.stop="openAddActeForm({{ $facture['id'] }})" class="min-w-[150px] px-4 py-2 text-sm font-semibold bg-green-600 text-white rounded hover:bg-green-700 flex items-center justify-center gap-2">
                                        <i class="fas fa-plus"></i> Ajouter un acte
                                    </button>
                                    <a href="{{ route('consultations.facture-patient', $facture['id']) }}" target="_blank" class="min-w-[120px] px-4 py-2 text-sm font-semibold bg-gray-700 text-white rounded hover:bg-gray-800 flex items-center justify-center gap-2">
                                        <i class="fas fa-print"></i> Imprimer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Modal de règlement de facture -->
    @if($showReglementModal && $factureSelectionnee)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <!-- Header vert dégradé -->
                <div class="p-6 rounded-t-lg text-white shadow relative" style="background: linear-gradient(90deg, #06b6d4 0%, #0e7490 100%);">
                    <button wire:click="closeReglementForm" class="absolute top-4 right-4 text-white hover:text-red-200 text-2xl font-bold">&times;</button>
                    <h2 class="text-2xl font-bold">Facture/DEVIS</h2>
                    <p class="text-green-100 mt-1">Facture N° {{ $factureSelectionnee['numero'] }}</p>
                </div>

                <!-- Détails de la facture -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
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

                    <!-- Formulaire de règlement -->
                    <form wire:submit.prevent="enregistrerReglement">
                        <div class="mt-4">
                            <label for="montantReglement" class="block text-sm font-medium text-gray-700">
                                Montant du paiement
                            </label>
                            <div class="mt-1">
                                <input type="number" step="0.01" wire:model="montantReglement" id="montantReglement"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="Entrez un montant positif pour un paiement/acompte, négatif pour un remboursement">
                            </div>
                            @if($factureSelectionnee['est_reglee'])
                                <p class="mt-2 text-sm text-gray-500">
                                    Cette facture est déjà réglée. Vous pouvez ajouter un nouveau paiement ou un remboursement.
                                </p>
                            @else
                                <p class="mt-2 text-sm text-gray-500">
                                    Montant restant à payer : {{ number_format($factureSelectionnee['reste_a_payer'], 2) }} MRU
                                </p>
                            @endif
                        </div>

                        <div class="mt-4">
                            <label for="modePaiement" class="block text-sm font-medium text-gray-700">
                                Mode de paiement
                            </label>
                            <div class="mt-1">
                                <select wire:model="modePaiement" id="modePaiement" required
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">Sélectionnez un mode de paiement</option>
                                    @foreach($modesPaiement as $mode)
                                        <option value="{{ $mode->idtypepaie }}">{{ $mode->LibPaie }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <div class="flex justify-end mt-4">
                                <button type="button" wire:click="closeReglementForm" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Annuler
                                </button>
                                <button type="button" wire:click="enregistrerReglement" class="ml-3 px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Payer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer avec bouton Fermer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg">
                    <button type="button" wire:click="closeReglementForm" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Formulaire d'ajout d'acte sous la facture sélectionnée -->
    @if($showAddActeForm && $factureIdForActe)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <!-- Header vert dégradé -->
                    <div class="p-6 rounded-t-lg text-white shadow" style="background: linear-gradient(90deg, #06b6d4 0%, #0e7490 100%);">
                        <h2 class="text-2xl font-bold">Ajouter un acte à la facture</h2>
                        <p class="text-green-100 mt-1">Facture N° {{ $factures->firstWhere('id', $factureIdForActe)['numero'] ?? '' }}</p>
                    </div>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 rounded-b-lg">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Ajouter un acte à la facture</h3>
                        <form wire:submit.prevent="saveActeToFacture" wire:key="form-add-acte-{{ $factureIdForActe }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Acte</label>
                                    <livewire:acte-search :fkidassureur="$selectedPatient['Assureur'] ?? null" :key="'acte-search-'.$factureIdForActe" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence</label>
                                    <input type="number" wire:model="prixReference" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-gray-50 text-gray-900" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix facturé</label>
                                    <input type="number" wire:model="prixFacture" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
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
                                    <input type="number" wire:model.defer="quantite" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500" min="1">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Séance (Dent)</label>
                                    <input type="text" wire:model.defer="seance" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg">
                        <button type="button" wire:click="closeAddActeForm" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de sélection du médecin -->
    @if($showMedecinModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-primary text-white p-4 rounded-t-lg">
                    <button wire:click="$set('showMedecinModal', false)" class="absolute top-4 right-4 text-white hover:text-red-200 text-2xl font-bold">&times;</button>
                    <h2 class="text-2xl font-bold">Sélectionner un médecin</h2>
                </div>

                <!-- Contenu -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <input type="text" wire:model.debounce.300ms="searchMedecin" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                            placeholder="Rechercher un médecin...">
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($medecins as $medecin)
                            <button wire:click="selectMedecin({{ $medecin->idMedecin }})"
                                class="text-left px-4 py-2 hover:bg-gray-100 rounded-md transition-colors duration-150">
                                <div class="font-medium">Dr. {{ $medecin->Nom }}</div>
                                @if($medecin->Contact)
                                <div class="text-sm text-gray-500">{{ $medecin->Contact }}</div>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    window.addEventListener('open-receipt', function(e) {
        if (e.detail && e.detail.url) {
            window.open(e.detail.url, '_blank');
        }
    });
</script> 