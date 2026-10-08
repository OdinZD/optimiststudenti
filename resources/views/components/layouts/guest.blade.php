<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Prijava · Optimist' }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <main class="w-full max-w-[400px]">
        <div class="flex items-center justify-center gap-2.5 mb-6">
            <span class="w-8 h-8 rounded-lg bg-[var(--acc)] inline-flex items-center justify-center shrink-0">
                <span class="w-3.5 h-3.5 rounded-full border-2 border-white"></span>
            </span>
            <span class="font-display text-[22px] font-bold text-[var(--ink)]">Optimist</span>
        </div>

        {{ $slot }}
    </main>
</body>
</html>
