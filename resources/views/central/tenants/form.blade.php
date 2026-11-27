@extends('layouts.central')

@php($editing = $tenant->exists)
@section('title', $editing ? 'Modifier un cabinet' : 'Ajouter un cabinet')

@section('content')
    <div class="mb-7">
        <a href="{{ route('central.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">Retour aux cabinets</a>
        <h1 class="mt-3 text-3xl font-bold">{{ $editing ? 'Modifier le cabinet' : 'Ajouter un cabinet' }}</h1>
        <p class="mt-2 text-base text-slate-600">La connexion sera testée avant l’enregistrement. Aucune migration ne sera exécutée sur la base métier.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <p class="font-semibold">Le formulaire contient des erreurs.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing ? route('central.tenants.update', $tenant) : route('central.tenants.store') }}"
        class="space-y-6">
        @csrf
        @if($editing) @method('PUT') @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-xl font-bold">Identification</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold">Nom du cabinet</label>
                    <input id="name" name="name" value="{{ old('name', $tenant->name) }}" required class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div dir="rtl">
                    <label for="name_ar" class="mb-2 block text-sm font-semibold">الاسم بالعربية</label>
                    <input id="name_ar" name="name_ar" value="{{ old('name_ar', $tenant->name_ar) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="slug" class="mb-2 block text-sm font-semibold">Identifiant technique</label>
                    <input id="slug" name="slug" value="{{ old('slug', $tenant->slug) }}" required placeholder="cabinet-nouakchott" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                    <p class="mt-2 text-sm text-slate-500">Lettres, chiffres, tirets et tirets bas.</p>
                </div>
                <div>
                    <label for="domain" class="mb-2 block text-sm font-semibold">Domaine complet</label>
                    <input id="domain" name="domain" value="{{ old('domain', $tenant->domain) }}" required placeholder="cabinet.rdvsysdentalmr.com" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-xl font-bold">Base de données métier</h2>
            <p class="mt-2 text-sm text-slate-600">Les identifiants sont stockés dans la base centrale. Le mot de passe est chiffré avec la clé de l’application.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="db_host" class="mb-2 block text-sm font-semibold">Serveur MySQL</label>
                    <input id="db_host" name="db_host" value="{{ old('db_host', $tenant->db_host) }}" required placeholder="192.0.2.10" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="db_port" class="mb-2 block text-sm font-semibold">Port</label>
                    <input id="db_port" name="db_port" type="number" min="1" max="65535" value="{{ old('db_port', $tenant->db_port ?: 3306) }}" required class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="database" class="mb-2 block text-sm font-semibold">Nom de la base</label>
                    <input id="database" name="database" value="{{ old('database', $tenant->database) }}" required class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="db_username" class="mb-2 block text-sm font-semibold">Utilisateur MySQL</label>
                    <input id="db_username" name="db_username" value="{{ old('db_username', $tenant->db_username) }}" required autocomplete="off" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div class="md:col-span-2">
                    <label for="db_password" class="mb-2 block text-sm font-semibold">Mot de passe MySQL</label>
                    <input id="db_password" name="db_password" type="password" {{ $editing ? '' : 'required' }} autocomplete="new-password" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                    <p class="mt-2 text-sm text-slate-500">{{ $editing ? 'Laissez vide pour conserver le mot de passe actuel.' : 'Le mot de passe ne sera jamais réaffiché.' }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-xl font-bold">Identité et contact</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="logo" class="mb-2 block text-sm font-semibold">Logo</label>
                    <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp,.svg" class="w-full rounded-xl border border-slate-300 p-2">
                    @if($tenant->logo_path)<p class="mt-2 text-sm text-slate-500">Un logo est déjà configuré.</p>@endif
                </div>
                <div>
                    <label for="reference_prefix" class="mb-2 block text-sm font-semibold">Préfixe des réservations</label>
                    <input id="reference_prefix" name="reference_prefix" value="{{ old('reference_prefix', $tenant->reference_prefix ?: 'RDV') }}" maxlength="12" class="w-full rounded-xl border-slate-300 uppercase focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="primary_color" class="mb-2 block text-sm font-semibold">Couleur principale</label>
                    <input id="primary_color" name="primary_color" type="color" value="{{ old('primary_color', $tenant->primary_color ?: '#1e3a8a') }}" class="h-12 w-full rounded-xl border border-slate-300 p-1">
                </div>
                <div>
                    <label for="secondary_color" class="mb-2 block text-sm font-semibold">Couleur secondaire</label>
                    <input id="secondary_color" name="secondary_color" type="color" value="{{ old('secondary_color', $tenant->secondary_color ?: '#0e7490') }}" class="h-12 w-full rounded-xl border border-slate-300 p-1">
                </div>
                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold">Téléphone public</label>
                    <input id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold">E-mail public</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $tenant->email) }}" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">
                </div>
                <div class="md:col-span-2">
                    <label for="address" class="mb-2 block text-sm font-semibold">Adresse</label>
                    <textarea id="address" name="address" rows="3" class="w-full rounded-xl border-slate-300 focus:border-primary focus:ring-primary">{{ old('address', $tenant->address) }}</textarea>
                </div>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <label class="flex items-center gap-3 font-semibold">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tenant->is_active)) class="rounded border-slate-300 text-primary focus:ring-primary">
                Cabinet actif
            </label>
            <div class="flex gap-3">
                <a href="{{ route('central.dashboard') }}" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700">Annuler</a>
                <button class="rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:opacity-90">
                    Tester et {{ $editing ? 'enregistrer' : 'créer' }}
                </button>
            </div>
        </section>
    </form>
@endsection
