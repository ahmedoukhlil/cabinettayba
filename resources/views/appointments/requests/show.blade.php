@extends('layouts.app')

@section('title', 'Traiter une demande de rendez-vous')

@section('content')
<main class="mx-auto max-w-4xl space-y-5 px-4 py-5 sm:px-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('appointment-requests.index') }}" class="text-sm font-semibold text-primary hover:underline">
                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Toutes les demandes
            </a>
            <h1 class="mt-2 text-2xl font-bold text-primary">Demande {{ $appointmentRequest->reference }}</h1>
        </div>
        @php
            $statusClasses = [
                'pending' => 'bg-amber-100 text-amber-900',
                'confirmed' => 'bg-green-100 text-green-900',
                'rejected' => 'bg-red-100 text-red-900',
                'cancelled' => 'bg-gray-200 text-gray-800',
            ];
            $statusLabels = ['pending' => 'En attente', 'confirmed' => 'Confirmée', 'rejected' => 'Refusée', 'cancelled' => 'Annulée'];
        @endphp
        <span class="rounded-full px-4 py-2 text-sm font-bold {{ $statusClasses[$appointmentRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
            {{ $statusLabels[$appointmentRequest->status] ?? $appointmentRequest->status }}
        </span>
    </div>

    @if(session('message'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 font-medium text-green-800" role="status">
            <i class="fas fa-check-circle mr-2" aria-hidden="true"></i>{{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 font-medium text-red-800" role="alert">
            <i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i>{{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-2">
        <div><p class="text-xs font-bold uppercase text-gray-400">Patient</p><p class="mt-1 font-semibold text-gray-900">{{ trim($appointmentRequest->prenom . ' ' . $appointmentRequest->nom) }}</p></div>
        <div><p class="text-xs font-bold uppercase text-gray-400">Téléphone</p><a href="tel:{{ $appointmentRequest->telephone }}" class="mt-1 inline-block font-semibold text-primary hover:underline">{{ $appointmentRequest->telephone }}</a></div>
        <div><p class="text-xs font-bold uppercase text-gray-400">Médecin</p><p class="mt-1 text-gray-900">Dr {{ optional($appointmentRequest->medecin)->Nom ?: '—' }}</p></div>
        <div><p class="text-xs font-bold uppercase text-gray-400">Motif</p><p class="mt-1 text-gray-900">{{ optional($appointmentRequest->acte)->Acte ?? 'Consultation' }}</p></div>
        <div><p class="text-xs font-bold uppercase text-gray-400">Date</p><p class="mt-1 font-semibold text-gray-900">{{ $appointmentRequest->date_souhaitee->format('d/m/Y') }}</p></div>
        <div><p class="text-xs font-bold uppercase text-gray-400">Heure</p><p class="mt-1 font-semibold text-gray-900">{{ substr($appointmentRequest->heure_souhaitee, 0, 5) }}</p></div>
        <div class="sm:col-span-2"><p class="text-xs font-bold uppercase text-gray-400">Message</p><p class="mt-1 text-gray-700">{{ $appointmentRequest->message ?: 'Aucun message' }}</p></div>
    </section>

    @if(in_array($appointmentRequest->status, ['pending', 'confirmed'], true) && $canManage)
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                @if($appointmentRequest->status === 'pending')
                <h2 class="text-lg font-bold text-gray-900">Traiter la demande</h2>
                <p class="mt-1 text-sm text-gray-500">La confirmation créera automatiquement le rendez-vous dans l’agenda.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('appointment-requests.confirm', $appointmentRequest) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-3 font-semibold text-white hover:bg-green-700">
                            <i class="fas fa-check" aria-hidden="true"></i> Confirmer
                        </button>
                    </form>
                    <form method="POST" action="{{ route('appointment-requests.reject', $appointmentRequest) }}" onsubmit="event.preventDefault(); const form = this; confirmAction('Refuser la demande', 'Le créneau sera libéré et le patient sera notifié.', () => form.submit());">
                        @csrf
                        <button type="submit" class="btn-danger px-4 py-3"><i class="fas fa-times mr-1" aria-hidden="true"></i> Refuser</button>
                    </form>
                </div>
                @else
                    <h2 class="text-lg font-bold text-gray-900">Rendez-vous confirmé</h2>
                    <p class="mt-1 text-sm text-gray-500">L’annulation libérera le créneau dans l’agenda et préviendra le patient.</p>
                    <form method="POST" action="{{ route('appointment-requests.cancel', $appointmentRequest) }}" class="mt-5" onsubmit="event.preventDefault(); const form = this; confirmAction('Annuler le rendez-vous', 'Cette action libérera le créneau et notifiera le patient.', () => form.submit());">
                        @csrf
                        <button type="submit" class="btn-danger px-4 py-3">
                            <i class="fas fa-calendar-times mr-1" aria-hidden="true"></i> Annuler le rendez-vous
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <h2 class="text-lg font-bold text-primary">Reprogrammer</h2>
                <p class="mt-1 text-sm text-gray-600">Choisissez un nouveau créneau. L’agenda sera synchronisé et le patient sera notifié.</p>
                <form method="POST" action="{{ route('appointment-requests.reschedule', $appointmentRequest) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="date_souhaitee" class="block text-sm font-semibold text-gray-700">Nouvelle date</label>
                        <input id="date_souhaitee" name="date_souhaitee" type="date" required
                               min="{{ now()->toDateString() }}"
                               max="{{ now()->addDays(config('appointments.booking_horizon_days'))->toDateString() }}"
                               value="{{ old('date_souhaitee', $appointmentRequest->date_souhaitee->format('Y-m-d')) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 bg-white">
                    </div>
                    <div>
                        <label for="heure_souhaitee" class="block text-sm font-semibold text-gray-700">Nouvelle heure</label>
                        <input id="heure_souhaitee" name="heure_souhaitee" type="time" required
                               min="{{ config('appointments.opening_time') }}"
                               max="{{ config('appointments.closing_time') }}"
                               step="{{ max(10, (int) config('appointments.slot_duration_minutes')) * 60 }}"
                               value="{{ old('heure_souhaitee', substr($appointmentRequest->heure_souhaitee, 0, 5)) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 bg-white">
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 font-semibold text-white hover:bg-blue-900 sm:col-span-2">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i> Enregistrer le nouveau créneau
                    </button>
                </form>
            </div>
        </section>
    @endif
</main>
@endsection
