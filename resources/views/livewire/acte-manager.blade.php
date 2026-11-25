<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Gestion des actes</h2>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('message') }}</div>
    @endif

    <!-- Filtres et bouton nouveau -->
    <div class="mb-4 flex items-center gap-4">
        <label class="font-semibold">Filtrer par assureur :</label>
        <select wire:model.live="selectedAssureur" class="form-select max-w-xs">
            <option value="">Tous</option>
            @foreach($assureurs as $assureur)
                <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
            @endforeach
        </select>
        <div class="flex-1">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un acte..." class="form-input">
        </div>
        <button type="button" wire:click="openModal" class="btn-primary whitespace-nowrap"><i class="fas fa-plus" aria-hidden="true"></i> Nouvel acte</button>
    </div>

    <!-- Tableau des actes -->
    <div class="mt-2 max-h-[60vh] overflow-y-auto">
        <div class="overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix de référence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assureur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($actes as $acte)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $acte->Acte }}
                                @if($acte->ActeArab)
                                    <div class="text-xs text-gray-500">{{ $acte->ActeArab }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($acte->PrixRef, 2) }} MRU
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $acte->assureur->LibAssurance ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <button wire:click="openModal({{ $acte->ID }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Modifier</button>
                                <button wire:click="confirmDelete({{ $acte->ID }})" class="text-red-600 hover:text-red-900">Supprimer</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-gray-400">Aucun acte trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $actes->links() }}
        </div>
    </div>

    <!-- Modal création/édition -->
    @if($showModal)
        <x-modal id="act-form" :title="$acteId ? 'Modifier un acte' : 'Créer un acte'" icon="fa-tooth" close="closeModal" level="nested" max-width="max-w-lg">
                <form wire:submit="save">
                    <div class="mb-4">
                        <label for="acteNom" class="block text-sm font-medium text-gray-700">Nom de l'acte *</label>
                        <input type="text" wire:model="acteNom" id="acteNom" class="form-input">
                        @error('acteNom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="montant" class="block text-sm font-medium text-gray-700">Prix de référence *</label>
                        <input type="number" min="0" step="0.01" wire:model="montant" id="montant" class="form-input">
                        @error('montant') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="assureurId" class="block text-sm font-medium text-gray-700">Assureur *</label>
                        <select wire:model="assureurId" id="assureurId" class="form-select">
                            <option value="">Sélectionner un assureur</option>
                            @foreach($assureurs as $assureur)
                                <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                            @endforeach
                        </select>
                        @error('assureurId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="acteArab" class="block text-sm font-medium text-gray-700">Nom arabe</label>
                        <input type="text" wire:model="acteArab" id="acteArab" class="form-input" dir="rtl">
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Enregistrer</span><span wire:loading wire:target="save">Enregistrement…</span></button>
                    </div>
                </form>
        </x-modal>
    @endif

    <!-- Modal de suppression -->
    @if($showDeleteModal)
        <x-modal id="act-delete" title="Supprimer l’acte ?" subtitle="Cette action est irréversible." icon="fa-triangle-exclamation" close="$set('showDeleteModal', false)" level="nested" max-width="max-w-md">
            <p class="text-sm text-gray-700">L’acte ne pourra être supprimé que s’il n’est utilisé dans aucune facture.</p>
            <x-slot:footer>
                <button type="button" wire:click="$set('showDeleteModal', false)" class="btn-secondary">Annuler</button>
                <button type="button" wire:click="deleteActe" class="btn-danger" wire:loading.attr="disabled" wire:target="deleteActe">Supprimer</button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
