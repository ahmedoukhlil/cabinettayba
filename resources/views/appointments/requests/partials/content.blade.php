@php
    $embedded = $embedded ?? false;
@endphp

<main class="space-y-4 p-4 md:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @unless($embedded)
                <h1 class="text-2xl font-bold text-primary">Demandes de rendez-vous en ligne</h1>
            @endunless
            <p class="text-sm text-gray-600">Vérifiez le patient et le créneau avant de traiter chaque demande.</p>
        </div>
        <a href="{{ route('public.appointment.create') }}" target="_blank" rel="noopener" class="btn-secondary justify-center whitespace-nowrap">
            <i class="fas fa-external-link-alt" aria-hidden="true"></i> Voir la page publique
        </a>
    </div>

    @if(session('message'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">
            <i class="fas fa-check-circle mr-2" aria-hidden="true"></i>{{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
            <i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i>{{ session('error') }}
        </div>
    @endif

    <nav class="flex flex-wrap gap-2 rounded-xl border border-gray-200 bg-white p-2" aria-label="Filtrer les demandes">
        @foreach(['pending' => ['En attente', 'fa-clock'], 'confirmed' => ['File active', 'fa-users'], 'completed' => ['Terminées', 'fa-check-double'], 'no_show' => ['Absents', 'fa-user-times'], 'rejected' => ['Refusées', 'fa-times-circle'], 'cancelled' => ['Annulées', 'fa-calendar-times']] as $value => [$label, $icon])
            <a href="{{ route('appointment-requests.index', ['status' => $value] + ($embedded ? ['embedded' => 1] : [])) }}"
               class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $status === $value ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-primary-light hover:text-primary' }}">
                <i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}
            </a>
        @endforeach
    </nav>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" aria-label="Liste des demandes">
        <div class="overflow-x-auto">
            <table class="min-w-[900px]">
                <thead>
                    <tr>
                        <th>Créneau</th>
                        <th>Patient</th>
                        <th>Téléphone</th>
                        <th>Médecin</th>
                        <th>Motif</th>
                        <th>Message</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $item)
                        <tr class="align-top">
                            <td class="whitespace-nowrap font-semibold text-gray-900">
                                {{ $item->date_souhaitee->format('d/m/Y') }}
                                <span class="mt-1 block text-xs font-normal text-gray-500"><i class="far fa-clock mr-1"></i>{{ substr($item->heure_souhaitee, 0, 5) }}</span>
                            </td>
                            <td>
                                <span class="font-medium text-gray-900">{{ trim($item->prenom . ' ' . $item->nom) }}</span>
                                <a href="{{ route('appointment-requests.show', $item) }}" class="mt-1 block text-xs font-semibold text-primary hover:underline">{{ $item->reference }}</a>
                            </td>
                            <td class="whitespace-nowrap"><a href="tel:{{ $item->telephone }}" class="font-medium text-primary hover:underline">{{ $item->telephone }}</a></td>
                            <td>Dr {{ optional($item->medecin)->Nom ?: '—' }}</td>
                            <td>{{ optional($item->acte)->Acte ?? 'Consultation' }}</td>
                            <td class="max-w-xs text-sm text-gray-600">{{ $item->message ?: '—' }}</td>
                            <td class="text-right">
                                @if($item->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('appointment-requests.confirm', $item) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                                                <i class="fas fa-check" aria-hidden="true"></i> Confirmer
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('appointment-requests.reject', $item) }}" onsubmit="event.preventDefault(); const form = this; confirmAction('Refuser la demande', 'Le créneau sera libéré et le patient sera notifié.', () => form.submit());">
                                            @csrf
                                            <button type="submit" class="btn-danger px-3 py-2 text-xs"><i class="fas fa-times" aria-hidden="true"></i> Refuser</button>
                                        </form>
                                    </div>
                                @else
                                    @php
                                        $itemStatusLabels = [
                                            'confirmed' => 'Confirmée', 'arrived' => 'Arrivé',
                                            'in_consultation' => 'En consultation', 'completed' => 'Terminée',
                                            'no_show' => 'Absent', 'rejected' => 'Refusée', 'cancelled' => 'Annulée',
                                        ];
                                        $itemStatusClasses = [
                                            'confirmed' => 'badge-success', 'arrived' => 'bg-blue-100 text-blue-800',
                                            'in_consultation' => 'bg-purple-100 text-purple-800',
                                            'completed' => 'bg-slate-200 text-slate-800',
                                            'no_show' => 'bg-orange-100 text-orange-800',
                                            'rejected' => 'badge-error', 'cancelled' => 'bg-gray-200 text-gray-700',
                                        ];
                                    @endphp
                                    <a href="{{ route('appointment-requests.show', $item) }}" class="badge {{ $itemStatusClasses[$item->status] ?? 'bg-gray-100 text-gray-700' }} hover:underline">
                                        {{ $itemStatusLabels[$item->status] ?? $item->status }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center">
                                <i class="fas fa-inbox mb-3 block text-3xl text-primary/30" aria-hidden="true"></i>
                                <p class="font-medium text-gray-600">Aucune demande dans cette catégorie</p>
                                <p class="mt-1 text-sm text-gray-400">Les nouvelles demandes apparaîtront automatiquement ici.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $requests->links() }}</div>
        @endif
    </section>
</main>
