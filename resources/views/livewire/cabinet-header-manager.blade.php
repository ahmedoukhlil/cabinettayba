<div class="p-4 md:p-6">
    @if(session()->has('message'))
        <div class="mb-5 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-800" role="status">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
            <h3 class="mb-4 flex items-center gap-2 font-bold text-primary">
                <i class="fas fa-image" aria-hidden="true"></i> Identité visuelle
            </h3>
            <div class="grid gap-5 md:grid-cols-[9rem_1fr] md:items-start">
                <div class="flex h-36 w-36 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-3">
                    @if($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Aperçu du nouveau logo" class="max-h-full max-w-full object-contain">
                    @else
                        <img src="{{ $currentLogoUrl }}" alt="Logo actuel du cabinet" class="max-h-full max-w-full object-contain">
                    @endif
                </div>
                <div>
                    <label for="cabinet-logo" class="mb-2 block text-sm font-semibold text-gray-700">Logo du cabinet</label>
                    <input id="cabinet-logo" type="file" wire:model="logo" accept=".png,.jpg,.jpeg,.webp"
                           class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm">
                    <p class="mt-2 text-xs text-gray-500">PNG, JPG ou WEBP — 2 Mo maximum.</p>
                    @error('logo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <div wire:loading.flex wire:target="logo" class="mt-2 items-center gap-2 text-sm text-primary" role="status">
                        <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Préparation de l’aperçu…
                    </div>
                    @if($hasLogo)
                        <button type="button" wire:click="$set('showRemoveLogoModal', true)" class="mt-3 text-sm font-semibold text-red-600 hover:text-red-800">
                            Retirer le logo personnalisé
                        </button>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
            <h3 class="mb-4 flex items-center gap-2 font-bold text-primary">
                <i class="fas fa-building" aria-hidden="true"></i> Informations du cabinet
            </h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="cabinet-name" class="mb-1 block text-sm font-semibold text-gray-700">Nom du cabinet *</label>
                    <input id="cabinet-name" type="text" wire:model="name" class="form-input" maxlength="150">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div dir="rtl">
                    <label for="cabinet-name-ar" class="mb-1 block text-sm font-semibold text-gray-700">اسم العيادة</label>
                    <input id="cabinet-name-ar" type="text" wire:model="nameAr" class="form-input text-right" maxlength="150">
                    @error('nameAr')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cabinet-phone" class="mb-1 block text-sm font-semibold text-gray-700">Téléphone public</label>
                    <input id="cabinet-phone" type="tel" wire:model="phone" class="form-input" dir="ltr" maxlength="50">
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cabinet-email" class="mb-1 block text-sm font-semibold text-gray-700">Adresse e-mail</label>
                    <input id="cabinet-email" type="email" wire:model="email" class="form-input" dir="ltr" maxlength="190">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="cabinet-address" class="mb-1 block text-sm font-semibold text-gray-700">Adresse</label>
                    <textarea id="cabinet-address" wire:model="address" class="form-textarea" rows="3" maxlength="500"></textarea>
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
            <h3 class="mb-4 flex items-center gap-2 font-bold text-primary">
                <i class="fas fa-palette" aria-hidden="true"></i> Apparence et réservations
            </h3>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="primary-color" class="mb-1 block text-sm font-semibold text-gray-700">Couleur principale</label>
                    <div class="flex gap-2">
                        <input id="primary-color" type="color" wire:model.live="primaryColor" class="h-11 w-14 rounded border border-gray-300 p-1">
                        <input type="text" wire:model.live.debounce.300ms="primaryColor" class="form-input font-mono uppercase" maxlength="7" aria-label="Code de la couleur principale">
                    </div>
                    @error('primaryColor')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="secondary-color" class="mb-1 block text-sm font-semibold text-gray-700">Couleur secondaire</label>
                    <div class="flex gap-2">
                        <input id="secondary-color" type="color" wire:model.live="secondaryColor" class="h-11 w-14 rounded border border-gray-300 p-1">
                        <input type="text" wire:model.live.debounce.300ms="secondaryColor" class="form-input font-mono uppercase" maxlength="7" aria-label="Code de la couleur secondaire">
                    </div>
                    @error('secondaryColor')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reference-prefix" class="mb-1 block text-sm font-semibold text-gray-700">Préfixe des demandes</label>
                    <input id="reference-prefix" type="text" wire:model="referencePrefix" class="form-input uppercase" maxlength="12" placeholder="RDV">
                    @error('referencePrefix')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 flex justify-end border-t border-gray-200 bg-white/95 px-1 py-4 backdrop-blur">
            <button type="submit" class="btn-primary min-w-44 justify-center" wire:loading.attr="disabled" wire:target="save,logo">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save" aria-hidden="true"></i> Enregistrer</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </button>
        </div>
    </form>

    @if($showRemoveLogoModal)
        <x-modal id="cabinet-logo-delete" title="Retirer le logo personnalisé ?"
                 subtitle="Le logo par défaut sera utilisé à sa place."
                 icon="fa-triangle-exclamation" close="$set('showRemoveLogoModal', false)" level="nested" max-width="max-w-md">
            <p class="text-sm text-gray-700">Cette action supprimera le fichier actuellement associé au cabinet.</p>
            <x-slot:footer>
                <button type="button" wire:click="$set('showRemoveLogoModal', false)" class="btn-secondary">Annuler</button>
                <button type="button" wire:click="removeLogo" class="btn-danger" wire:loading.attr="disabled" wire:target="removeLogo">Retirer</button>
            </x-slot:footer>
        </x-modal>
    @endif
</div>
