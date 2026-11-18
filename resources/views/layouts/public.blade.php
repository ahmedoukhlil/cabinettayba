<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <meta name="referrer" content="strict-origin">
    <title>@yield('title', 'Réservation') - Cabinet Tayba</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @php($publicCabinet = $cabinet ?? optional($appointmentRequest ?? null)->cabinet)
    <header class="border-b border-blue-100 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('public.appointment.create') }}" class="flex items-center gap-3 text-[#1e3a8a]">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-100" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3c-2.4-2-7-1.2-8 2.3C2.8 9 5.2 11.4 6 14.7 6.8 18 7.3 21 9 21c1.8 0 1.5-5.2 3-5.2s1.2 5.2 3 5.2c1.7 0 2.2-3 3-6.3.8-3.3 3.2-5.7 2-9.4C19 1.8 14.4 1 12 3Z"/>
                    </svg>
                </span>
                <span>
                    <strong class="block text-lg">{{ optional($publicCabinet)->NomCabFr ?: 'Cabinet Tayba' }}</strong>
                    <span class="block text-xs text-slate-500">Réservation en ligne</span>
                </span>
            </a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 rounded">
                Espace professionnel
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-6 text-center text-sm text-slate-500 sm:px-6">
            © {{ date('Y') }} {{ optional($publicCabinet)->NomCabFr ?: 'Cabinet Tayba' }} — Vos informations sont utilisées uniquement pour traiter votre demande.
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
