<div>
    <div class="flex">
        <select wire:model.live="searchBy" class="rounded-l-md border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
            <option value="telephone" selected>Téléphone</option>
            <option value="nom">Nom</option>
            <option value="identifiant">N° Fiche</option>
        </select>
        <div class="relative flex-1">
            <div class="flex">
                <input type="text" 
                       class="form-control" 
                       placeholder="Rechercher un patient..."
                       wire:model.debounce.1000ms="search"
                       wire:loading.attr="disabled"
                       wire:target="search">
            </div>
            @if($selectedPatient)
                <button type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        wire:click="clearPatient">
                    <i class="fas fa-times"></i>
                </button>
            @endif
            @if($isSearching)
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            @endif
        </div>
    </div>

    <!-- Patient sélectionné -->
    @if($selectedPatient)
        <div class="mt-2 p-2 bg-primary-light rounded border border-primary">
            <div class="flex items-center justify-between">
                <span class="font-medium text-primary">{{ $selectedPatient['Prenom'] }}</span>
                <span class="text-sm text-primary ml-2">Tél: {{ $selectedPatient['Telephone1'] }}</span>
            </div>
        </div>
    @endif

    <!-- Résultats de recherche -->
    @if(strlen(trim($search)) >= 1)
        <div class="fixed z-50 mt-1 w-full max-w-2xl bg-white shadow-lg rounded-md border border-gray-200 max-h-60 overflow-y-auto">
            @if($isSearching)
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    <div class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5 text-primary mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Recherche en cours...
                    </div>
                </div>
            @else
                @if(!empty($patients))
                    <div class="px-3 py-2 text-xs text-gray-500 border-b border-gray-100">
                        {{ count($patients) }} résultat(s) trouvé(s)
                    </div>
                    @foreach($patients as $patient)
                        <div class="px-4 py-2 hover:bg-primary-light cursor-pointer border-b border-gray-100 last:border-b-0" 
                             wire:click="selectPatient({{ $patient['ID'] }})">
                            <div class="flex flex-col">
                                <div class="flex items-center">
                                    <i class="fas fa-user-circle text-gray-400 mr-2"></i>
                                    <span class="font-medium text-gray-900">{{ $patient['Prenom'] }}</span>
                                </div>
                                <div class="ml-6 mt-1 space-y-1">
                                    @if($patient['IdentifiantPatient'])
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-id-card w-4 mr-2"></i>
                                            <span>N° Fiche: {{ $patient['IdentifiantPatient'] }}</span>
                                        </div>
                                    @endif
                                    @if($patient['Telephone1'])
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-phone w-4 mr-2"></i>
                                            <span>Tel: {{ $patient['Telephone1'] }}</span>
                                        </div>
                                    @endif
                                    @if($patient['Telephone2'])
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-phone w-4 mr-2"></i>
                                            <span>Tel 2: {{ $patient['Telephone2'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="px-4 py-3 text-sm text-gray-500 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-search text-gray-400 text-2xl mb-2"></i>
                            <span>Aucun patient trouvé</span>
                            <span class="text-xs mt-1">Essayez avec d'autres critères de recherche</span>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif
</div> 