(() => {
  const config = window.PWA_CONFIG || {};
  const labels = config.locale === 'ar'
    ? { install: 'تثبيت التطبيق', enable: 'تفعيل الإشعارات', disable: 'إيقاف الإشعارات', enabled: 'تم تفعيل الإشعارات', denied: 'يجب السماح بالإشعارات من إعدادات المتصفح.', error: 'تعذر تفعيل الإشعارات.' }
    : { install: 'Installer l’application', enable: 'Activer les notifications', disable: 'Désactiver les notifications', enabled: 'Notifications activées', denied: 'Autorisez les notifications dans les réglages du navigateur.', error: 'Impossible d’activer les notifications.' };
  let installPrompt = null;
  let registration = null;
  const subscriptionStorageKey = config.subscriptionId ? `tayba-push:${config.subscriptionId}` : null;

  const installButtons = [...document.querySelectorAll('[data-pwa-install]')];
  const notificationButtons = [...document.querySelectorAll('[data-pwa-notifications]')];
  const notify = message => window.showToast ? window.showToast(message, 'info') : alert(message);
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

  const updateNotificationButtons = enabled => notificationButtons.forEach(button => {
    button.hidden = false;
    button.dataset.enabled = enabled ? 'true' : 'false';
    button.textContent = enabled ? labels.disable : labels.enable;
  });

  const initialise = async () => {
    if (!('serviceWorker' in navigator)) return;
    try {
      registration = await navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
      await navigator.serviceWorker.ready;
      if (config.subscribeUrl && config.vapidPublicKey && 'PushManager' in window) {
        const existing = await registration.pushManager.getSubscription();
        updateNotificationButtons(Boolean(existing && subscriptionStorageKey && localStorage.getItem(subscriptionStorageKey) === '1'));
      }
    } catch (error) {
      console.error('PWA registration failed', error);
    }
  };

  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    if (!standalone) installButtons.forEach(button => { button.hidden = false; button.textContent = labels.install; });
  });

  window.addEventListener('appinstalled', () => installButtons.forEach(button => { button.hidden = true; }));

  if (!standalone && /iphone|ipad|ipod/i.test(navigator.userAgent)) {
    installButtons.forEach(button => { button.hidden = false; button.textContent = labels.install; });
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
    if (!registration) return notify(labels.error);
    button.disabled = true;
    try {
      const existing = await registration.pushManager.getSubscription();
      const enabledForThisContext = existing && subscriptionStorageKey && localStorage.getItem(subscriptionStorageKey) === '1';
      if (enabledForThisContext) {
        const response = await request('DELETE', { endpoint: existing.endpoint });
        if (!response.ok) throw new Error('unsubscribe failed');
        localStorage.removeItem(subscriptionStorageKey);
        updateNotificationButtons(false);
        return;
      }

      const permission = Notification.permission === 'granted'
        ? 'granted'
        : await Notification.requestPermission();
      if (permission !== 'granted') return notify(labels.denied);
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
      updateNotificationButtons(true);
      notify(labels.enabled);
    } catch (error) {
      console.error(error);
      notify(labels.error);
    } finally {
      button.disabled = false;
    }
  }));

  initialise();
})();
