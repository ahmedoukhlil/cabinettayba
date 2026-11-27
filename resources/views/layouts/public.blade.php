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
    <link rel="manifest" href="{{ route('branding.manifest', 'patient') }}">
    <link rel="icon" href="{{ $cabinetBrand['logo_url'] }}">
    <link rel="apple-touch-icon" href="{{ $cabinetBrand['logo_url'] }}">
    <title>@yield('title', 'Réservation') - {{ $cabinetBrand['name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @php
        $publicPwaConfig = [
            'locale' => trim($__env->yieldContent('lang', 'fr')),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'subscribeUrl' => trim($__env->yieldContent('pwa_subscribe_url')) ?: null,
            'subscriptionId' => trim($__env->yieldContent('pwa_subscription_id')) ?: null,
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
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 rounded">
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
    <div class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2" aria-label="تطبيق الهاتف">
        <button type="button" data-pwa-install hidden class="rounded-xl px-4 py-3 font-semibold text-white shadow-lg" style="background:{{ $cabinetBrand['primary_color'] }}">تثبيت التطبيق</button>
        @hasSection('pwa_subscribe_url')
            <button type="button" data-pwa-notifications hidden class="rounded-xl px-4 py-3 font-semibold text-white shadow-lg" style="background:{{ $cabinetBrand['primary_color'] }}">تفعيل الإشعارات</button>
        @endif
    </div>
    @stack('scripts')
</body>
</html>
