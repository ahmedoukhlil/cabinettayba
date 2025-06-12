@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Bienvenue sur Medipole</h1>
        <p class="text-lg text-gray-600">Gérez efficacement votre cabinet avec nos outils intégrés</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Carte Rendez-vous -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Rendez-vous</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-calendar-check text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Gérez les rendez-vous de vos patients de manière efficace</p>
                <div class="space-y-4">
                    <a href="{{ route('rendez-vous.create') }}" class="block w-full text-center bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors duration-300">
                        <i class="fas fa-plus mr-2"></i>Nouveau rendez-vous
                    </a>
                    <a href="{{ route('rendez-vous') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                        <i class="fas fa-list mr-2"></i>Voir tous les rendez-vous
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Patients -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Patients</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-users text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Gérez votre base de données de patients</p>
                <div class="space-y-4">
                    <a href="{{ route('patients.index') }}" class="block w-full text-center bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors duration-300">
                        <i class="fas fa-user-plus mr-2"></i>Nouveau patient
                    </a>
                    <a href="{{ route('patients.index') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                        <i class="fas fa-list mr-2"></i>Liste des patients
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Caisse -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Caisse</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-cash-register text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Gérez les opérations de caisse et les paiements</p>
                <div class="space-y-4">
                    <button disabled class="block w-full text-center bg-purple-500 text-white py-2 px-4 rounded-lg opacity-50 cursor-not-allowed">
                        <i class="fas fa-plus mr-2"></i>Nouvelle opération
                    </button>
                    <a href="{{ route('caisse-operations') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                        <i class="fas fa-history mr-2"></i>Historique des opérations
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Règlement Factures -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Règlement Factures</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-file-invoice-dollar text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Gérez les règlements des factures de vos patients</p>
                <div class="space-y-4">
                    <a href="{{ route('reglement-facture') }}" class="block w-full text-center bg-yellow-500 text-white py-2 px-4 rounded-lg hover:bg-yellow-600 transition-colors duration-300">
                        <i class="fas fa-money-bill-wave mr-2"></i>Régler une facture
                    </a>
                </div>
            </div>
        </div>

        @if(auth()->user() && auth()->user()->IdClasseUser == 3)
        <!-- Carte Utilisateurs (visible uniquement pour le propriétaire) -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Utilisateurs</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-user-cog text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Gérez les utilisateurs du système</p>
                <div class="space-y-4">
                    <a href="{{ route('users.index') }}" class="block w-full text-center bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors duration-300">
                        <i class="fas fa-user-plus mr-2"></i>Nouvel utilisateur
                    </a>
                    <a href="{{ route('users.index') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                        <i class="fas fa-users-cog mr-2"></i>Gérer les utilisateurs
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Carte Statistiques -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Statistiques</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Consultez les statistiques de votre cabinet</p>
                <div class="space-y-4">
                    <a href="{{ route('dashboard') }}" class="block w-full text-center bg-indigo-500 text-white py-2 px-4 rounded-lg hover:bg-indigo-600 transition-colors duration-300">
                        <i class="fas fa-chart-bar mr-2"></i>Tableau de bord
                    </a>
                    <a href="{{ route('finances.global') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>Rapports financiers
                    </a>
                </div>
            </div>
        </div>

        <!-- Carte Consultation -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
            <div class="bg-gradient-to-r from-blue-700 to-blue-500 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-white">Consultation</h2>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="fas fa-notes-medical text-2xl text-white"></i>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">Créer une nouvelle consultation pour un patient</p>
                <div class="space-y-4">
                    <a href="/consultations/create" class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300">
                        <i class="fas fa-plus mr-2"></i>Créer une consultation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush 