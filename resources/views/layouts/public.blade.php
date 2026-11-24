<!DOCTYPE html>
<html lang="@yield('lang', 'fr')" dir="@yield('dir', 'ltr')">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta name="referrer" content="strict-origin">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="{{ asset('manifest-patient.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/pwa-192.png') }}">
    <title>@yield('title', 'Réservation') - Cabinet Tayba</title>
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
    <script src="{{ asset('js/pwa.js') }}" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" style="font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif">
    @php
        $publicCabinet = $cabinet ?? optional($appointmentRequest ?? null)->cabinet;
        $isRtl = trim($__env->yieldContent('dir', 'ltr')) === 'rtl';
        $publicCabinetName = $isRtl
            ? (optional($publicCabinet)->NomCabAr ?: optional($publicCabinet)->NomCabFr ?: 'عيادة طيبة')
            : (optional($publicCabinet)->NomCabFr ?: 'Cabinet Tayba');
    @endphp
    <header class="border-b border-blue-100 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('public.appointment.create') }}" class="flex items-center gap-3 text-[#1e3a8a]">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-100" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3c-2.4-2-7-1.2-8 2.3C2.8 9 5.2 11.4 6 14.7 6.8 18 7.3 21 9 21c1.8 0 1.5-5.2 3-5.2s1.2 5.2 3 5.2c1.7 0 2.2-3 3-6.3.8-3.3 3.2-5.7 2-9.4C19 1.8 14.4 1 12 3Z"/>
                    </svg>
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
        <button type="button" data-pwa-install hidden class="rounded-xl bg-[#1e3a8a] px-4 py-3 font-semibold text-white shadow-lg">تثبيت التطبيق</button>
        @hasSection('pwa_subscribe_url')
            <button type="button" data-pwa-notifications hidden class="rounded-xl bg-[#1e3a8a] px-4 py-3 font-semibold text-white shadow-lg">تفعيل الإشعارات</button>
        @endif
    </div>
    @stack('scripts')
</body>
</html>
