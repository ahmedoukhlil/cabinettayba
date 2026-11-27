<div class="p-4 md:p-6">
    @if(session()->has('message'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-800" role="status">
            {{ session('message') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-800" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <label for="payment-method-search" class="sr-only">Rechercher un mode de paiement</label>
            <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input id="payment-method-search" type="search" wire:model.live.debounce.300ms="search"
                   class="form-input pl-10" placeholder="Rechercher un mode de paiement…">
        </div>
        <button type="button" wire:click="openModal" class="btn-primary justify-center whitespace-nowrap">
            <i class="fas fa-plus" aria-hidden="true"></i> Nouveau mode
        </button>
    </div>

    <div wire:loading.flex wire:target="search" class="mb-3 items-center gap-2 text-sm text-primary" role="status">
        <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Actualisation…
    </div>

    <div class="space-y-3 md:hidden">
        @forelse($methods as $method)
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900">{{ $method->LibPaie }}</p>
                        <p class="mt-1 text-xs text-gray-500">Mode de règlement</p>
                    </div>
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-primary-light text-primary">
                        <i class="fas fa-credit-card" aria-hidden="true"></i>
                    </span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="button" wire:click="openModal({{ $method->idtypepaie }})" class="btn-secondary justify-center">
                        <i class="fas fa-pen" aria-hidden="true"></i> Modifier
                    </button>
                    <button type="button" wire:click="confirmDelete({{ $method->idtypepaie }})" class="btn-danger justify-center">
                        <i class="fas fa-trash" aria-hidden="true"></i> Supprimer
                    </button>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
                <i class="fas fa-credit-card mb-3 block text-3xl text-primary/40" aria-hidden="true"></i>
                Aucun mode de paiement trouvé.
            </div>
        @endforelse
    </div>

    <div class="hidden overflow-hidden rounded-xl border border-gray-200 md:block">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Mode de paiement</th>
                    <th class="w-64 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($methods as $method)
                    <tr>
                        <td class="font-medium text-gray-900">
                            <i class="fas fa-credit-card mr-2 text-primary" aria-hidden="true"></i>
                            {{ $method->LibPaie }}
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="openModal({{ $method->idtypepaie }})" class="btn-secondary px-3 py-2 text-sm">
                                    <i class="fas fa-pen" aria-hidden="true"></i> Modifier
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $method->idtypepaie }})" class="btn-danger px-3 py-2 text-sm">
                                    <i class="fas fa-trash" aria-hidden="true"></i> Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-10 text-center text-gray-500">Aucun mode de paiement trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $methods->links() }}</div>

    @if($showModal)
        <x-modal id="payment-method-form" :title="$paymentMethodId ? 'Modifier le mode de paiement' : 'Ajouter un mode de paiement'"
                 icon="fa-credit-card" close="closeModal" level="nested" max-width="max-w-lg">
            <form wire:submit="save">
                <div>
                    <label for="payment-method-label" class="mb-2 block text-sm font-semibold text-gray-700">Libellé *</label>
                    <input id="payment-method-label" type="text" wire:model="label" class="form-input"
                           maxlength="100" autocomplete="off" placeholder="Ex. Espèces, Bankily, Carte bancaire">
                    @error('label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Enregistrer</span>
                        <span wire:loading wire:target="save">Enregistrement…</span>
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    @if($showDeleteModal)
        <x-modal id="payment-method-delete" title="Supprimer ce mode de paiement ?"
                 subtitle="La suppression est possible uniquement si le mode n’a jamais été utilisé."
                 icon="fa-triangle-exclamation" close="closeDeleteModal" level="nested" max-width="max-w-md">
            <p class="text-sm text-gray-700">Les règlements et l’historique comptable existants ne seront jamais modifiés.</p>
            <x-slot:footer>
                <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Annuler</button>
                <button type="button" wire:click="deletePaymentMethod" class="btn-danger"
                        wire:loading.attr="disabled" wire:target="deletePaymentMethod">Supprimer</button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
