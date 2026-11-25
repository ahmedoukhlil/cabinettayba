<div class="relative">
    <div class="flex">
        <label for="patient-search-type" class="sr-only">Critère de recherche</label>
        <select id="patient-search-type" wire:model="searchBy" class="rounded-l-lg border-r-0 border-gray-300 bg-gray-50 text-sm text-gray-600 focus:border-primary focus:ring-primary">
            <option value="telephone">Téléphone</option>
            <option value="nom">Nom</option>
            <option value="identifiant">N° Fiche</option>
        </select>
        <div class="relative flex-1">
            <label for="patient-search-input" class="sr-only">Rechercher un patient</label>
            <input id="patient-search-input" type="search" wire:model.live.debounce.300ms="search"
                wire:keydown.enter="selectFirstPatient" wire:focus="onFocus" wire:blur="onBlur"
                autocomplete="off" placeholder="Rechercher un patient…"
                class="w-full rounded-r-lg border-gray-300 pr-10 text-sm focus:border-primary focus:ring-primary">

            <div wire:loading wire:target="search" class="absolute inset-y-0 right-3 flex items-center" aria-label="Recherche en cours">
                <span class="spinner spinner-dark"></span>
            </div>

            @if($selectedPatient && !$isSearching)
                <button type="button" wire:click="clearPatient" class="absolute inset-y-0 right-2 rounded p-2 text-gray-400 hover:text-primary" aria-label="Effacer la sélection">
                    <i class="fas fa-times"></i>
                </button>
            @endif
        </div>
    </div>

    @if($selectedPatient)
        <div class="mt-2 flex items-center justify-between rounded-lg border border-primary/20 bg-primary-light px-3 py-2 text-sm">
            <span class="font-semibold text-primary"><i class="fas fa-user-check mr-2"></i>{{ trim(($selectedPatient['Prenom'] ?? '') . ' ' . ($selectedPatient['NomPatient'] ?? '')) }}</span>
            <span class="text-gray-600">{{ $selectedPatient['Telephone1'] ?? '' }}</span>
        </div>
    @endif

    @if((strlen(trim($search)) >= 1 || $isSearching) && $isFocused)
        <div class="patient-search-results absolute left-0 right-0 z-50 mt-1 max-h-72 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl">
            @if($isSearching)
                <div class="flex items-center justify-center gap-2 px-4 py-4 text-sm text-gray-500"><span class="spinner spinner-dark"></span> Recherche en cours…</div>
            @elseif($searchBy === 'telephone' && strlen(trim($search)) > 0 && strlen(trim($search)) < 8)
                <div class="px-4 py-4 text-center text-sm text-gray-500">
                    <i class="fas fa-phone mb-2 block text-xl text-gray-300"></i>
                    Saisissez au moins 8 chiffres.
                </div>
            @elseif(!empty($patients))
                <div class="border-b border-gray-100 px-3 py-2 text-xs font-medium text-gray-500">{{ count($patients) }} résultat(s)</div>
                @foreach($patients as $patient)
                    <button type="button" wire:click="selectPatient({{ $patient['ID'] }})"
                        class="block w-full border-b border-gray-100 px-4 py-3 text-left last:border-0 hover:bg-primary-light focus:bg-primary-light focus:outline-none">
                        <span class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="fas fa-user"></i></span>
                            <span class="min-w-0">
                                <strong class="block truncate text-sm text-gray-900">{{ trim(($patient['Prenom'] ?? '') . ' ' . ($patient['NomPatient'] ?? '')) }}</strong>
                                <span class="mt-1 block text-xs text-gray-500">
                                    N° {{ $patient['IdentifiantPatient'] ?: '—' }}
                                    @if($patient['Telephone1']) · {{ $patient['Telephone1'] }} @endif
                                </span>
                            </span>
                        </span>
                    </button>
                @endforeach
            @elseif(strlen(trim($search)) >= ($searchBy === 'telephone' ? 8 : 1))
                <div class="px-4 py-5 text-center text-sm text-gray-500">
                    <i class="fas fa-search mb-2 block text-xl text-gray-300"></i>
                    Aucun patient trouvé.
                </div>
            @endif
        </div>
    @endif
</div>

<script>
document.addEventListener('livewire:init', function () {
    let blurTimeout;

    Livewire.on('maintain-focus', () => {
        const input = document.getElementById('patient-search-input');
        if (!input) return;
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    });

    Livewire.on('delay-blur', () => {
        clearTimeout(blurTimeout);
        blurTimeout = setTimeout(() => @this.call('closeResults'), 200);
    });

    document.addEventListener('mousedown', event => {
        if (event.target.closest('.patient-search-results')) clearTimeout(blurTimeout);
    });
});
</script>
