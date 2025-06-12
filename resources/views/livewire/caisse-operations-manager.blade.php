<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Filtres -->
        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
            <div class="bg-gradient-to-r from-green-600 to-green-500 px-8 py-6">
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-filter mr-3"></i>
                    Filtres
                </h2>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    @if($isDocteurProprietaire)
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Médecin</label>
                            <select wire:model="medecin_id" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                                <option value="">Tous les médecins</option>
                                @foreach($medecins as $medecin)
                                    <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                            <input type="date" wire:model="date_debut" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        </div>
                    @elseif($isDocteur)
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Médecin</label>
                            <input type="text" value="{{ Auth::user()->NomComplet ?? Auth::user()->name ?? '' }}" class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 cursor-not-allowed" disabled>
                        </div>
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                            <input type="date" wire:model="date_debut" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        </div>
                    @elseif($isSecretaire)
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date du jour</label>
                            <input type="date" value="{{ now()->toDateString() }}" class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 cursor-not-allowed" disabled>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($operations->count() > 0)
            <!-- Totaux généraux -->
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
                <div class="bg-gradient-to-r from-green-600 to-green-500 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-chart-line mr-3"></i>
                        Totaux généraux
                    </h2>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <dt class="text-sm font-semibold text-gray-600 mb-2">Total des recettes</dt>
                            <dd class="text-3xl font-bold text-gray-900">{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</dd>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <dt class="text-sm font-semibold text-gray-600 mb-2">Total des dépenses</dt>
                            <dd class="text-3xl font-bold text-gray-900">{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</dd>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                            <dt class="text-sm font-semibold text-gray-600 mb-2">Bilan</dt>
                            <dd class="text-3xl font-bold {{ $solde >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($solde, 0, ',', ' ') }} MRU
                            </dd>
                        </div>
                    </div>

                    <!-- Ventilation des totaux généraux par mode de paiement -->
                    <div class="mt-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-6">Ventilation par mode de paiement</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($totauxGenerauxParMoyenPaiement as $type => $totaux)
                                <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="text-lg font-bold text-green-700">{{ $type }}</div>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Recettes</span>
                                            <span class="text-lg font-semibold text-green-600">{{ number_format($totaux['recettes'], 0, ',', ' ') }} MRU</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Dépenses</span>
                                            <span class="text-lg font-semibold text-red-600">{{ number_format($totaux['depenses'], 0, ',', ' ') }} MRU</span>
                                        </div>
                                        <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                            <span class="text-sm font-semibold text-gray-700">Solde</span>
                                            <span class="text-lg font-semibold {{ $totaux['solde'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ number_format($totaux['solde'], 0, ',', ' ') }} MRU
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totaux par médecin -->
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl mb-8">
                <div class="bg-gradient-to-r from-green-600 to-green-500 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-user-md mr-3"></i>
                        Totaux par médecin
                    </h2>
                </div>
                <div class="p-8">
                    <div class="space-y-8">
                        @foreach($totauxParMedecin as $medecinId => $totaux)
                            <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                                <h3 class="text-xl font-bold text-gray-800 mb-4">Dr. {{ $totaux['nom'] }}</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-4 shadow-sm">
                                        <dt class="text-sm font-semibold text-gray-600 mb-2">Recettes</dt>
                                        <dd class="text-2xl font-bold text-green-600">{{ number_format($totaux['recettes'], 0, ',', ' ') }} MRU</dd>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-4 shadow-sm">
                                        <dt class="text-sm font-semibold text-gray-600 mb-2">Dépenses</dt>
                                        <dd class="text-2xl font-bold text-red-600">{{ number_format($totaux['depenses'], 0, ',', ' ') }} MRU</dd>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-4 shadow-sm">
                                        <dt class="text-sm font-semibold text-gray-600 mb-2">Solde</dt>
                                        <dd class="text-2xl font-bold {{ $totaux['solde'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($totaux['solde'], 0, ',', ' ') }} MRU
                                        </dd>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Liste des opérations -->
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl">
                <div class="bg-gradient-to-r from-green-600 to-green-500 px-8 py-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <i class="fas fa-list mr-3"></i>
                        Liste des opérations
                    </h2>
                </div>
                <div class="p-8">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médecin</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opération</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moyen de paiement</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($operations as $operation)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $operation->medecin->Nom ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $operation->tiers->Nom ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $operation->designation }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $operation->entreEspece ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($operation->MontantOperation, 0, ',', ' ') }} MRU
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $operation->TypePAie ?? 'CASH' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $operations->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl">
                <div class="p-8 text-center">
                    <div class="text-gray-500">
                        <i class="fas fa-info-circle text-4xl mb-4"></i>
                        <p class="text-lg font-medium">Aucune opération trouvée</p>
                        <p class="mt-2">Veuillez ajuster vos filtres pour voir les opérations.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div> 