<div class="w-full space-y-6 p-4">
    <!-- En-tête avec recherche et boutons -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
        <div class="flex-1 w-full max-w-lg">
            <div class="relative">
                <label for="patient-search" class="sr-only">Rechercher un patient</label>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-0">
                    <select wire:model.live="searchBy" aria-label="Critère de recherche" class="rounded-lg sm:rounded-r-none border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Tout</option>
                        <option value="name">Nom/Prénom</option>
                        <option value="nni">NNI</option>
                        <option value="phone">Téléphone</option>
                    </select>
                    <input id="patient-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un patient..." class="flex-1 pl-4 pr-4 py-2 rounded-lg sm:rounded-l-none border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 w-full md:w-auto">
            <label class="flex items-center space-x-2 text-sm text-gray-600">
                <input type="checkbox" wire:model.live="showInactive" class="form-checkbox h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                <span>Afficher les patients inactifs</span>
            </label>
            <button type="button" wire:click="openModal" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus" aria-hidden="true"></i> Nouveau patient
            </button>
        </div>
    </div>

    <!-- Messages de notification -->
    @if (session()->has('message'))
        <div class="rounded-lg bg-green-50 p-4" role="status" aria-live="polite">
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
        <div class="rounded-lg bg-red-50 p-4" role="alert" aria-live="assertive">
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
    <div class="bg-white rounded-lg shadow w-full">
        <div class="space-y-3 p-3 md:hidden">
            @forelse($patients as $patient)
                @php
                    $mobileFirstName = trim($patient->Prenom ?? '');
                    $mobileLastName = trim($patient->Nom ?? '');
                    $mobileDisplayName = $mobileFirstName !== '' && mb_strtolower($mobileFirstName) === mb_strtolower($mobileLastName)
                        ? $mobileFirstName
                        : trim($mobileFirstName . ' ' . $mobileLastName);
                    $mobileInsurerName = trim($patient->assureur->LibAssurance ?? '');
                    $mobileHasInsurance = !empty($patient->IdentifiantAssurance)
                        && $patient->assureur
                        && !str_contains(mb_strtolower($mobileInsurerName), 'sans assurance');
                @endphp
                <article class="rounded-xl border border-gray-200 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate font-bold text-gray-900">{{ $mobileDisplayName ?: 'Patient sans nom' }}</h3>
                            <p class="mt-1 text-xs text-gray-500">N° fiche {{ $patient->IdentifiantPatient ?: '—' }}</p>
                        </div>
                        <span class="rounded-full px-2 py-1 text-xs font-bold {{ in_array($patient->Genre, ['H','M']) ? 'bg-blue-100 text-blue-800' : ($patient->Genre === 'F' ? 'bg-pink-100 text-pink-800' : 'bg-gray-100 text-gray-500') }}">
                            {{ in_array($patient->Genre, ['H','M']) ? 'H' : ($patient->Genre === 'F' ? 'F' : '—') }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase text-gray-400">Téléphone</p>
                            <a href="tel:{{ $patient->Telephone1 }}" class="mt-1 inline-flex min-h-11 items-center font-semibold text-primary">{{ $patient->Telephone1 ?: '—' }}</a>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-gray-400">Assurance</p>
                            <p class="mt-2 text-gray-700">{{ $mobileHasInsurance ? $mobileInsurerName : 'Non assuré' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 border-t border-gray-100 pt-3">
                        <button type="button" wire:click="showPaymentHistory({{ $patient->ID }})" class="btn-secondary min-h-12 justify-center px-2" aria-label="Paiements de {{ $mobileDisplayName }}">
                            <i class="fas fa-money-bill-wave" aria-hidden="true"></i><span class="sr-only">Paiements</span>
                        </button>
                        <button type="button" wire:click="toggleStatus({{ $patient->ID }})" class="btn-secondary min-h-12 justify-center px-2" aria-label="{{ $patient->choix ? 'Activer' : 'Désactiver' }} ce patient">
                            <i class="fas fa-{{ $patient->choix ? 'check' : 'times' }}" aria-hidden="true"></i><span class="sr-only">Statut</span>
                        </button>
                        <button type="button" wire:click="edit({{ $patient->ID }})" class="btn-secondary min-h-12 justify-center px-2" aria-label="Modifier {{ $mobileDisplayName }}">
                            <i class="fas fa-edit" aria-hidden="true"></i><span class="sr-only">Modifier</span>
                        </button>
                    </div>
                </article>
            @empty
                <p class="py-10 text-center text-sm text-gray-500">Aucun patient trouvé</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[760px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 w-1/12 text-center">N° Fiche</th>
                        <th scope="col" class="px-3 py-2 w-1/4">Patient</th>
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
                            @php
                                $firstName = trim($patient->Prenom ?? '');
                                $lastName = trim($patient->Nom ?? '');
                                $displayName = $firstName !== '' && mb_strtolower($firstName) === mb_strtolower($lastName)
                                    ? $firstName
                                    : trim($firstName . ' ' . $lastName);
                                $insurerName = trim($patient->assureur->LibAssurance ?? '');
                                $hasRealInsurance = !empty($patient->IdentifiantAssurance)
                                    && $patient->assureur
                                    && !str_contains(mb_strtolower($insurerName), 'sans assurance');
                            @endphp
                            <td class="px-3 py-2 w-1/4 font-medium text-gray-900">{{ $displayName ?: 'Patient sans nom' }}</td>
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
                                @if($hasRealInsurance)
                                    <div class="space-y-1">
                                        <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 border border-green-200 shadow-sm">
                                            {{ $insurerName }}
                                        </span>
                                        <div class="text-xs text-gray-500">
                                            Taux PEC: {{ number_format($patient->assureur->TauxdePEC * 100, 2) }}%
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
                                    <button type="button" wire:click="showPaymentHistory({{ $patient->ID }})" class="rounded-md p-2 text-primary hover:bg-primary-light hover:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary" title="Historique des paiements" aria-label="Afficher l'historique des paiements de {{ $displayName }}">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                    <button type="button" wire:click="toggleStatus({{ $patient->ID }})" class="p-2 rounded-md text-{{ $patient->choix ? 'green' : 'red' }}-600 hover:text-{{ $patient->choix ? 'green' : 'red' }}-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="{{ $patient->choix ? 'Activer' : 'Désactiver' }}" aria-label="{{ $patient->choix ? 'Activer' : 'Désactiver' }} ce patient">
                                        <i class="fas fa-{{ $patient->choix ? 'check' : 'times' }}"></i>
                                    </button>
                                    <button type="button" wire:click="edit({{ $patient->ID }})" class="rounded-md p-2 text-primary hover:bg-primary-light hover:text-primary-dark focus:outline-none focus:ring-2 focus:ring-primary" title="Modifier" aria-label="Modifier ce patient">
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
        <x-modal id="patient-form" :title="$patientId ? 'Modifier le patient' : 'Nouveau patient'" icon="fa-user" close="closeModal" level="nested" max-width="max-w-3xl" body-class="modal-body !p-0">
                <form wire:submit="save">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="space-y-4">
                            <!-- Informations personnelles -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Informations personnelles</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="identifiantPatient" class="block text-sm font-medium text-gray-700">N° Fiche</label>
                                        <input type="number" wire:model="identifiantPatient" id="identifiantPatient" class="form-input" @if(!$patientId) readonly @endif>
                                        @error('identifiantPatient') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        @if(!$patientId)
                                            <p class="text-xs text-gray-500">Le N° Fiche est généré automatiquement à la création.</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom *</label>
                                        <input type="text" wire:model="nom" id="nom" class="form-input" placeholder="Entrez le nom du patient">
                                        @error('nom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
                                    <input type="text" wire:model="prenom" id="prenom" autocomplete="given-name" class="form-input" placeholder="Entrez le prénom du patient">
                                    @error('prenom') <span class="text-red-500 text-xs" role="alert">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="nni" class="block text-sm font-medium text-gray-700">NNI</label>
                                        <input type="text" wire:model="nni" id="nni" class="form-input" placeholder="Numéro d'identité national">
                                        @error('nni') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="genre" class="block text-sm font-medium text-gray-700">Genre</label>
                                        <select wire:model="genre" id="genre" class="form-select">
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
                                        <input type="date" wire:model="dateNaissance" id="dateNaissance" max="{{ now()->toDateString() }}" class="form-input">
                                        @error('dateNaissance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="telephone1" class="block text-sm font-medium text-gray-700">Téléphone principal *</label>
                                        <input type="tel" wire:model="telephone1" id="telephone1" class="form-input" placeholder="Ex: +222 12345678">
                                        @error('telephone1') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="telephone2" class="block text-sm font-medium text-gray-700">Téléphone secondaire</label>
                                        <input type="tel" wire:model="telephone2" id="telephone2" class="form-input" placeholder="Ex: +222 12345678">
                                        @error('telephone2') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="adresse" class="block text-sm font-medium text-gray-700">Adresse</label>
                                        <textarea wire:model="adresse" id="adresse" rows="2" class="form-textarea" placeholder="Adresse complète du patient"></textarea>
                                        @error('adresse') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" wire:model.live="isAssured" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Patient assuré</span>
                                    </label>
                                </div>

                                @if($isAssured)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="assureur" class="block text-sm font-medium text-gray-700">Assureur *</label>
                                        <select wire:model="assureur" id="assureur" class="form-select">
                                            <option value="">Sélectionner un assureur</option>
                                            @foreach($assureurs as $assureur)
                                                <option value="{{ $assureur->IDAssureur }}">{{ $assureur->LibAssurance }}</option>
                                            @endforeach
                                        </select>
                                        @error('assureur') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="identifiantAssurance" class="block text-sm font-medium text-gray-700">Identifiant Assurance *</label>
                                        <input type="text" wire:model="identifiantAssurance" id="identifiantAssurance" class="form-input" placeholder="Numéro d'assuré">
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
                                        <input type="checkbox" wire:model="isActive" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Patient actif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                         <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary sm:ml-3">
                            <span wire:loading.remove wire:target="save">Enregistrer</span>
                            <span wire:loading wire:target="save">Enregistrement…</span>
                        </button>
                         <button type="button" wire:click="closeModal" class="btn-secondary mt-3 sm:mt-0 sm:ml-3">
                            Annuler
                        </button>
                    </div>
                </form>
        </x-modal>
    @endif

    <!-- Modal d'historique des paiements -->
    @if($showPaymentHistoryModal)
        <x-modal id="payment-history" title="Historique des paiements" icon="fa-money-bill-wave" close="closePaymentHistoryModal" level="nested" max-width="max-w-4xl">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
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
        </x-modal>
    @endif
</div>
