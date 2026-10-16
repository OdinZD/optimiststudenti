{{-- PWA: installable on Android home screen. Static assets live in public/ (see public/sw.js). --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2D3E7B">
<link rel="icon" href="/icons/icon-192.png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<script>
    // Register only over HTTPS (production) so local http Vite/artisan-serve dev is untouched.
    if ('serviceWorker' in navigator && location.protocol === 'https:') {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js');
        });
    }
</script>
