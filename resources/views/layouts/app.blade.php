<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cabinet Tayba')</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @livewireStyles
    <style>
        .text-primary { color: #1e3a8a !important; }
        .bg-primary { background-color: #1e3a8a !important; }
        .border-primary { border-color: #1e3a8a !important; }
        .hover\:bg-primary:hover { background-color: #1e3a8a !important; }
        .hover\:text-primary:hover { color: #1e3a8a !important; }
        .bg-primary-light { background-color: #e6eaf2 !important; }
        .text-primary-light { color: #e6eaf2 !important; }
        .text-primary-dark { color: #152a5c !important; }
        .hover\:text-white:hover { color: #fff !important; }
        .focus\:ring-primary:focus { outline: 2px solid transparent; box-shadow: 0 0 0 2px #1e3a8a; }
        
        [wire\:loading] { cursor: progress; }
        .nav-link { color: #4b5563; padding: .5rem 1rem; border-radius: .5rem; font-weight: 500; border: 1px solid #e5e7eb; }
        .nav-link:hover, .nav-link:focus { color: #2563eb; background: #eff6ff; outline: 2px solid transparent; }
        .nav-link.active { color: #2563eb; background: #eff6ff; border-color: #bfdbfe; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white shadow-lg border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="flex items-center">
                            <span class="h-10 w-10 mr-3 rounded-full bg-primary-light text-primary inline-flex items-center justify-center" aria-hidden="true">
                                <i class="fas fa-tooth text-xl"></i>
                            </span>
                            <span class="text-xl font-bold text-[#1e3a8a]">Cabinet Tayba</span>
                        </a>
                    </div>
                    <!-- Menu desktop -->
                    <div class="hidden md:flex space-x-4">
                        
                    </div>
                </div>

                <!-- Bouton menu mobile -->
                <div class="flex items-center md:hidden">
                    <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Menu utilisateur -->
                <div class="hidden md:flex items-center space-x-4">
                    @if(auth()->check())
                        <div class="relative group">
                            <button class="flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border border-gray-200" aria-haspopup="true" aria-label="Menu du compte utilisateur">
                                <span class="font-medium">{{ auth()->user()->NomComplet ?? auth()->user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-chevron-down text-sm group-hover:text-blue-600 transition-colors duration-200"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 invisible group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 transition-all duration-200 transform origin-top-right scale-95 group-hover:scale-100 group-focus-within:scale-100 z-50">
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

        <!-- Menu mobile -->
        <div id="mobile-menu" class="mobile-menu hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-2">
               
                
                @if(auth()->check())
                    <form id="mobile-logout-form" action="{{ route('logout') }}" method="POST" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                            Déconnexion
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4 sm:py-8">
        <div class="w-full bg-white sm:rounded-xl shadow-sm border-y sm:border border-gray-100 p-3 sm:p-6 mb-8">
            @yield('content')
        </div>
    </main>

    @livewireScripts

    <!-- Script pour le menu mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.querySelector('.mobile-menu-button');
            const menu = document.querySelector('.mobile-menu');

            if (!btn || !menu) return;

            btn.addEventListener('click', () => {
                const isOpen = !menu.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', String(isOpen));
                btn.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
            });
        });
    </script>

    <!-- Script impression modal paiement -->
    <script>
    document.addEventListener('imprimer-modal', function () {
        console.log('Événement imprimer-modal reçu !');
        let printModal = document.getElementById('historique-paiement-modal');
        if (!printModal) return;
        let printContents = printModal.innerHTML;
        let mywindow = window.open('', 'PRINT', 'height=800,width=1200');
        let styles = '';
        document.querySelectorAll('link[rel="stylesheet"]').forEach(function(node) {
            styles += node.outerHTML;
        });
        styles += `
            <style>
                body { background: #fff !important; color: #222 !important; font-family: Arial, sans-serif !important; font-size: 12px !important; line-height: 1.4 !important; margin: 0 !important; padding: 20px !important; }
                * { visibility: visible !important; display: initial !important; }
                table { width: 100% !important; border-collapse: collapse !important; margin-bottom: 20px !important; }
                th, td { border: 1px solid #000 !important; padding: 4px 8px !important; text-align: left !important; }
                th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .print\\:hidden { display: none !important; }
                @page { size: A4; margin: 10mm; }
            </style>
        `;
        mywindow.document.write('<html><head><title>Historique des paiements</title>');
        mywindow.document.write(styles);
        mywindow.document.write('</head><body>');
        mywindow.document.write(printContents);
        mywindow.document.write('</body></html>');
        mywindow.document.close();
        mywindow.focus();
        setTimeout(function() {
            mywindow.print();
            mywindow.close();
        }, 500);
    });
    </script>
</body>
</html>
