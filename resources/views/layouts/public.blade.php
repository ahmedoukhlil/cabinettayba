<!DOCTYPE html>
<html lang="@yield('lang', 'fr')" dir="@yield('dir', 'ltr')">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta name="referrer" content="strict-origin">
    <meta name="theme-color" content="{{ $cabinetBrand['primary_color'] }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $cabinetBrand['pwa_name'] }}">
    <link rel="manifest" href="{{ route('branding.manifest', 'patient') }}">
    <link rel="icon" href="{{ $cabinetBrand['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $cabinetBrand['logo_url'] }}">
    <title>@yield('title', 'Réservation') - {{ $cabinetBrand['name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $publicPwaConfig = [
            'locale' => trim($__env->yieldContent('lang', 'fr')),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'subscribeUrl' => trim($__env->yieldContent('pwa_subscribe_url')) ?: null,
            'subscriptionId' => trim($__env->yieldContent('pwa_subscription_id')) ?: null,
            'appName' => $cabinetBrand['pwa_name'],
            'logoUrl' => $cabinetBrand['logo_url'],
        ];
    @endphp
    <script>
        window.PWA_CONFIG = @json($publicPwaConfig);
    </script>
    <script src="{{ asset('js/pwa.js') }}?v={{ filemtime(public_path('js/pwa.js')) }}" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" style="font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif">
    @php
        $isRtl = trim($__env->yieldContent('dir', 'ltr')) === 'rtl';
        $publicCabinetName = $isRtl ? $cabinetBrand['name_ar'] : $cabinetBrand['name'];
    @endphp
    <header class="border-b border-blue-100 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('public.appointment.create') }}" class="flex items-center gap-3" style="color:{{ $cabinetBrand['primary_color'] }}">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-100" aria-hidden="true">
                    <img src="{{ $cabinetBrand['logo_url'] }}" alt="" class="h-9 w-9 object-contain">
                </span>
                <span>
                    <strong class="block text-lg">{{ $publicCabinetName }}</strong>
                    <span class="block text-xs text-slate-500">@yield('header_subtitle', 'Réservation en ligne')</span>
                </span>
            </a>
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded text-sm font-medium text-slate-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600">
                @yield('professional_label', 'Espace professionnel')
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-6 text-center text-sm text-slate-500 sm:px-6">
            © {{ date('Y') }} {{ $publicCabinetName }} — @yield('privacy_text', 'Vos informations sont utilisées uniquement pour traiter votre demande.')
        </div>
    </footer>
    <div id="toast-container" class="public-toast-container" aria-live="polite" aria-atomic="false"></div>
    <div class="pwa-actions" data-pwa-actions hidden aria-label="تطبيق الهاتف">
        <button type="button" class="pwa-actions-toggle" data-pwa-actions-toggle aria-expanded="false" aria-label="خيارات التطبيق">
            <span aria-hidden="true">🔔</span>
        </button>
        <div class="pwa-actions-menu" data-pwa-actions-menu hidden>
            <button type="button" data-pwa-install hidden class="pwa-action">تثبيت التطبيق</button>
            @hasSection('pwa_subscribe_url')
                <button type="button" data-pwa-notifications hidden class="pwa-action">تفعيل الإشعارات</button>
            @endif
        </div>
    </div>
    <script>
        window.showToast = function(message, type = 'info', duration = 4200) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const labels = {
                success: @json(trim($__env->yieldContent('lang', 'fr')) === 'ar' ? 'تم بنجاح' : 'Succès'),
                error: @json(trim($__env->yieldContent('lang', 'fr')) === 'ar' ? 'حدث خطأ' : 'Erreur'),
                warning: @json(trim($__env->yieldContent('lang', 'fr')) === 'ar' ? 'تنبيه' : 'Attention'),
                info: @json(trim($__env->yieldContent('lang', 'fr')) === 'ar' ? 'معلومة' : 'Information')
            };
            const symbols = { success: '✓', error: '!', warning: '!', info: 'i' };
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} public-toast`;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

            const icon = document.createElement('span');
            icon.className = 'public-toast-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = symbols[type] || symbols.info;

            const content = document.createElement('span');
            content.className = 'public-toast-content';
            const title = document.createElement('strong');
            title.textContent = labels[type] || labels.info;
            const body = document.createElement('span');
            body.textContent = message;
            content.append(title, body);

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'public-toast-close';
            close.setAttribute('aria-label', trimDirection() === 'rtl' ? 'إغلاق' : 'Fermer');
            close.textContent = '×';

            const dismiss = () => {
                if (!toast.isConnected) return;
                toast.classList.add('toast-hide');
                window.setTimeout(() => toast.remove(), 220);
            };
            close.addEventListener('click', dismiss);
            toast.append(icon, content, close);
            container.appendChild(toast);
            window.setTimeout(dismiss, duration);
        };

        function trimDirection() {
            return document.documentElement.dir || 'ltr';
        }
    </script>
    @stack('scripts')
</body>
</html>
