<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Optimist') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="h-[60px] bg-[var(--surface)] border-b border-[var(--line)]">
        <div class="mx-auto max-w-[1344px] h-full flex items-center gap-2.5 px-8">
            <span class="w-7 h-7 rounded-lg bg-[var(--acc)] inline-flex items-center justify-center shrink-0">
                <span class="w-3 h-3 rounded-full border-2 border-white"></span>
            </span>
            <span class="font-display text-[20px] font-bold text-[var(--ink)]">Optimist</span>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button type="submit"
                            class="h-9 px-3 rounded-[var(--r-control)] text-[14px] font-semibold text-[var(--ink2)] transition hover:bg-[var(--bg)]">
                        Odjava
                    </button>
                </form>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-[1344px] px-8 pt-7 pb-10">
        {{ $slot }}
    </main>

    {{-- Toast (SPEC §5.6) — save confirmations; delete uses its own undo toast. --}}
    <div x-data="{ show: false, msg: '', timer: null }"
         x-on:toast.window="msg = $event.detail.message; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 4000)"
         x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed left-1/2 -translate-x-1/2 bottom-6 z-[60]" role="status" aria-live="polite">
        <div class="rounded-[var(--r-card)] px-4 py-3 text-[14px] font-medium text-white" style="background:var(--ink);box-shadow:var(--shadow-toast)">
            <span x-text="msg"></span>
        </div>
    </div>
</body>
</html>
