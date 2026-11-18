@extends('layouts.public')

@section('title', 'Suivi de la demande')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="mx-auto max-w-2xl rounded-2xl bg-white p-6 text-center shadow-xl ring-1 ring-slate-200 sm:p-10">
    @php
        $states = [
            'pending' => ['label' => 'En attente de confirmation', 'class' => 'bg-amber-100 text-amber-900', 'text' => 'Le secrétariat va examiner votre demande.'],
            'confirmed' => ['label' => 'Rendez-vous confirmé', 'class' => 'bg-green-100 text-green-900', 'text' => 'Votre rendez-vous a été ajouté au planning du cabinet.'],
            'rejected' => ['label' => 'Créneau non confirmé', 'class' => 'bg-red-100 text-red-900', 'text' => 'Ce créneau n’a pas pu être confirmé. Vous pouvez effectuer une nouvelle demande.'],
        ];
        $state = $states[$appointmentRequest->status] ?? $states['pending'];
    @endphp

    <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $state['class'] }}">{{ $state['label'] }}</span>
    <h1 class="mt-6 text-3xl font-bold text-slate-900">Demande enregistrée</h1>
    <p class="mt-3 text-slate-600">{{ $state['text'] }}</p>

    <dl class="mt-8 grid gap-4 rounded-xl bg-slate-50 p-5 text-left sm:grid-cols-2">
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Référence</dt><dd class="mt-1 font-bold text-slate-900">{{ $appointmentRequest->reference }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Date demandée</dt><dd class="mt-1 text-slate-900">{{ $appointmentRequest->date_souhaitee->format('d/m/Y') }} à {{ substr($appointmentRequest->heure_souhaitee, 0, 5) }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Médecin</dt><dd class="mt-1 text-slate-900">Dr {{ optional($appointmentRequest->medecin)->Nom }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase text-slate-500">Motif</dt><dd class="mt-1 text-slate-900">{{ optional($appointmentRequest->acte)->Acte ?? 'Consultation' }}</dd></div>
    </dl>

    <p class="mt-6 text-sm text-slate-500">Conservez cette page ou votre référence pour suivre la demande.</p>
    <a href="{{ route('public.appointment.create') }}" class="mt-6 inline-flex rounded-lg bg-[#1e3a8a] px-5 py-3 font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Nouvelle demande</a>
</div>
@endsection
