<div class="w-full">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header vert uni -->
        <div class="px-6 py-4 bg-green-600">
            <h3 class="text-lg font-semibold text-white">Rendez-vous à venir</h3>
        </div>
        <div class="px-6 py-4 border-b border-green-100 bg-white">
            <form wire:submit.prevent="annulerSelection">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-green-700 mb-1">Médecin</label>
                        <div class="flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-green-200 bg-green-50 text-green-600">
                                <i class="fas fa-user-md"></i>
                            </span>
                            <select class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-lg border border-green-200 focus:ring-green-500 focus:border-green-500 sm:text-sm" wire:model="medecin_id" @if(!$canViewAllRdv) disabled @endif>
                                <option value="">Tous les médecins</option>
                                @foreach($medecins as $medecin)
                                    <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-green-700 mb-1">Date</label>
                        <div class="flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-green-200 bg-green-50 text-green-600">
                                <i class="fas fa-calendar"></i>
                            </span>
                            <input type="date" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-lg border border-green-200 focus:ring-green-500 focus:border-green-500 sm:text-sm" wire:model="date">
                        </div>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-green-700 mb-1">Rechercher un patient</label>
                        <div class="flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-green-200 bg-green-50 text-green-600">
                                <i class="fas fa-search"></i>
                            </span>
                            <select class="w-32 px-3 py-2 border border-green-200 focus:ring-green-500 focus:border-green-500 sm:text-sm" wire:model="searchBy">
                                <option value="name">Nom</option>
                                <option value="nni">NNI</option>
                                <option value="phone">Téléphone</option>
                            </select>
                            <input type="text" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-lg border border-green-200 focus:ring-green-500 focus:border-green-500 sm:text-sm" wire:model.debounce.300ms="searchPatient" placeholder="Rechercher...">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-green-700 mb-1">Options</label>
                        <div class="flex items-center bg-white p-2 rounded-lg shadow-sm border border-green-200">
                            <input type="checkbox" id="showPastRdv" wire:model="showPastRdv" class="h-4 w-4 text-green-600 focus:ring-green-500 border-green-300 rounded">
                            <label for="showPastRdv" class="ml-2 block text-sm text-green-700">
                                <i class="fas fa-history mr-2"></i>
                                Afficher les rendez-vous passés
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto bg-white">
            <table class="min-w-full divide-y divide-green-100">
                <thead class="bg-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heure</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médecin</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acte prévu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-green-100">
                    @forelse($RendezVous as $rdv)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($rdv->DateRdv)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $rdv->HeureRdv ? \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') : '' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-semibold">{{ $rdv->patient->Prenom ?? '' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $rdv->medecin->Nom ?? '' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $rdv->ActePrevu ?? '' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($rdv->rdvConfirmer == 'confirmé')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">confirmé</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">En attente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($rdv->rdvConfirmer != 'confirmé' && $rdv->rdvConfirmer != 'annulé')
                                    <button type="button" wire:click="confirmerRendezVous({{ $rdv->IDRdv }})" class="inline-flex items-center px-2 py-1 mr-1 rounded bg-green-500 text-white text-xs font-semibold hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400">
                                        Confirmer
                                    </button>
                                    <button type="button" wire:click="annulerRendezVous({{ $rdv->IDRdv }})" class="inline-flex items-center px-2 py-1 rounded bg-red-500 text-white text-xs font-semibold hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400">
                                        Annuler
                                    </button>
                                @elseif($rdv->rdvConfirmer == 'annulé')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Annulé</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">Aucun rendez-vous à venir</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination et autres éléments peuvent être ajoutés ici -->
    </div>

    @if(session()->has('message'))
        <div class="mt-4 rounded-lg bg-gradient-to-r from-green-50 to-white p-4 shadow-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ session('message') }}
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex rounded-lg bg-green-50 p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50 transition-all duration-200" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Fermer</span>
                            <i class="fas fa-times h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mt-4 rounded-lg bg-gradient-to-r from-red-50 to-white p-4 shadow-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        {{ session('error') }}
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex rounded-lg bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50 transition-all duration-200" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Fermer</span>
                            <i class="fas fa-times h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>