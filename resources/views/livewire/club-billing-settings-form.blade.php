<div class="max-w-[720px]">
    <h1 class="font-display text-[34px] font-semibold tracking-[-0.02em] text-[var(--ink)] leading-tight">Postavke naplate</h1>
    <p class="text-[15px] text-[var(--ink2)] mt-0.5 mb-5">Članarine · podaci za račune (eRačuni)</p>

    @if ($eracuniEnvironment === 'production')
        <div class="rounded-[var(--r-control)] px-4 py-3 mb-5 text-[14px] font-semibold" style="background:var(--danger-tint);color:var(--danger)">
            ⚠ Okruženje je <strong>PRODUKCIJA</strong> — računi se stvarno izdaju i šalju. Provjeri podatke prije spremanja.
        </div>
    @endif

    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- Osnovno --}}
        <section class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-5 flex flex-col gap-4">
            <h2 class="font-display text-[16px] font-semibold text-[var(--ink)]">Klub</h2>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(220px,100%),1fr))]">
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Naziv kluba</label>
                    <input type="text" wire:model="clubName" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Adresa</label>
                    <input type="text" wire:model="address" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                </div>
            </div>
        </section>

        {{-- Naplata --}}
        <section class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-5 flex flex-col gap-4">
            <h2 class="font-display text-[16px] font-semibold text-[var(--ink)]">Naplata</h2>
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Tekst stavke računa</label>
                <input type="text" wire:model="invoiceLineTemplate" placeholder="Članarina {mjesec} {godina} – {ime djeteta}"
                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                       style="border-color:{{ $errors->has('invoiceLineTemplate') ? 'var(--danger)' : 'var(--line2)' }}">
                <p class="text-[12px] text-[var(--ink3)] mt-1">Zamjenske oznake: {mjesec} {godina} {ime djeteta}</p>
            </div>
            <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(160px,100%),1fr))]">
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Dani do podsjetnika (nakon roka)</label>
                    <input type="number" wire:model="reminderDaysAfterDue" min="0" max="365"
                           class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                           style="border-color:{{ $errors->has('reminderDaysAfterDue') ? 'var(--danger)' : 'var(--line2)' }}">
                    @error('reminderDaysAfterDue') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Rok plaćanja (dan u mjesecu)</label>
                    <input type="number" wire:model="dueDay" min="1" max="31"
                           class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                           style="border-color:{{ $errors->has('dueDay') ? 'var(--danger)' : 'var(--line2)' }}">
                    @error('dueDay') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Mjeseci naplate</label>
                    <input type="text" wire:model="billingMonths" placeholder="9,10,11,12,1,2,3,4,5,6"
                           class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                           style="border-color:{{ $errors->has('billingMonths') ? 'var(--danger)' : 'var(--line2)' }}">
                    @error('billingMonths') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- eRačuni --}}
        <section class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-5 flex flex-col gap-4">
            <h2 class="font-display text-[16px] font-semibold text-[var(--ink)]">eRačuni — pristupni podaci</h2>
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Okruženje *</label>
                <select wire:model.live="eracuniEnvironment" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    <option value="test">Test</option>
                    <option value="production">Produkcija</option>
                </select>
            </div>

            @foreach ([
                ['prop' => 'eracuniUsername', 'label' => 'Korisničko ime', 'set' => $usernameSet],
                ['prop' => 'eracuniSecretKey', 'label' => 'Secret key', 'set' => $secretKeySet],
                ['prop' => 'eracuniToken', 'label' => 'Token', 'set' => $tokenSet],
                ['prop' => 'eracuniEndpoint', 'label' => 'Endpoint URL', 'set' => $endpointSet],
            ] as $cred)
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">
                        {{ $cred['label'] }}
                        <span class="font-normal text-[var(--ink3)]">· {{ $cred['set'] ? 'postavljeno ••••' : 'nije postavljeno' }}</span>
                    </label>
                    <input type="password" wire:model="{{ $cred['prop'] }}" autocomplete="new-password"
                           placeholder="{{ $cred['set'] ? 'upiši novu vrijednost za promjenu' : 'upiši vrijednost' }}"
                           class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                </div>
            @endforeach
            <div class="flex items-center gap-3 flex-wrap">
                <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                        class="h-10 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink)] transition hover:bg-[var(--bg)] disabled:opacity-60">
                    <span wire:loading.remove wire:target="testConnection">Provjeri vezu</span>
                    <span wire:loading wire:target="testConnection">Provjeravam…</span>
                </button>
                <p class="text-[12px] text-[var(--ink3)]">Šalje samo čitanje (jedan upit partnera). Prvo spremi postavke.</p>
            </div>
            <p class="text-[12px] text-[var(--ink3)]">Vjerodajnice se spremaju šifrirano i nikad se ne prikazuju u cijelosti.</p>
        </section>

        {{-- Nekorišteno (HUB) --}}
        <section class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-5" x-data="{ open: false }">
            <button type="button" class="w-full flex items-center justify-between text-left" x-on:click="open = ! open">
                <h2 class="font-display text-[16px] font-semibold text-[var(--ink2)]">Nekorišteno (HUB uplatnica)</h2>
                <span class="text-[var(--ink3)] text-[13px]" x-text="open ? 'Sakrij' : 'Prikaži'"></span>
            </button>
            <div x-show="open" x-cloak class="mt-4 flex flex-col gap-4">
                <p class="text-[13px] text-[var(--ink3)]">Zadržano radi podataka; više se ne koristi (račune izdaje eRačuni).</p>
                <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr))]">
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">IBAN</label>
                        <input type="text" wire:model="iban" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Model</label>
                        <input type="text" wire:model="model" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Pravilo poziva na broj</label>
                        <input type="text" wire:model="referenceRule" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Šifra namjene</label>
                        <input type="text" wire:model="purposeCode" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    </div>
                </div>
            </div>
        </section>

        <div>
            <button type="submit" class="h-11 px-5 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[15px] font-semibold transition hover:brightness-110">Spremi postavke</button>
        </div>
    </form>
</div>
