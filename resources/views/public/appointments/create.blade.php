@extends('layouts.public')

@section('title', 'Prendre rendez-vous')

@section('content')
<div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
    <section>
        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">Simple et sécurisé</span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Demandez votre rendez-vous</h1>
        <p class="mt-4 text-lg leading-8 text-slate-600">
            Choisissez un médecin et un créneau disponible. Le cabinet examinera votre demande avant de la confirmer.
        </p>

        <div class="mt-8 space-y-4 rounded-2xl border border-blue-100 bg-blue-50 p-5">
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">1</span>
                <p class="text-sm text-slate-700">Sélectionnez le créneau qui vous convient.</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">2</span>
                <p class="text-sm text-slate-700">Recevez une référence de suivi immédiatement.</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">3</span>
                <p class="text-sm text-slate-700">Le secrétariat confirme ensuite votre rendez-vous.</p>
            </div>
        </div>

        @if($cabinet && ($cabinet->TelephonePublic || $cabinet->AdresseFr1))
            <div class="mt-6 text-sm text-slate-600">
                @if($cabinet->TelephonePublic)<p><strong>Téléphone :</strong> {{ $cabinet->TelephonePublic }}</p>@endif
                @if($cabinet->AdresseFr1)<p class="mt-1"><strong>Adresse :</strong> {{ $cabinet->AdresseFr1 }}</p>@endif
            </div>
        @endif
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-xl ring-1 ring-slate-200 sm:p-8" aria-labelledby="booking-form-title">
        <h2 id="booking-form-title" class="text-xl font-bold text-slate-900">Informations de réservation</h2>

        @if($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert" aria-live="assertive">
                <p class="font-semibold">Certaines informations doivent être corrigées.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($medecins->isEmpty())
            <div class="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                La réservation en ligne est temporairement indisponible. Merci de contacter le cabinet.
            </div>
        @else
        <form method="POST" action="{{ route('public.appointment.store') }}" class="mt-6 space-y-5" id="booking-form">
            @csrf
            <div class="absolute -left-[10000px]" aria-hidden="true">
                <label for="website">Ne pas remplir ce champ</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="nom" class="block text-sm font-semibold text-slate-700">Nom <span class="text-red-600">*</span></label>
                    <input id="nom" name="nom" value="{{ old('nom') }}" required autocomplete="family-name" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label for="prenom" class="block text-sm font-semibold text-slate-700">Prénom</label>
                    <input id="prenom" name="prenom" value="{{ old('prenom') }}" autocomplete="given-name" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div>
                <label for="telephone" class="block text-sm font-semibold text-slate-700">Téléphone <span class="text-red-600">*</span></label>
                <input id="telephone" name="telephone" type="tel" value="{{ old('telephone') }}" required autocomplete="tel" inputmode="tel" placeholder="Ex. +222 12 34 56 78" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="acte_id" class="block text-sm font-semibold text-slate-700">Motif de la visite</label>
                <select id="acte_id" name="acte_id" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">Consultation</option>
                    @foreach($actes as $acte)
                        <option value="{{ $acte->ID }}" @selected(old('acte_id') == $acte->ID)>{{ $acte->Acte }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="medecin_id" class="block text-sm font-semibold text-slate-700">Médecin <span class="text-red-600">*</span></label>
                <select id="medecin_id" name="medecin_id" required class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="">Choisir un médecin</option>
                    @foreach($medecins as $medecin)
                        <option value="{{ $medecin->idMedecin }}" @selected(old('medecin_id') == $medecin->idMedecin)>Dr {{ $medecin->Nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="date" class="block text-sm font-semibold text-slate-700">Date <span class="text-red-600">*</span></label>
                    <input id="date" name="date" type="date" value="{{ old('date') }}" min="{{ $minDate }}" max="{{ $maxDate }}" required class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label for="heure" class="block text-sm font-semibold text-slate-700">Heure <span class="text-red-600">*</span></label>
                    <select id="heure" name="heure" required disabled class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">Choisissez d’abord le médecin et la date</option>
                    </select>
                    <p id="slot-status" class="mt-1.5 text-xs text-slate-500" aria-live="polite"></p>
                </div>
            </div>

            <div>
                <label for="message" class="block text-sm font-semibold text-slate-700">Message facultatif</label>
                <textarea id="message" name="message" rows="3" maxlength="1000" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Une précision utile pour le secrétariat">{{ old('message') }}</textarea>
            </div>

            <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                <span>J’accepte que mes informations soient utilisées par le cabinet pour traiter cette demande de rendez-vous.</span>
            </label>

            <button type="submit" id="submit-button" class="inline-flex w-full items-center justify-center rounded-lg bg-[#1e3a8a] px-5 py-3 font-semibold text-white shadow-sm hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                Envoyer ma demande
            </button>
        </form>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const doctor = document.getElementById('medecin_id');
    const date = document.getElementById('date');
    const time = document.getElementById('heure');
    const status = document.getElementById('slot-status');
    const form = document.getElementById('booking-form');
    const submit = document.getElementById('submit-button');
    if (!doctor || !date || !time) return;

    const previousTime = @json(old('heure'));

    async function loadSlots() {
        time.disabled = true;
        time.innerHTML = '<option value="">Chargement des créneaux…</option>';
        status.textContent = '';
        if (!doctor.value || !date.value) {
            time.innerHTML = '<option value="">Choisissez d’abord le médecin et la date</option>';
            return;
        }

        try {
            const params = new URLSearchParams({ medecin_id: doctor.value, date: date.value });
            const response = await fetch(`{{ route('public.appointment.availability') }}?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('availability');
            const data = await response.json();
            time.innerHTML = '<option value="">Choisir un créneau</option>';
            data.slots.forEach(slot => {
                const option = new Option(slot, slot, false, slot === previousTime);
                time.add(option);
            });
            time.disabled = data.slots.length === 0;
            status.textContent = data.slots.length
                ? `${data.slots.length} créneau(x) disponible(s).`
                : 'Aucun créneau disponible à cette date.';
        } catch (error) {
            time.innerHTML = '<option value="">Créneaux indisponibles</option>';
            status.textContent = 'Impossible de charger les créneaux. Réessayez dans un instant.';
        }
    }

    doctor.addEventListener('change', loadSlots);
    date.addEventListener('change', loadSlots);
    if (doctor.value && date.value) loadSlots();

    form?.addEventListener('submit', () => {
        submit.disabled = true;
        submit.textContent = 'Envoi en cours…';
    });
});
</script>
@endpush
