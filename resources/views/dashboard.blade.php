@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-1 gap-6 w-full">
            {{-- Première carte : finances du médecin et rendez-vous --}}
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                @livewire('medecin-paiement-stats')
            </div>
            
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush