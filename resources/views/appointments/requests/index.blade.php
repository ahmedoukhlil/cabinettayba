@extends('layouts.app')

@section('title', 'Demandes de rendez-vous')

@section('content')
    <div class="mx-auto max-w-7xl px-3 sm:px-4 md:px-6">
        @include('appointments.requests.partials.content', ['embedded' => false])
    </div>
@endsection
