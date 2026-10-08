<div class="bg-[var(--surface)] border border-[var(--line)] rounded-[var(--r-card)] p-6 shadow-sm">
    <h1 class="font-display text-[20px] font-semibold text-[var(--ink)]">Prijava</h1>
    <p class="text-[14px] text-[var(--ink2)] mt-1 mb-5">Evidencija polaznika kluba Optimist.</p>

    <form wire:submit="login" class="flex flex-col gap-4" novalidate>
        <div>
            <label for="email" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">E-mail</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="username"
                required
                class="w-full h-11 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
            >
            @error('email')
                <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Lozinka</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                required
                class="w-full h-11 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
            >
            @error('password')
                <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-[14px] text-[var(--ink2)] select-none">
            <input wire:model="remember" type="checkbox" class="w-4 h-4"> Zapamti me
        </label>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="login"
            class="h-11 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[15px] transition hover:brightness-110 disabled:opacity-70"
        >
            <span wire:loading.remove wire:target="login">Prijava</span>
            <span wire:loading wire:target="login">Prijava…</span>
        </button>
    </form>
</div>
