<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-[34px] font-semibold tracking-[-0.02em] text-[var(--ink)] leading-tight">Natjecanja</h1>
            <p class="text-[15px] text-[var(--ink2)] mt-0.5">{{ $competitions->count() }} natjecanja</p>
        </div>
        <button type="button" wire:click="openCreate"
                class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[15px] transition hover:brightness-110">
            + Novo natjecanje
        </button>
    </div>

    {{-- List --}}
    <div class="mt-5 flex flex-col gap-2">
        @forelse ($competitions as $c)
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] px-4 py-3 flex items-center justify-between gap-4" wire:key="comp-{{ $c->id }}">
                <button type="button" wire:click="openEdit({{ $c->id }})" class="text-left min-w-0">
                    <div class="text-[15px] font-semibold text-[var(--ink)] truncate">{{ $c->name }}</div>
                    <div class="text-[13px] text-[var(--ink3)] mt-0.5">
                        {{ $c->held_on->format('d.m.Y.') }}@if ($c->city) · {{ $c->city }}@endif
                    </div>
                </button>
                <div class="flex items-center gap-1 shrink-0">
                    <span class="text-[13px] text-[var(--ink3)] mr-2 whitespace-nowrap">{{ $c->results_count }} rezultata</span>
                    <button type="button" wire:click="openEdit({{ $c->id }})" aria-label="Uredi: {{ $c->name }}"
                            class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] transition hover:bg-[var(--tint)]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </button>
                    <button type="button" wire:click="confirmDelete({{ $c->id }})" aria-label="Obriši: {{ $c->name }}"
                            class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] transition hover:bg-[var(--danger-tint)]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-8 text-center">
                <div class="font-display text-[20px] font-semibold text-[var(--ink)]">Još nema natjecanja</div>
                <p class="text-[14px] text-[var(--ink2)] mt-1">Dodaj prvo natjecanje gumbom gore desno.</p>
            </div>
        @endforelse
    </div>

    {{-- Drawer: novo / uredi natjecanje --}}
    <div x-cloak x-show="$wire.open" class="fixed inset-0 z-50" @keydown.escape.window="$wire.open && $wire.close()">
        <div x-show="$wire.open" x-transition.opacity.duration.200ms class="absolute inset-0" style="background:var(--overlay-drawer)" @click="$wire.close()"></div>
        <div x-show="$wire.open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 h-full bg-[var(--surface)] flex flex-col" style="width:min(520px,100%);box-shadow:var(--shadow-drawer)"
             role="dialog" aria-modal="true" aria-labelledby="comp-title">
            <form wire:submit="save" class="flex flex-col h-full">
                <header class="flex items-center justify-between gap-4 px-6 h-[68px] border-b border-[var(--line)] shrink-0">
                    <h2 id="comp-title" class="font-display text-[24px] font-semibold text-[var(--ink)]">{{ $editId ? 'Uredi natjecanje' : 'Novo natjecanje' }}</h2>
                    <button type="button" wire:click="close" aria-label="Zatvori" class="w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] hover:bg-[var(--bg)]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-6 py-5 flex flex-col gap-4">
                    <div>
                        <label for="c-name" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Naziv *</label>
                        <input id="c-name" type="text" wire:model.blur="name"
                               class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                               style="border-color:{{ $errors->has('name') ? 'var(--danger)' : 'var(--line2)' }}">
                        @error('name') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr))]">
                        <div>
                            <label for="c-date" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Datum *</label>
                            <input id="c-date" type="date" wire:model.blur="heldOn"
                                   class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                   style="border-color:{{ $errors->has('heldOn') ? 'var(--danger)' : 'var(--line2)' }}">
                            @error('heldOn') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="c-city" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Grad / mjesto</label>
                            <input id="c-city" type="text" wire:model.blur="city"
                                   class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                        </div>
                    </div>
                </div>

                <footer class="shrink-0 border-t border-[var(--line)] px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" wire:click="close" class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                    <button type="submit" class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[15px] font-semibold transition hover:brightness-110">{{ $editId ? 'Spremi promjene' : 'Dodaj natjecanje' }}</button>
                </footer>
            </form>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div x-cloak x-show="$wire.confirmingDelete" class="fixed inset-0 z-[55]" @keydown.escape.window="$wire.confirmingDelete && $wire.cancelDelete()">
        <div x-show="$wire.confirmingDelete" x-transition.opacity.duration.150ms class="absolute inset-0" style="background:var(--overlay-dialog)" @click="$wire.cancelDelete()"></div>
        <div x-show="$wire.confirmingDelete" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-[var(--surface)] p-6" style="width:min(440px,calc(100%-32px));border-radius:var(--r-dialog);box-shadow:var(--shadow-dialog)"
             role="alertdialog" aria-modal="true" aria-labelledby="comp-del-title">
            <div class="w-11 h-11 rounded-full inline-flex items-center justify-center" style="background:var(--danger-tint)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </div>
            <h2 id="comp-del-title" class="font-display text-[22px] font-semibold text-[var(--ink)] mt-3">Obrisati natjecanje?</h2>
            <p class="text-[14px] text-[var(--ink2)] mt-2">
                Natjecanje <strong class="text-[var(--ink)]">{{ $deleteName }}</strong> bit će obrisano
                @if ($deleteResults > 0) zajedno sa <strong class="text-[var(--ink)]">{{ $deleteResults }}</strong> upisanih rezultata. @else. @endif
                Ovo se ne može poništiti.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" wire:click="cancelDelete" class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                <button type="button" wire:click="delete" class="h-11 px-4 rounded-[var(--r-control)] text-white text-[15px] font-semibold transition hover:brightness-110" style="background:var(--danger)">Da, obriši</button>
            </div>
        </div>
    </div>
</div>
