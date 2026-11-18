@extends('layouts.app')

@section('title', 'Demandes de rendez-vous')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Demandes de rendez-vous en ligne</h1>
            <p class="mt-1 text-sm text-slate-600">Vérifiez le créneau avant de confirmer la demande.</p>
        </div>
        <a href="{{ route('public.appointment.create') }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 font-semibold text-blue-800 hover:bg-blue-100">Voir la page publique</a>
    </div>

    @if(session('message'))<div class="mt-5 rounded-lg bg-green-50 p-4 text-green-800" role="status">{{ session('message') }}</div>@endif
    @if(session('error'))<div class="mt-5 rounded-lg bg-red-50 p-4 text-red-800" role="alert">{{ session('error') }}</div>@endif

    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Filtrer les demandes">
        @foreach(['pending' => 'En attente', 'confirmed' => 'Confirmées', 'rejected' => 'Refusées'] as $value => $label)
            <a href="{{ route('appointment-requests.index', ['status' => $value]) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === $value ? 'bg-blue-800 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-[900px] w-full divide-y divide-slate-200">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Créneau</th><th class="px-4 py-3">Patient</th><th class="px-4 py-3">Téléphone</th><th class="px-4 py-3">Médecin</th><th class="px-4 py-3">Motif</th><th class="px-4 py-3">Message</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($requests as $item)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-900">{{ $item->date_souhaitee->format('d/m/Y') }}<br><span class="text-sm font-normal text-slate-600">{{ substr($item->heure_souhaitee, 0, 5) }}</span></td>
                        <td class="px-4 py-4 text-slate-900">{{ trim($item->prenom . ' ' . $item->nom) }}<br><span class="text-xs text-slate-500">{{ $item->reference }}</span></td>
                        <td class="whitespace-nowrap px-4 py-4"><a href="tel:{{ $item->telephone }}" class="text-blue-700 hover:underline">{{ $item->telephone }}</a></td>
                        <td class="px-4 py-4">Dr {{ optional($item->medecin)->Nom }}</td>
                        <td class="px-4 py-4">{{ optional($item->acte)->Acte ?? 'Consultation' }}</td>
                        <td class="max-w-xs px-4 py-4 text-sm text-slate-600">{{ $item->message ?: '—' }}</td>
                        <td class="px-4 py-4 text-right">
                            @if($item->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('appointment-requests.confirm', $item) }}">@csrf<button class="rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-600">Confirmer</button></form>
                                    <form method="POST" action="{{ route('appointment-requests.reject', $item) }}" onsubmit="return confirm('Refuser cette demande et libérer le créneau ?')">@csrf<button class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600">Refuser</button></form>
                                </div>
                            @else
                                <span class="text-sm text-slate-500">Traitée</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">Aucune demande dans cette catégorie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $requests->links() }}</div>
</div>
@endsection
