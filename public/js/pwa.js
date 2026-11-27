(() => {
  const config = window.PWA_CONFIG || {};
  const labels = config.locale === 'ar'
    ? { install: 'تثبيت التطبيق', enable: 'تفعيل الإشعارات', disable: 'إيقاف الإشعارات', enabled: 'تم تفعيل الإشعارات', denied: 'يجب السماح بالإشعارات من إعدادات المتصفح.', error: 'تعذر تفعيل الإشعارات.', promptTitle: 'فعّل إشعارات المواعيد', promptBody: 'اسمح بالإشعارات لتلقي طلبات المواعيد وتحديثاتها.', later: 'لاحقاً' }
    : { install: 'Installer l’application', enable: 'Activer les notifications', disable: 'Désactiver les notifications', enabled: 'Notifications activées', denied: 'Autorisez les notifications dans les réglages du navigateur.', error: 'Impossible d’activer les notifications.', promptTitle: 'Autoriser les notifications', promptBody: 'Recevez les nouvelles demandes de rendez-vous et leurs mises à jour.', later: 'Plus tard' };
  let installPrompt = null;
  let registration = null;
  let reloadingForServiceWorkerUpdate = false;
  const subscriptionStorageKey = config.subscriptionId ? `cabinet-push:${config.subscriptionId}` : null;
  const subscriptionSyncStorageKey = subscriptionStorageKey ? `${subscriptionStorageKey}:synced-at` : null;
  const SUBSCRIPTION_SYNC_INTERVAL = 6 * 60 * 60 * 1000;

  const installButtons = [...document.querySelectorAll('[data-pwa-install]')];
  const notificationButtons = [...document.querySelectorAll('[data-pwa-notifications]')];
  const actionGroups = [...document.querySelectorAll('[data-pwa-actions]')];

  const syncActionGroups = () => actionGroups.forEach(group => {
    const actionButtons = [...group.querySelectorAll('[data-pwa-install], [data-pwa-notifications]')];
    const hasVisibleAction = actionButtons.some(button => !button.hidden);
    const shouldHide = !hasVisibleAction || Boolean(document.querySelector('.modal-overlay'));
    if (group.hidden !== shouldHide) group.hidden = shouldHide;
    if (shouldHide) {
      const menu = group.querySelector('[data-pwa-actions-menu]');
      const toggle = group.querySelector('[data-pwa-actions-toggle]');
      if (menu && !menu.hidden) menu.hidden = true;
      toggle?.setAttribute('aria-expanded', 'false');
    }
  });

  actionGroups.forEach(group => {
    const toggle = group.querySelector('[data-pwa-actions-toggle]');
    const menu = group.querySelector('[data-pwa-actions-menu]');
    toggle?.addEventListener('click', () => {
      if (!menu) return;
      menu.hidden = !menu.hidden;
      toggle.setAttribute('aria-expanded', String(!menu.hidden));
    });
  });

  document.addEventListener('click', event => {
    actionGroups.forEach(group => {
      if (group.contains(event.target)) return;
      const menu = group.querySelector('[data-pwa-actions-menu]');
      const toggle = group.querySelector('[data-pwa-actions-toggle]');
      if (menu && !menu.hidden) menu.hidden = true;
      toggle?.setAttribute('aria-expanded', 'false');
    });
  });

  new MutationObserver(syncActionGroups).observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['hidden']
  });
  const notify = (message, type = 'info') => {
    if (window.showToast) window.showToast(message, type);
  };
  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

  const base64ToUint8Array = value => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const raw = atob((value + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map(char => char.charCodeAt(0)));
  };

  const subscriptionPayload = subscription => {
    const json = subscription.toJSON();
    return {
      endpoint: json.endpoint,
      keys: json.keys,
      contentEncoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm'
    };
  };

  const request = (method, body) => fetch(config.subscribeUrl, {
    method,
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    },
    body: JSON.stringify(body)
  });

  const updateNotificationButtons = enabled => {
    notificationButtons.forEach(button => {
      button.hidden = false;
      button.dataset.enabled = enabled ? 'true' : 'false';
      button.textContent = enabled ? labels.disable : labels.enable;
    });
    syncActionGroups();
  };

  const hideNotificationOnboarding = () => document.getElementById('pwa-notification-onboarding')?.remove();

  const showNotificationOnboarding = () => {
    if (!config.subscribeUrl || !config.vapidPublicKey || Notification.permission === 'denied') return;
    if (subscriptionStorageKey && localStorage.getItem(subscriptionStorageKey) === '1') return;
    if (document.getElementById('pwa-notification-onboarding')) return;

    const panel = document.createElement('div');
    panel.id = 'pwa-notification-onboarding';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.style.cssText = 'position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.62)';
    panel.innerHTML = `
      <div style="width:min(100%,26rem);border-radius:1rem;background:#fff;padding:1.5rem;box-shadow:0 24px 60px rgba(15,23,42,.3);text-align:${config.locale === 'ar' ? 'right' : 'left'}" dir="${config.locale === 'ar' ? 'rtl' : 'ltr'}">
        <div style="font-size:2rem;line-height:1" aria-hidden="true">🔔</div>
        <h2 style="margin:.8rem 0 .4rem;color:#0f172a;font-size:1.25rem;font-weight:800">${labels.promptTitle}</h2>
        <p style="margin:0;color:#475569;line-height:1.55">${labels.promptBody}</p>
        <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.25rem;flex-wrap:wrap">
          <button type="button" data-notification-later style="border:1px solid #cbd5e1;border-radius:.7rem;background:#fff;color:#334155;padding:.7rem 1rem;font-weight:700;cursor:pointer">${labels.later}</button>
          <button type="button" data-notification-allow style="border:0;border-radius:.7rem;background:#1e3a8a;color:#fff;padding:.7rem 1rem;font-weight:700;cursor:pointer">${labels.enable}</button>
        </div>
      </div>`;
    document.body.appendChild(panel);
    panel.querySelector('[data-notification-later]').addEventListener('click', hideNotificationOnboarding);
    panel.querySelector('[data-notification-allow]').addEventListener('click', event => {
      hideNotificationOnboarding();
      notificationButtons[0]?.click();
      event.currentTarget.disabled = true;
    });
  };

  const initialise = async () => {
    if (!('serviceWorker' in navigator)) return;
    try {
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloadingForServiceWorkerUpdate) return;
        reloadingForServiceWorkerUpdate = true;
        window.location.reload();
      });

      registration = await navigator.serviceWorker.register('/service-worker.js', {
        scope: '/',
        updateViaCache: 'none'
      });
      await registration.update();
      await navigator.serviceWorker.ready;
      if (config.subscribeUrl && config.vapidPublicKey && 'PushManager' in window) {
        const existing = await registration.pushManager.getSubscription();
        let enabled = Boolean(existing && subscriptionStorageKey && localStorage.getItem(subscriptionStorageKey) === '1');

        // Une base tenant peut être restaurée ou déplacée alors que le navigateur
        // conserve encore son abonnement. Réinscrire périodiquement l'abonnement
        // évite un faux état « activé » côté PWA sans ligne correspondante en base.
        if (enabled && subscriptionSyncStorageKey) {
          const lastSync = Number(localStorage.getItem(subscriptionSyncStorageKey) || 0);
          if (!lastSync || Date.now() - lastSync >= SUBSCRIPTION_SYNC_INTERVAL) {
            const response = await request('POST', subscriptionPayload(existing));
            enabled = response.ok;
            if (enabled) {
              localStorage.setItem(subscriptionSyncStorageKey, String(Date.now()));
            } else {
              localStorage.removeItem(subscriptionStorageKey);
              localStorage.removeItem(subscriptionSyncStorageKey);
            }
          }
        }

        updateNotificationButtons(enabled);
        if (standalone && !enabled) window.setTimeout(showNotificationOnboarding, 500);
      }
    } catch (error) {
      console.error('PWA registration failed', error);
    }
  };

  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    if (!standalone) installButtons.forEach(button => { button.hidden = false; button.textContent = labels.install; });
    syncActionGroups();
  });

  window.addEventListener('appinstalled', () => {
    installButtons.forEach(button => { button.hidden = true; });
    syncActionGroups();
    window.setTimeout(showNotificationOnboarding, 300);
  });

  if (!standalone && /iphone|ipad|ipod/i.test(navigator.userAgent)) {
    installButtons.forEach(button => { button.hidden = false; button.textContent = labels.install; });
    syncActionGroups();
  }

  installButtons.forEach(button => button.addEventListener('click', async () => {
    if (installPrompt) {
      await installPrompt.prompt();
      installPrompt = null;
    } else if (/iphone|ipad|ipod/i.test(navigator.userAgent)) {
      notify(config.locale === 'ar' ? 'اضغط مشاركة ثم «إضافة إلى الشاشة الرئيسية».' : 'Touchez Partager puis « Sur l’écran d’accueil ».');
    }
  }));

  notificationButtons.forEach(button => button.addEventListener('click', async () => {
    if (!registration) return notify(labels.error, 'error');
    button.disabled = true;
    try {
      const existing = await registration.pushManager.getSubscription();
      const enabledForThisContext = existing && subscriptionStorageKey && localStorage.getItem(subscriptionStorageKey) === '1';
      if (enabledForThisContext) {
        const response = await request('DELETE', { endpoint: existing.endpoint });
        if (!response.ok) throw new Error('unsubscribe failed');
        localStorage.removeItem(subscriptionStorageKey);
        if (subscriptionSyncStorageKey) localStorage.removeItem(subscriptionSyncStorageKey);
        updateNotificationButtons(false);
        return;
      }

      const permission = Notification.permission === 'granted'
        ? 'granted'
        : await Notification.requestPermission();
      if (permission !== 'granted') return notify(labels.denied, 'warning');
      let subscription = existing;
      let createdSubscription = false;
      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: base64ToUint8Array(config.vapidPublicKey)
          });
        createdSubscription = true;
      }
      const response = await request('POST', subscriptionPayload(subscription));
      if (!response.ok) {
        if (createdSubscription) await subscription.unsubscribe();
        throw new Error('subscribe failed');
      }
      if (subscriptionStorageKey) localStorage.setItem(subscriptionStorageKey, '1');
      if (subscriptionSyncStorageKey) localStorage.setItem(subscriptionSyncStorageKey, String(Date.now()));
      updateNotificationButtons(true);
      notify(labels.enabled, 'success');
    } catch (error) {
      console.error(error);
      notify(labels.error, 'error');
    } finally {
      button.disabled = false;
    }
  }));

  syncActionGroups();
  initialise();
})();
