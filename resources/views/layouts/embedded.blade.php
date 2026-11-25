<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cabinet Tayba')</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    @yield('content')

    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

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
</body>
</html>
