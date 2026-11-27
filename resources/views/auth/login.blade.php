<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — {{ $cabinetBrand['name'] }}</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh; margin: 0; padding: 1rem;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f8fafc 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }
        .login-card {
            width: 100%; max-width: 25rem; background: #fff; border-radius: 1.25rem;
            box-shadow: 0 20px 60px rgba(30, 58, 138, .12); overflow: hidden;
        }
        :root { --color-primary: {{ $cabinetBrand['primary_color'] }}; }
        .login-header { background: var(--color-primary, #1e3a8a); padding: 2rem 1.5rem 1.75rem; text-align: center; }
        .login-logo {
            width: 4.5rem; height: 4.5rem; margin: 0 auto .875rem; border-radius: 1rem;
            background: #fff; color: var(--color-primary, #1e3a8a); display: flex;
            align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(0, 0, 0, .12);
        }
        .login-header h1 { color: #fff; font-size: 1.375rem; font-weight: 700; margin: 0 0 .25rem; }
        .login-header p { color: rgba(255,255,255,.72); font-size: .75rem; margin: 0; }
        .login-body { padding: 1.75rem; }
        .field-label { display: block; color: #4b5563; font-size: .75rem; font-weight: 600; margin-bottom: .375rem; }
        .field-wrap { position: relative; margin-bottom: 1rem; }
        .field-wrap > i { position: absolute; left: .875rem; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
        .field-wrap input {
            width: 100%; padding: .75rem 2.5rem; border: 1.5px solid #e5e7eb;
            border-radius: .625rem; background: #f9fafb; color: #111827; font-size: 1rem; outline: none;
        }
        .field-wrap input:focus { background: #fff; border-color: var(--color-primary, #1e3a8a); box-shadow: 0 0 0 3px rgba(30,58,138,.1); }
        .toggle-password {
            position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
            color: #9ca3af; padding: .375rem; border: 0; background: transparent; cursor: pointer;
        }
        .login-footer { margin-top: 1.25rem; text-align: center; color: #9ca3af; font-size: .75rem; }
        @media (min-width: 480px) { .login-body { padding: 2rem; } }
    </style>
</head>
<body>
    <main class="login-card">
        <header class="login-header">
            <div class="login-logo"><img src="{{ $cabinetBrand['logo_url'] }}" alt="" style="width:3.5rem;height:3.5rem;object-fit:contain"></div>
            <h1>{{ $cabinetBrand['name'] }}</h1>
            <p>Accès sécurisé à votre espace professionnel</p>
        </header>

        <div class="login-body">
            @if($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert" aria-live="assertive">
                    <i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i>{{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <label for="login" class="field-label">Identifiant</label>
                <div class="field-wrap">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" placeholder="Votre identifiant">
                </div>

                <label for="password" class="field-label">Mot de passe</label>
                <div class="field-wrap">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Votre mot de passe">
                    <button type="button" class="toggle-password" aria-label="Afficher le mot de passe" onclick="togglePassword(this)">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>

                <label class="mb-5 flex items-center gap-2 text-xs text-gray-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                    <span>Se souvenir de moi</span>
                </label>

                <button type="submit" class="btn-primary w-full justify-center py-3">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Se connecter
                </button>
                <a href="{{ route('public.appointment.create') }}" class="btn-secondary mt-3 w-full justify-center py-3">
                    <i class="fas fa-calendar-plus" aria-hidden="true"></i> Prendre rendez-vous
                </a>
            </form>

            <p class="login-footer">© {{ date('Y') }} {{ $cabinetBrand['name'] }}</p>
        </div>
    </main>

    <script>
        function togglePassword(button) {
            const input = document.getElementById('password');
            const icon = button.querySelector('i');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            icon.className = visible ? 'fas fa-eye' : 'fas fa-eye-slash';
            button.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        }
    </script>
</body>
</html>
