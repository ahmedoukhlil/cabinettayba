@extends('layouts.central')

@section('title', 'Cabinets')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">Registre central</p>
            <h1 class="mt-1 text-3xl font-bold">Cabinets</h1>
            <p class="mt-2 text-base text-slate-600">Chaque cabinet conserve sa propre base de données métier.</p>
        </div>
        <a href="{{ route('central.tenants.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:opacity-90">
            Ajouter un cabinet
        </a>
    </div>

    <div class="mb-7 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Total</p>
            <p class="mt-1 text-3xl font-bold">{{ $tenants->total() }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-sm text-emerald-700">Actifs</p>
            <p class="mt-1 text-3xl font-bold text-emerald-900">{{ $activeCount }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-700">Suspendus</p>
            <p class="mt-1 text-3xl font-bold text-amber-900">{{ $inactiveCount }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-slate-600">Cabinet</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-slate-600">Domaine</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-slate-600">Base métier</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-slate-600">État</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tenants as $tenant)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $tenant->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $tenant->slug }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                <a href="https://{{ $tenant->domain }}" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">{{ $tenant->domain }}</a>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                <p class="font-medium">{{ $tenant->database }}</p>
                                <p class="mt-1 text-slate-500">{{ $tenant->db_host }}:{{ $tenant->db_port }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $tenant->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $tenant->is_active ? 'Actif' : 'Suspendu' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('central.tenants.edit', $tenant) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold hover:border-primary hover:text-primary">Modifier</a>
                                    <form method="POST" action="{{ route('central.tenants.toggle', $tenant) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border px-3 py-2 text-sm font-semibold {{ $tenant->is_active ? 'border-amber-300 text-amber-800 hover:bg-amber-50' : 'border-emerald-300 text-emerald-800 hover:bg-emerald-50' }}">
                                            {{ $tenant->is_active ? 'Suspendre' : 'Réactiver' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Aucun cabinet enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $tenants->links() }}</div>
@endsection
