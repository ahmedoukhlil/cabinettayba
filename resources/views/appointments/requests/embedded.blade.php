@extends('layouts.embedded')

@section('title', 'Demandes de rendez-vous')

@section('content')
    @include('appointments.requests.partials.content', ['embedded' => true])
@endsection
