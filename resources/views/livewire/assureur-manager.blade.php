<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Gestion des assureurs</h2>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-primary-light text-primary rounded">{{ session('message') }}</div>
    @endif

    <!-- Barre de recherche et bouton nouveau -->
    <div class="mb-4 flex items-center gap-4">
        <input type="text" wire:model="search" placeholder="Rechercher un assureur..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
        <button wire:click="openModal" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">+ Nouvel assureur</button>
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-8 relative">
                <div class="text-xl font-bold mb-4">{{ $assureurId ? 'Modifier' : 'Créer' }} un assureur</div>
                <button wire:click="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-primary text-2xl font-bold">&times;</button>
                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label for="libAssurance" class="block text-sm font-medium text-gray-700">Nom de l'assureur *</label>
                        <input type="text" wire:model.defer="libAssurance" id="libAssurance" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('libAssurance') <span class="text-primary text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="tauxdePEC" class="block text-sm font-medium text-gray-700">Taux de PEC (%) *</label>
                        <input type="number" step="0.01" wire:model.defer="tauxdePEC" id="tauxdePEC" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('tauxdePEC') <span class="text-primary text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Annuler</button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal de suppression -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-8 relative">
                <div class="text-xl font-bold mb-4">Confirmer la suppression</div>
                <p class="mb-6">Êtes-vous sûr de vouloir supprimer cet assureur ? Cette action est irréversible.</p>
                <div class="flex justify-end space-x-3">
                    <button wire:click="deleteAssureur" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">Supprimer</button>
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Annuler</button>
                </div>
            </div>
        </div>
    @endif
</div> 