<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-[34px] font-semibold tracking-[-0.02em] text-[var(--ink)] leading-tight">Tarife članarine</h1>
            <p class="text-[15px] text-[var(--ink2)] mt-0.5">{{ $plans->count() }} tarifa</p>
        </div>
        <button type="button" wire:click="openCreate"
                class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[15px] transition hover:brightness-110">
            + Nova tarifa
        </button>
    </div>

    {{-- List --}}
    <div class="mt-5 flex flex-col gap-2">
        @forelse ($plans as $p)
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] px-4 py-3 flex items-center justify-between gap-4" wire:key="plan-{{ $p->id }}">
                <button type="button" wire:click="openEdit({{ $p->id }})" class="text-left min-w-0">
                    <div class="text-[15px] font-semibold text-[var(--ink)] truncate">
                        {{ $p->name }}
                        @unless ($p->active)
                            <span class="ml-1 inline-block rounded-[var(--r-tag)] px-1.5 text-[11px] font-semibold align-middle" style="background:var(--neutral-pill);color:var(--ink2)">neaktivna</span>
                        @endunless
                    </div>
                    <div class="text-[13px] text-[var(--ink3)] mt-0.5">{{ $p->type->label() }} · {{ \App\Support\Money::format($p->amount_cents) }}</div>
                </button>
                <div class="flex items-center gap-1 shrink-0">
                    <span class="text-[13px] text-[var(--ink3)] mr-2 whitespace-nowrap">{{ $p->student_fee_plans_count }} dodijeljeno</span>
                    <button type="button" wire:click="openEdit({{ $p->id }})" aria-label="Uredi: {{ $p->name }}"
                            class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] transition hover:bg-[var(--tint)]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </button>
                    <button type="button" wire:click="confirmDelete({{ $p->id }})" aria-label="Obriši: {{ $p->name }}"
                            class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] transition hover:bg-[var(--danger-tint)]">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-8 text-center">
                <div class="font-display text-[20px] font-semibold text-[var(--ink)]">Još nema tarifa</div>
                <p class="text-[14px] text-[var(--ink2)] mt-1">Dodaj prvu tarifu gumbom gore desno.</p>
            </div>
        @endforelse
    </div>

    {{-- Drawer: nova / uredi tarifa --}}
    <div x-cloak x-show="$wire.open" class="fixed inset-0 z-50" @keydown.escape.window="$wire.open && $wire.close()">
        <div x-show="$wire.open" x-transition.opacity.duration.200ms class="absolute inset-0" style="background:var(--overlay-drawer)" @click="$wire.close()"></div>
        <div x-show="$wire.open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 h-full bg-[var(--surface)] flex flex-col" style="width:min(520px,100%);box-shadow:var(--shadow-drawer)"
             role="dialog" aria-modal="true" aria-labelledby="plan-title">
            <form wire:submit="save" class="flex flex-col h-full">
                <header class="flex items-center justify-between gap-4 px-6 h-[68px] border-b border-[var(--line)] shrink-0">
                    <h2 id="plan-title" class="font-display text-[24px] font-semibold text-[var(--ink)]">{{ $editId ? 'Uredi tarifu' : 'Nova tarifa' }}</h2>
                    <button type="button" wire:click="close" aria-label="Zatvori" class="w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] hover:bg-[var(--bg)]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-6 py-5 flex flex-col gap-4">
                    <div>
                        <label for="p-name" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Naziv *</label>
                        <input id="p-name" type="text" wire:model.blur="name"
                               class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                               style="border-color:{{ $errors->has('name') ? 'var(--danger)' : 'var(--line2)' }}">
                        @error('name') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr))]">
                        <div>
                            <label for="p-amount" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Iznos (EUR) *</label>
                            <input id="p-amount" type="text" inputmode="decimal" wire:model.blur="amount" placeholder="npr. 25,00"
                                   class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                   style="border-color:{{ $errors->has('amount') ? 'var(--danger)' : 'var(--line2)' }}">
                            @error('amount') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="p-type" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Vrsta *</label>
                            <select id="p-type" wire:model="type"
                                    class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                                @foreach ($types as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-[14px] text-[var(--ink2)] select-none">
                        <input type="checkbox" wire:model="active" class="w-4 h-4"> Aktivna
                    </label>
                </div>

                <footer class="shrink-0 border-t border-[var(--line)] px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" wire:click="close" class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                    <button type="submit" class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[15px] font-semibold transition hover:brightness-110">{{ $editId ? 'Spremi promjene' : 'Dodaj tarifu' }}</button>
                </footer>
            </form>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div x-cloak x-show="$wire.confirmingDelete" class="fixed inset-0 z-[55]" @keydown.escape.window="$wire.confirmingDelete && $wire.cancelDelete()">
        <div x-show="$wire.confirmingDelete" x-transition.opacity.duration.150ms class="absolute inset-0" style="background:var(--overlay-dialog)" @click="$wire.cancelDelete()"></div>
        <div x-show="$wire.confirmingDelete" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-[var(--surface)] p-6" style="width:min(440px,calc(100%-32px));border-radius:var(--r-dialog);box-shadow:var(--shadow-dialog)"
             role="alertdialog" aria-modal="true" aria-labelledby="plan-del-title">
            <div class="w-11 h-11 rounded-full inline-flex items-center justify-center" style="background:var(--danger-tint)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </div>
            <h2 id="plan-del-title" class="font-display text-[22px] font-semibold text-[var(--ink)] mt-3">Obrisati tarifu?</h2>
            <p class="text-[14px] text-[var(--ink2)] mt-2">
                @if ($deleteAssignments > 0)
                    Tarifa <strong class="text-[var(--ink)]">{{ $deleteName }}</strong> dodijeljena je
                    <strong class="text-[var(--ink)]">{{ $deleteAssignments }}</strong> djeci — ne može se obrisati. Deaktiviraj je umjesto toga.
                @else
                    Tarifa <strong class="text-[var(--ink)]">{{ $deleteName }}</strong> bit će obrisana. Ovo se ne može poništiti.
                @endif
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" wire:click="cancelDelete" class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                @if ($deleteAssignments === 0)
                    <button type="button" wire:click="delete" class="h-11 px-4 rounded-[var(--r-control)] text-white text-[15px] font-semibold transition hover:brightness-110" style="background:var(--danger)">Da, obriši</button>
                @endif
            </div>
        </div>
    </div>
</div>
