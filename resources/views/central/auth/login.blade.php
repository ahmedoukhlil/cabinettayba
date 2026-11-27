@extends('layouts.central')

@section('title', 'Connexion centrale')

@section('content')
    <div class="mx-auto max-w-md pt-6 sm:pt-12">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-7">
                <p class="text-sm font-semibold uppercase tracking-wide text-primary">Plateforme centrale</p>
                <h1 class="mt-2 text-2xl font-bold">Connexion administrateur</h1>
                <p class="mt-2 text-base text-slate-600">Gérez les cabinets, leurs domaines et leurs connexions métier.</p>
            </div>

            <form method="POST" action="{{ route('central.login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="w-full rounded-xl border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary">
                    @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Mot de passe</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="w-full rounded-xl border-slate-300 px-4 py-3 focus:border-primary focus:ring-primary">
                    @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-primary focus:ring-primary">
                    Rester connecté sur cet appareil
                </label>

                <button class="w-full rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:opacity-90">
                    Se connecter
                </button>
            </form>
        </section>
    </div>
@endsection
