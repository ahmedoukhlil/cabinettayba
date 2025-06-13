<div class="space-y-6 max-w-3xl mx-auto p-4">
    <!-- En-tête avec recherche et boutons -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
        <div class="flex-1 max-w-lg">
            <div class="relative">
                <div class="flex space-x-2">
                    <select wire:model="searchBy" class="rounded-l-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Tout</option>
                        <option value="name">Nom/Prénom</option>
                        <option value="nni">NNI</option>
                        <option value="phone">Téléphone</option>
                    </select>
                    <input type="text" wire:model.debounce.300ms="search" placeholder="Rechercher un patient..." class="flex-1 pl-4 pr-4 py-2 rounded-r-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="showInactive" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span>Afficher les patients inactifs</span>
            </label>
            <button wire:click="openModal" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                Nouveau Patient
            </button>
        </div>
    </div>

    <!-- Messages de notification -->
    @if (session()->has('message'))
        <div class="rounded-lg bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Tableau des patients -->
    <div class="bg-white rounded-lg shadow overflow-hidden w-full">
        <div>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 w-1/12 text-center">N° Fiche</th>
                        <th scope="col" class="px-3 py-2 w-1/4">Nom</th>
                        <th scope="col" class="px-3 py-2 w-1/6">Téléphone</th>
                        <th scope="col" class="px-3 py-2 w-1/12 text-center">Genre</th>
                        <th scope="col" class="px-3 py-2 w-1/4">Assurance</th>
                        <th scope="col" class="px-3 py-2 w-1/12 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($patients as $patient)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 w-1/12 text-center">{{ $patient->IdentifiantPatient }}</td>
                            <td class="px-3 py-2 w-1/4">{{ $patient->Prenom }}</td>
                            <td class="px-3 py-2 w-1/6">{{ $patient->Telephone1 }}@if($patient->Telephone2)<div class="text-xs text-gray-500">{{ $patient->Telephone2 }}</div>@endif</td>
                            <td class="px-3 py-2 w-1/12 text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ in_array($patient->Genre, ['H','M']) ? 'bg-blue-100 text-blue-800' : ($patient->Genre === 'F' ? 'bg-pink-100 text-pink-800' : 'bg-gray-100 text-gray-500') }}">
                                    @if(in_array($patient->Genre, ['H','M']))
                                        H
                                    @elseif($patient->Genre === 'F')
                                        F
                                    @else
                                        -
                                    @endif
                                </span>
                            </td>
                            <td class="px-3 py-2 w-1/4">
                                @if($patient->Assureur > 0 && $patient->assureur)
                                    <div class="space-y-1">
                                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200 shadow-sm">
                                            {{ $patient->assureur->LibAssurance }}
                                        </span>
                                        <div class="text-xs text-gray-500">
                                            Taux PEC: {{ number_format($patient->assureur->TauxdePEC, 2) }}%
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-500 border border-gray-200 italic">
                                        Non assuré
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 w-1/12 text-center">
                                <div class="flex items-center justify-center space-x-3">
                                    <button wire:click="showPaymentHistory({{ $patient->ID }})" class="text-green-600 hover:text-green-900" title="Historique des paiements">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                    <button wire:click="toggleStatus({{ $patient->ID }})" class="text-{{ $patient->choix ? 'green' : 'red' }}-600 hover:text-{{ $patient->choix ? 'green' : 'red' }}-900" title="{{ $patient->choix ? 'Activer' : 'Désactiver' }}">
                                        <i class="fas fa-{{ $patient->choix ? 'check' : 'times' }}"></i>
                                    </button>
                                    <button wire:click="edit({{ $patient->ID }})" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 text-center">
                                Aucun patient trouvé
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $patients->links() }}
        </div>
    </div>

    <!-- Modal de création/édition -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-stretch justify-center min-h-screen px-0 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl border border-gray-300 transform transition-all sm:my-8 sm:align-middle max-w-lg w-full">
                <form wire:submit.prevent="save">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ $patientId ? 'Modifier le patient' : 'Nouveau patient' }}
                            </h3>
                        </div>
                        <div class="space-y-4">
                            <!-- Informations personnelles -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Informations personnelles</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="identifiantPatient" class="block text-sm font-medium text-gray-700">N° Fiche</label>
                                        <input type="number" wire:model.defer="identifiantPatient" id="identifiantPatient" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" @if(!$patientId) disabled @endif>
                                        @error('identifiantPatient') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        @if(!$patientId)
                                            <p class="text-xs text-gray-500">Le N° Fiche est généré automatiquement à la création.</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom *</label>
                                        <input type="text" wire:model.defer="nom" id="nom" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" placeholder="Entrez le nom du patient">
                                        @error('nom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="nni" class="block text-sm font-medium text-gray-700">NNI</label>
                                        <input type="text" wire:model.defer="nni" id="nni" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" placeholder="Numéro d'identité national">
                                        @error('nni') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="genre" class="block text-sm font-medium text-gray-700">Genre</label>
                                        <select wire:model.defer="genre" id="genre" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500">
                                            <option value="">Sélectionner</option>
                                            <option value="H">Homme (H)</option>
                                            <option value="F">Femme (F)</option>
                                        </select>
                                        @error('genre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="dateNaissance" class="block text-sm font-medium text-gray-700">Date de naissance</label>
                                        <input type="date" wire:model.defer="dateNaissance" id="dateNaissance" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500">
                                        @error('dateNaissance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="telephone1" class="block text-sm font-medium text-gray-700">Téléphone principal *</label>
                                        <input type="tel" wire:model.defer="telephone1" id="telephone1" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" placeholder="Ex: +222 12345678">
                                        @error('telephone1') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="telephone2" class="block text-sm font-medium text-gray-700">Téléphone secondaire</label>
                                        <input type="tel" wire:model.defer="telephone2" id="telephone2" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" placeholder="Ex: +222 12345678">
                                        @error('telephone2') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="adresse" class="block text-sm font-medium text-gray-700">Adresse</label>
                                        <textarea wire:model.defer="adresse" id="adresse" rows="2" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" placeholder="Adresse complète du patient"></textarea>
                                        @error('adresse') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" wire:model="isAssured" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Patient assuré</span>
                                    </label>
                                </div>

                                @if($isAssured)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="assureur" class="block text-sm font-medium text-gray-700">Assureur *</label>
                                        <select wire:model="assureur" id="assureur" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500">
                                            <option value="">Sélectionner un assureur</option>
                                            @foreach($assureurs as $assureur)
                                                <option value="{{ $assureur->IDAssureur }}">{{ $assureur->LibAssurance }}</option>
                                            @endforeach
                                        </select>
                                        @error('assureur') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="identifiantAssurance" class="block text-sm font-medium text-gray-700">Identifiant Assurance *</label>
                                        <input type="text" wire:model="identifiantAssurance" id="identifiantAssurance" class="mt-1 block w-full rounded-md border-2 border-blue-400 shadow-sm focus:border-blue-600 focus:ring-blue-500" placeholder="Numéro d'assuré">
                                        @error('identifiantAssurance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Statut du patient -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Statut du patient</h4>
                                <div>
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" wire:model.defer="isActive" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Patient actif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Enregistrer
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal d'historique des paiements -->
    @if($showPaymentHistoryModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Historique des paiements
                            </h3>
                            
                            @if($paymentHistory->isEmpty())
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle text-gray-400 text-4xl mb-2"></i>
                                    <p class="text-gray-500">Aucun paiement enregistré pour ce patient</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médecin</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($paymentHistory as $payment)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ \Carbon\Carbon::parse($payment->dateoper)->format('d/m/Y H:i') }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        {{ $payment->designation }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ number_format($payment->MontantOperation, 0, ',', ' ') }} MRU
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $payment->entreEspece ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ $payment->entreEspece ? 'Entrée' : 'Sortie' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $payment->medecin }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="closePaymentHistoryModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div> 