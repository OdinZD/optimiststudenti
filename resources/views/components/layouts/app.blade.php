<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Optimist') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa')
</head>
<body>
    @auth
        @php($navItems = [
            ['route' => 'polaznici', 'label' => 'Polaznici'],
            ['route' => 'natjecanja', 'label' => 'Natjecanja'],
            ['route' => 'kata-timovi', 'label' => 'Kata timovi'],
        ])
    @endauth
    <header class="relative h-[60px] bg-[var(--surface)] border-b border-[var(--line)]" x-data="{ open: false }">
        <div class="mx-auto max-w-[1344px] h-full flex items-center gap-2.5 px-4 sm:px-8">
            <span class="w-7 h-7 rounded-lg bg-[var(--acc)] inline-flex items-center justify-center shrink-0">
                <span class="w-3 h-3 rounded-full border-2 border-white"></span>
            </span>
            <span class="font-display text-[20px] font-bold text-[var(--ink)]">Optimist</span>

            @auth
                {{-- Nav na širim ekranima --}}
                <nav class="hidden sm:flex items-center gap-1 ml-4 sm:ml-6 text-[14px] font-semibold">
                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                           @class(['px-3 h-9 inline-flex items-center rounded-[var(--r-control)] transition', 'bg-[var(--tint)] text-[var(--acc)]' => request()->routeIs($item['route']), 'text-[var(--ink2)] hover:bg-[var(--bg)]' => ! request()->routeIs($item['route'])])>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block ml-auto">
                    @csrf
                    <button type="submit"
                            class="h-9 px-3 rounded-[var(--r-control)] text-[14px] font-semibold text-[var(--ink2)] transition hover:bg-[var(--bg)]">
                        Odjava
                    </button>
                </form>

                {{-- Hamburger na mobitelu --}}
                <button type="button" x-on:click.stop="open = ! open" :aria-expanded="open" aria-controls="mobilni-izbornik" aria-label="Izbornik"
                        class="sm:hidden ml-auto w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] transition hover:bg-[var(--bg)]">
                    <svg x-show="! open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
                    <svg x-show="open" x-cloak width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            @endauth
        </div>

        @auth
            {{-- Padajući izbornik na mobitelu --}}
            <div id="mobilni-izbornik" x-show="open" x-cloak
                 x-on:click.outside="open = false" x-on:keydown.escape.window="open = false"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 class="sm:hidden absolute inset-x-0 top-[60px] z-50 bg-[var(--surface)] border-b border-[var(--line)] shadow-lg px-4 py-2 flex flex-col gap-0.5">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       @class(['h-11 px-3 inline-flex items-center rounded-[var(--r-control)] text-[14px] font-semibold transition', 'bg-[var(--tint)] text-[var(--acc)]' => request()->routeIs($item['route']), 'text-[var(--ink2)] hover:bg-[var(--bg)]' => ! request()->routeIs($item['route'])])>{{ $item['label'] }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-[var(--line)] pt-2">
                    @csrf
                    <button type="submit"
                            class="w-full h-11 px-3 inline-flex items-center rounded-[var(--r-control)] text-[14px] font-semibold text-[var(--ink2)] transition hover:bg-[var(--bg)]">
                        Odjava
                    </button>
                </form>
            </div>
        @endauth
    </header>

    <main class="mx-auto max-w-[1344px] px-4 sm:px-8 pt-7 pb-10">
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
