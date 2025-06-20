<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Filtres -->
        <div class="bg-gradient-to-r from-[#1e3a8a] to-[#1e3a8a]/80 px-8 py-6">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-filter mr-3"></i>
                Filtres
            </h2>
        </div>
        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
            <div class="p-8">
                <!-- Indicateur de période active -->
                @if($date_debut || $date_fin)
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
                            <div>
                                <span class="font-semibold text-blue-800">Période sélectionnée :</span>
                                <span class="text-blue-700 ml-2">
                                    @if($date_debut && $date_fin)
                                        Du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                                    @elseif($date_debut)
                                        À partir du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
                                    @elseif($date_fin)
                                        Jusqu'au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="relative">
                        <label class="block text-sm font-semibold text-[#1e3a8a] mb-2">Médecin</label>
                        <select wire:model="medecin_id" class="w-full px-4 py-3 rounded-lg border border-[#1e3a8a] focus:ring-2 focus:ring-[#1e3a8a] focus:border-[#1e3a8a] transition-colors">
                            <option value="">Tous les médecins</option>
                            @foreach($medecins as $medecin)
                                <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold text-[#1e3a8a] mb-2">Date début</label>
                        <input type="date" wire:model="date_debut" class="w-full px-4 py-3 rounded-lg border border-[#1e3a8a] focus:ring-2 focus:ring-[#1e3a8a] focus:border-[#1e3a8a] transition-colors">
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold text-[#1e3a8a] mb-2">Date fin</label>
                        <input type="date" wire:model="date_fin" class="w-full px-4 py-3 rounded-lg border border-[#1e3a8a] focus:ring-2 focus:ring-[#1e3a8a] focus:border-[#1e3a8a] transition-colors">
                    </div>
                    <div class="flex items-end gap-2">
                        <button wire:click="resetFilters" class="w-full px-4 py-3 bg-[#1e3a8a] text-white rounded-lg hover:bg-[#1e3a8a]/80 focus:outline-none focus:ring-2 focus:ring-[#1e3a8a] focus:ring-offset-2 transition-colors">
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Affichage des statistiques (reprendre la structure de caisse-operations-manager) -->
        @if($operations->count() > 0)
            <!-- Liste des opérations -->
            <div class="bg-gradient-to-r from-[#1e3a8a] to-[#1e3a8a]/80 px-8 py-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-list mr-3"></i>
                    Liste des opérations
                </h2>
            </div>
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
                <div class="p-8">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#1e3a8a]/20">
                            <thead class="bg-[#1e3a8a]/5">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#1e3a8a] uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#1e3a8a] uppercase tracking-wider">Médecin</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#1e3a8a] uppercase tracking-wider">Patient</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#1e3a8a] uppercase tracking-wider">Opération</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#1e3a8a] uppercase tracking-wider">Montant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-[#1e3a8a] uppercase tracking-wider">Moyen de paiement</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-[#1e3a8a]/20">
                                @foreach($operations as $operation)
                                    <tr class="hover:bg-[#1e3a8a]/5 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1e3a8a]">
                                            {{ \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1e3a8a]">
                                            {{ $operation->medecin->Nom ?? ($medecins->firstWhere('idMedecin', $operation->fkidmedecin)->Nom ?? 'N/A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1e3a8a]">
                                            {{ $operation->tiers->Nom ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1e3a8a]">
                                            {{ $operation->designation }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $operation->entreEspece ? 'text-[#1e3a8a]' : 'text-red-600' }}">
                                            {{ number_format($operation->MontantOperation, 0, ',', ' ') }} MRU
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-[#1e3a8a]">
                                            {{ $operation->TypePAie ?? 'CASH' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-[#1e3a8a]/20">
                        {{ $operations->links() }}
                    </div>
                </div>
            </div>
            <!-- Totaux généraux -->
            <div class="bg-gradient-to-r from-[#1e3a8a] to-[#1e3a8a]/80 px-8 py-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-chart-line mr-3"></i>
                    Totaux généraux
                    @if($date_debut || $date_fin)
                        <span class="text-sm font-normal text-blue-100 ml-3">
                            ({{ $operations->total() }} opérations)
                        </span>
                    @endif
                </h2>
            </div>
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-[#1e3a8a]/10 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <dt class="text-sm font-semibold text-[#1e3a8a] mb-2">Total des recettes</dt>
                            <dd class="text-3xl font-bold text-[#1e3a8a]">{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</dd>
                        </div>
                        <div class="bg-gradient-to-br from-[#1e3a8a]/10 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <dt class="text-sm font-semibold text-[#1e3a8a] mb-2">Total des dépenses</dt>
                            <dd class="text-3xl font-bold text-[#1e3a8a]">{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</dd>
                        </div>
                        <div class="bg-gradient-to-br from-[#1e3a8a]/10 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <dt class="text-sm font-semibold text-[#1e3a8a] mb-2">Bilan</dt>
                            <dd class="text-3xl font-bold {{ $solde >= 0 ? 'text-[#1e3a8a]' : 'text-red-600' }}">
                                {{ number_format($solde, 0, ',', ' ') }} MRU
                            </dd>
                        </div>
                    </div>

                    <!-- Ventilation des totaux généraux par mode de paiement -->
                    @if(count($totauxGenerauxParMoyenPaiement) > 0)
                        <div class="mt-8">
                            <h3 class="text-lg font-bold text-[#1e3a8a] mb-6">Ventilation par mode de paiement</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($totauxGenerauxParMoyenPaiement as $type => $totaux)
                                    <div class="bg-white border border-[#1e3a8a]/20 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="text-lg font-bold text-[#1e3a8a]">{{ $type }}</div>
                                        </div>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-[#1e3a8a]">Recettes</span>
                                                <span class="text-lg font-semibold text-[#1e3a8a]">{{ number_format($totaux['recettes'], 0, ',', ' ') }} MRU</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-[#1e3a8a]">Dépenses</span>
                                                <span class="text-lg font-semibold text-red-600">{{ number_format($totaux['depenses'], 0, ',', ' ') }} MRU</span>
                                            </div>
                                            <div class="flex justify-between items-center pt-3 border-t border-[#1e3a8a]/20">
                                                <span class="text-sm font-semibold text-[#1e3a8a]">Solde</span>
                                                <span class="text-lg font-semibold {{ $totaux['solde'] >= 0 ? 'text-[#1e3a8a]' : 'text-red-600' }}">
                                                    {{ number_format($totaux['solde'], 0, ',', ' ') }} MRU
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Totaux par médecin -->
            @if(count($totauxParMedecin) > 0)
                <div class="bg-gradient-to-r from-[#1e3a8a] to-[#1e3a8a]/80 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-user-md mr-3"></i>
                        Totaux par médecin
                    </h2>
                </div>
                <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
                    <div class="p-8">
                        <div class="space-y-8">
                            @foreach($totauxParMedecin as $medecinId => $totaux)
                                <div class="bg-white border border-[#1e3a8a]/20 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                                    <h3 class="text-xl font-bold text-[#1e3a8a] mb-4">Dr. {{ $totaux['nom'] }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div class="bg-gradient-to-br from-[#1e3a8a]/10 to-white rounded-xl p-4 shadow-sm">
                                            <dt class="text-sm font-semibold text-[#1e3a8a] mb-2">Recettes</dt>
                                            <dd class="text-2xl font-bold text-[#1e3a8a]">{{ number_format($totaux['recettes'], 0, ',', ' ') }} MRU</dd>
                                        </div>
                                        <div class="bg-gradient-to-br from-[#1e3a8a]/10 to-white rounded-xl p-4 shadow-sm">
                                            <dt class="text-sm font-semibold text-[#1e3a8a] mb-2">Dépenses</dt>
                                            <dd class="text-2xl font-bold text-red-600">{{ number_format($totaux['depenses'], 0, ',', ' ') }} MRU</dd>
                                        </div>
                                        <div class="bg-gradient-to-br from-[#1e3a8a]/10 to-white rounded-xl p-4 shadow-sm">
                                            <dt class="text-sm font-semibold text-[#1e3a8a] mb-2">Solde</dt>
                                            <dd class="text-2xl font-bold {{ $totaux['solde'] >= 0 ? 'text-[#1e3a8a]' : 'text-red-600' }}">
                                                {{ number_format($totaux['solde'], 0, ',', ' ') }} MRU
                                            </dd>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl">
                <div class="p-8 text-center">
                    <div class="text-[#1e3a8a]">
                        <i class="fas fa-info-circle text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Aucune opération trouvée</p>
                        <p class="mt-2">Veuillez ajuster vos filtres pour voir les opérations.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div> 