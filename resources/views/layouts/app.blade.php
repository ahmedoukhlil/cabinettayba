<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $cabinetBrand['primary_color'] }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="{{ route('branding.manifest', 'staff') }}">
    <link rel="icon" href="{{ $cabinetBrand['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $cabinetBrand['logo_url'] }}">
    <title>@yield('title', $cabinetBrand['name'])</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
    @php
        $staffPwaConfig = [
            'locale' => 'fr',
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'subscribeUrl' => auth()->check() ? route('pwa.notifications.store') : null,
            'subscriptionId' => auth()->check() ? 'staff:' . auth()->id() : null,
        ];
    @endphp
    <script>
        window.PWA_CONFIG = @json($staffPwaConfig);
    </script>
    <script src="{{ asset('js/pwa.js') }}?v={{ filemtime(public_path('js/pwa.js')) }}" defer></script>
    <style>
        :root{--color-primary:{{ $cabinetBrand['primary_color'] }};--color-primary-rgb:{{ $cabinetBrand['primary_rgb'] }};--color-primary-dark:color-mix(in srgb, {{ $cabinetBrand['primary_color'] }} 78%, #000);--color-primary-light:color-mix(in srgb, {{ $cabinetBrand['primary_color'] }} 10%, #fff);--color-secondary:{{ $cabinetBrand['secondary_color'] }}}
        .pwa-actions{position:fixed;right:1rem;bottom:1rem;z-index:60;display:flex;flex-direction:column;gap:.5rem;align-items:flex-end}
        .pwa-action{border:0;border-radius:.75rem;background:#1e3a8a;color:#fff;padding:.7rem 1rem;font-weight:700;box-shadow:0 8px 20px rgba(15,23,42,.2);cursor:pointer}
        .pwa-action[data-enabled="true"]{background:#475569}.pwa-action:disabled{opacity:.6;cursor:wait}
    </style>
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen text-gray-900 antialiased">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center">
                        <a href="{{ route('accueil.patient') }}" class="flex items-center" aria-label="Accueil {{ $cabinetBrand['name'] }}">
                            <span class="h-10 w-10 mr-3 rounded-xl bg-primary-light text-primary inline-flex items-center justify-center" aria-hidden="true">
                                <img src="{{ $cabinetBrand['logo_url'] }}" alt="" class="h-8 w-8 object-contain">
                            </span>
                            <span class="text-lg sm:text-xl font-bold text-primary">{{ $cabinetBrand['name'] }}</span>
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
    <main class="py-4">
        @yield('content')
    </main>

    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <div class="pwa-actions" aria-label="Application mobile">
        <button type="button" class="pwa-action" data-pwa-install hidden>Installer l’application</button>
        @auth
            <button type="button" class="pwa-action" data-pwa-notifications hidden>Activer les notifications</button>
        @endauth
    </div>

    @livewireScripts

    <script>
        window.showToast = function(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const icons = { success:'fa-check-circle', error:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('toast-hide');
                setTimeout(() => toast.remove(), 220);
            }, duration);
        };

        document.addEventListener('livewire:load', function() {
            Livewire.on('toast', payload => window.showToast(payload.message, payload.type || 'info'));
        });

        window.confirmAction = function(title, message, onConfirm) {
            document.getElementById('_confirm_dialog')?.remove();
            const overlay = document.createElement('div');
            overlay.id = '_confirm_dialog';
            overlay.className = 'confirm-dialog-overlay';
            overlay.innerHTML = `
                <div class="confirm-dialog-box" role="dialog" aria-modal="true" aria-labelledby="_cd_title">
                    <h3 id="_cd_title">${title}</h3>
                    ${message ? `<p>${message}</p>` : ''}
                    <div class="confirm-dialog-actions">
                        <button id="_cd_cancel" class="btn-secondary text-sm">Annuler</button>
                        <button id="_cd_ok" class="btn-danger text-sm">Confirmer</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
            const close = () => overlay.remove();
            overlay.querySelector('#_cd_cancel').addEventListener('click', close);
            overlay.addEventListener('click', event => { if (event.target === overlay) close(); });
            overlay.querySelector('#_cd_ok').addEventListener('click', () => {
                close();
                if (typeof onConfirm === 'function') onConfirm();
            });
            overlay.querySelector('#_cd_cancel').focus();
        };
    </script>

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
