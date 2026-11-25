@php
    $embedded = $embedded ?? false;
    $itemStatusLabels = [
        'pending' => 'En attente', 'confirmed' => 'Confirmée', 'arrived' => 'Arrivé',
        'in_consultation' => 'En consultation', 'completed' => 'Terminée',
        'no_show' => 'Absent', 'rejected' => 'Refusée', 'cancelled' => 'Annulée',
    ];
    $itemStatusClasses = [
        'pending' => 'badge-warning', 'confirmed' => 'badge-success',
        'arrived' => 'bg-blue-100 text-blue-800',
        'in_consultation' => 'bg-purple-100 text-purple-800',
        'completed' => 'bg-slate-200 text-slate-800',
        'no_show' => 'bg-orange-100 text-orange-800',
        'rejected' => 'badge-error', 'cancelled' => 'bg-gray-200 text-gray-700',
    ];
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

    <nav class="flex flex-nowrap gap-2 overflow-x-auto rounded-xl border border-gray-200 bg-white p-2 sm:flex-wrap" aria-label="Filtrer les demandes">
        @foreach(['pending' => ['En attente', 'fa-clock'], 'confirmed' => ['File active', 'fa-users'], 'completed' => ['Terminées', 'fa-check-double'], 'no_show' => ['Absents', 'fa-user-times'], 'rejected' => ['Refusées', 'fa-times-circle'], 'cancelled' => ['Annulées', 'fa-calendar-times']] as $value => [$label, $icon])
            <a href="{{ route('appointment-requests.index', ['status' => $value] + ($embedded ? ['embedded' => 1] : [])) }}"
               class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $status === $value ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-primary-light hover:text-primary' }}">
                <i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}
            </a>
        @endforeach
    </nav>

    <section class="space-y-3 md:hidden" aria-label="Liste des demandes">
        @forelse($requests as $item)
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-gray-900">{{ trim($item->prenom . ' ' . $item->nom) }}</p>
                        <a href="{{ route('appointment-requests.show', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}" class="mt-1 inline-flex min-h-11 items-center text-sm font-semibold text-primary hover:underline">
                            {{ $item->reference }}
                        </a>
                    </div>
                    <span class="badge {{ $itemStatusClasses[$item->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $itemStatusLabels[$item->status] ?? $item->status }}
                    </span>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-gray-100 pt-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-400">Créneau</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $item->date_souhaitee->format('d/m/Y') }} · {{ substr($item->heure_souhaitee, 0, 5) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-400">Médecin</dt>
                        <dd class="mt-1 text-gray-700">Dr {{ optional($item->medecin)->Nom ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-400">Téléphone</dt>
                        <dd><a href="tel:{{ $item->telephone }}" class="mt-1 inline-flex min-h-11 items-center font-semibold text-primary">{{ $item->telephone }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-400">Motif</dt>
                        <dd class="mt-1 text-gray-700">{{ optional($item->acte)->Acte ?? 'Consultation' }}</dd>
                    </div>
                </dl>

                @if($item->message)
                    <p class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">{{ $item->message }}</p>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-2">
                    @if($item->status === 'pending')
                        <form method="POST" action="{{ route('appointment-requests.confirm', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                <i class="fas fa-check" aria-hidden="true"></i> Confirmer
                            </button>
                        </form>
                        <form method="POST" action="{{ route('appointment-requests.reject', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}" onsubmit="event.preventDefault(); const form = this; confirmAction('Refuser la demande', 'Le créneau sera libéré et le patient sera notifié.', () => form.submit());">
                            @csrf
                            <button type="submit" class="btn-danger min-h-12 w-full justify-center px-3 py-2 text-sm"><i class="fas fa-times" aria-hidden="true"></i> Refuser</button>
                        </form>
                    @else
                        <a href="{{ route('appointment-requests.show', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}" class="btn-secondary col-span-2 min-h-12 justify-center">
                            <i class="fas fa-arrow-right" aria-hidden="true"></i> Ouvrir la demande
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-12 text-center shadow-sm">
                <i class="fas fa-inbox mb-3 block text-3xl text-primary/30" aria-hidden="true"></i>
                <p class="font-medium text-gray-600">Aucune demande dans cette catégorie</p>
                <p class="mt-1 text-sm text-gray-400">Les nouvelles demandes apparaîtront automatiquement ici.</p>
            </div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block" aria-label="Tableau des demandes">
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
                                <a href="{{ route('appointment-requests.show', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}" class="mt-1 block text-xs font-semibold text-primary hover:underline">{{ $item->reference }}</a>
                            </td>
                            <td class="whitespace-nowrap"><a href="tel:{{ $item->telephone }}" class="font-medium text-primary hover:underline">{{ $item->telephone }}</a></td>
                            <td>Dr {{ optional($item->medecin)->Nom ?: '—' }}</td>
                            <td>{{ optional($item->acte)->Acte ?? 'Consultation' }}</td>
                            <td class="max-w-xs text-sm text-gray-600">{{ $item->message ?: '—' }}</td>
                            <td class="text-right">
                                @if($item->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('appointment-requests.confirm', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500">
                                                <i class="fas fa-check" aria-hidden="true"></i> Confirmer
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('appointment-requests.reject', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}" onsubmit="event.preventDefault(); const form = this; confirmAction('Refuser la demande', 'Le créneau sera libéré et le patient sera notifié.', () => form.submit());">
                                            @csrf
                                            <button type="submit" class="btn-danger px-3 py-2 text-xs"><i class="fas fa-times" aria-hidden="true"></i> Refuser</button>
                                        </form>
                                    </div>
                                @else
                                    <a href="{{ route('appointment-requests.show', ['appointmentRequest' => $item] + ($embedded ? ['embedded' => 1] : [])) }}" class="badge {{ $itemStatusClasses[$item->status] ?? 'bg-gray-100 text-gray-700' }} hover:underline">
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
    </section>

    @if($requests->hasPages())
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">{{ $requests->links() }}</div>
    @endif
</main>
