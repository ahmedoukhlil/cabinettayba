<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Gestion des assureurs</h2>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-primary-light text-primary rounded">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded bg-red-50 p-3 text-red-800">{{ session('error') }}</div>
    @endif

    <!-- Barre de recherche et bouton nouveau -->
    <div class="mb-4 flex items-center gap-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un assureur..." class="form-input">
        <button type="button" wire:click="openModal" class="btn-primary whitespace-nowrap"><i class="fas fa-plus" aria-hidden="true"></i> Nouvel assureur</button>
    </div>

    <!-- Tableau des assureurs -->
    <div class="mt-2 max-h-[60vh] overflow-y-auto">
        <div class="overflow-x-auto">
            <table class="min-w-full w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taux de PEC (%)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($assureurs as $assureur)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $assureur->LibAssurance }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ is_numeric($assureur->TauxdePEC) ? number_format($assureur->TauxdePEC * 100, 2) . ' %' : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <button wire:click="openModal({{ $assureur->IDAssureur ?? $assureur->ID }})" class="text-primary hover:text-primary-dark mr-3">Modifier</button>
                                <button wire:click="confirmDelete({{ $assureur->IDAssureur ?? $assureur->ID }})" class="text-primary hover:text-primary-dark">Supprimer</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center text-gray-400">Aucun assureur trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $assureurs->links() }}
        </div>
    </div>

    <!-- Modal création/édition -->
    @if($showModal)
        <x-modal id="insurer-form" :title="$assureurId ? 'Modifier un assureur' : 'Créer un assureur'" icon="fa-shield-alt" close="closeModal" level="nested" max-width="max-w-lg">
                <form wire:submit="save">
                    <div class="mb-4">
                        <label for="libAssurance" class="block text-sm font-medium text-gray-700">Nom de l'assureur *</label>
                        <input type="text" wire:model="libAssurance" id="libAssurance" class="form-input">
                        @error('libAssurance') <span class="text-primary text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="tauxdePEC" class="block text-sm font-medium text-gray-700">Taux de PEC (%) *</label>
                        <input type="number" min="0" max="100" step="0.01" wire:model="tauxdePEC" id="tauxdePEC" class="form-input">
                        @error('tauxdePEC') <span class="text-primary text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">Enregistrer</button>
                    </div>
                </form>
        </x-modal>
    @endif

    <!-- Modal de suppression -->
    @if($showDeleteModal)
        <x-modal id="insurer-delete" title="Supprimer l’assureur ?" subtitle="Cette action est irréversible." icon="fa-triangle-exclamation" close="$set('showDeleteModal', false)" level="nested" max-width="max-w-md">
            <p class="text-sm text-gray-700">L’assureur ne pourra être supprimé que s’il n’est associé à aucun dossier.</p>
            <x-slot:footer>
                <button type="button" wire:click="$set('showDeleteModal', false)" class="btn-secondary">Annuler</button>
                <button type="button" wire:click="deleteAssureur" class="btn-danger" wire:loading.attr="disabled" wire:target="deleteAssureur">Supprimer</button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
