{{-- PWA: installable on Android home screen. Static assets live in public/ (see public/sw.js). --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2D3E7B">
<link rel="icon" href="/icons/icon-192.png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<script>
    // Capture Chrome's install event so a custom "Instaliraj Optimist" button (login page) can
    // trigger the native dialog. Fires only on Chromium/Android when installable; never on iOS/Firefox.
    window.deferredInstallPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.deferredInstallPrompt = e;
        window.dispatchEvent(new CustomEvent('pwa-installable'));
    });
    window.addEventListener('appinstalled', function () {
        window.deferredInstallPrompt = null;
        window.dispatchEvent(new CustomEvent('pwa-installed'));
    });

    // Register the service worker only over HTTPS (production) so local http Vite/artisan-serve dev is untouched.
    if ('serviceWorker' in navigator && location.protocol === 'https:') {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js');
        });
    }
</script>
