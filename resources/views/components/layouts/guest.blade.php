<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Prijava · Optimist' }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa')
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <main class="w-full max-w-[400px]">
        <div class="flex items-center justify-center gap-2.5 mb-6">
            <span class="w-8 h-8 rounded-lg bg-[var(--acc)] inline-flex items-center justify-center shrink-0">
                <span class="w-3.5 h-3.5 rounded-full border-2 border-white"></span>
            </span>
            <span class="font-display text-[22px] font-bold text-[var(--ink)]">Optimist</span>
        </div>

        {{-- PWA install bar — appears only when Chrome reports the app is installable (Chromium/Android). --}}
        <div x-data="{ show: !! window.deferredInstallPrompt }"
             x-on:pwa-installable.window="show = true" x-on:pwa-installed.window="show = false"
             x-show="show" x-cloak class="mb-4">
            <button type="button"
                    x-on:click="const p = window.deferredInstallPrompt; if (! p) return; p.prompt(); p.userChoice.finally(() => { window.deferredInstallPrompt = null; show = false; });"
                    class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:opacity-90">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                Instaliraj Optimist — vođenje kluba
            </button>
        </div>

        {{ $slot }}
    </main>
</body>
</html>
