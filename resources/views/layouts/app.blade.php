<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet Tayba</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header simplifié -->
    <header class="bg-white shadow-lg border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('accueil.patient') }}" class="flex items-center">
                        <x-logo />
                    </a>
                </div>

                <!-- Menu utilisateur -->
                <div class="flex items-center space-x-4">
                    @if(auth()->check())
                        <div class="relative group">
                            <button class="flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border border-gray-200">
                                <span class="font-medium">{{ auth()->user()->NomComplet ?? auth()->user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-chevron-down text-sm group-hover:text-blue-600 transition-colors duration-200"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 group-hover:opacity-100 transition-all duration-200 transform origin-top-right scale-95 group-hover:scale-100 z-50">
                                <div class="py-1">
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="block">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                                            Déconnexion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow">
                            Connexion
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex flex-col items-center justify-center py-8">
        <div class="w-full bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            @yield('content')
        </div>
    </main>

    @livewireScripts
</body>
</html>