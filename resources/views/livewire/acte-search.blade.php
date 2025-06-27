<div class="relative">
    <input 
        type="text" 
        wire:model.debounce.300ms="search" 
        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
        placeholder="Rechercher un acte..."
        autocomplete="off"
    >
    @if($showDropdown && strlen($search) > 0)
        <ul class="absolute z-50 bg-white border w-full mt-1 rounded shadow-lg max-h-64 overflow-auto">
            @forelse($actes as $acte)
                <li 
                    wire:click="selectActe({{ $acte->ID }})" 
                    class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 flex justify-between items-center"
                >
                    <span class="text-sm">{{ $acte->Acte }}</span>
                    <span class="text-xs text-gray-500 font-medium">{{ number_format($acte->PrixRef, 0, '', ' ') }} MRU</span>
                </li>
            @empty
                <li class="px-3 py-2 text-gray-400">Aucun acte trouvé</li>
            @endforelse
        </ul>
    @endif
</div>
