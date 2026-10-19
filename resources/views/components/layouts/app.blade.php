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
        @php
            $navItems = [
                ['route' => 'polaznici', 'label' => 'Polaznici'],
                ['route' => 'natjecanja', 'label' => 'Natjecanja'],
                ['route' => 'rosters', 'label' => 'Popisi za natjecanje'],
                ['route' => 'kata-timovi', 'label' => 'Kata timovi'],
            ];
            // Lucide-style 24x24 paths (2px stroke); unknown routes fall back to a generic dot.
            $navIcons = [
                'polaznici' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'natjecanja' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
                'kata-timovi' => '<path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/>',
                'rosters' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
            ];
            $fallbackIcon = '<circle cx="12" cy="12" r="3"/>';
        @endphp

        {{-- Fiksni bočni izbornik na desktopu (z-40: ispod ladica z-50, dijaloga z-[55] i toasta z-[60]) --}}
        <aside class="hidden lg:flex lg:fixed lg:inset-y-0 lg:left-0 z-40 w-[240px] flex-col bg-[var(--surface)] border-r border-[var(--line)] px-4 py-5">
            <div class="flex items-center gap-2.5 px-2">
                <span class="w-7 h-7 rounded-lg bg-[var(--acc)] inline-flex items-center justify-center shrink-0">
                    <span class="w-3 h-3 rounded-full border-2 border-white"></span>
                </span>
                <span class="font-display text-[20px] font-bold text-[var(--ink)]">Optimist</span>
            </div>

            <nav aria-label="Glavna navigacija" class="mt-6 flex flex-col gap-1 text-[14px] font-semibold">
                @foreach ($navItems as $item)
                    @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class(['px-3 h-11 flex items-center gap-3 rounded-[var(--r-control)] transition', 'bg-[var(--tint)] text-[var(--acc)]' => $active, 'text-[var(--ink2)] hover:bg-[var(--bg)]' => ! $active])>
                        <svg class="shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $navIcons[$item['route']] ?? $fallbackIcon !!}</svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="mt-auto border-t border-[var(--line)] pt-3">
                @csrf
                <button type="submit"
                        class="w-full h-11 px-3 flex items-center gap-3 rounded-[var(--r-control)] text-[14px] font-semibold text-[var(--ink2)] transition hover:bg-[var(--bg)]">
                    <svg class="shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    Odjava
                </button>
            </form>
        </aside>
    @endauth

    <header class="relative h-[60px] bg-[var(--surface)] border-b border-[var(--line)] lg:hidden" x-data="{ open: false }">
        <div class="mx-auto max-w-[1344px] h-full flex items-center gap-2.5 px-4 sm:px-8">
            <span class="w-7 h-7 rounded-lg bg-[var(--acc)] inline-flex items-center justify-center shrink-0">
                <span class="w-3 h-3 rounded-full border-2 border-white"></span>
            </span>
            <span class="font-display text-[20px] font-bold text-[var(--ink)]">Optimist</span>

            @auth
                {{-- Hamburger ispod lg breakpointa --}}
                <button type="button" x-on:click.stop="open = ! open" :aria-expanded="open" aria-controls="mobilni-izbornik" aria-label="Izbornik"
                        class="lg:hidden ml-auto w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] transition hover:bg-[var(--bg)]">
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
                 class="lg:hidden absolute inset-x-0 top-[60px] z-50 bg-[var(--surface)] border-b border-[var(--line)] shadow-lg px-4 py-2 flex flex-col gap-0.5">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       @class(['h-11 px-3 inline-flex items-center rounded-[var(--r-control)] text-[14px] font-semibold transition', 'bg-[var(--tint)] text-[var(--acc)]' => request()->routeIs($item['route'], $item['route'].'.*'), 'text-[var(--ink2)] hover:bg-[var(--bg)]' => ! request()->routeIs($item['route'], $item['route'].'.*')])>{{ $item['label'] }}</a>
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

    {{-- Wrapper nosi lijevi razmak za sidebar; unutarnji main zadržava centriranje i max širinu --}}
    <div @class(['lg:pl-[240px]' => auth()->check()])>
        <main class="mx-auto max-w-[1344px] px-4 sm:px-8 pt-7 pb-10">
            {{ $slot }}
        </main>
    </div>

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
