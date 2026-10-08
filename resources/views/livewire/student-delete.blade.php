<div>
    {{-- Confirmation dialog --}}
    <div x-cloak x-show="$wire.confirming" class="fixed inset-0 z-[55]" @keydown.escape.window="$wire.confirming && $wire.cancel()">
        <div x-show="$wire.confirming" x-transition.opacity.duration.150ms
             class="absolute inset-0" style="background:var(--overlay-dialog)" @click="$wire.cancel()"></div>

        <div x-show="$wire.confirming"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-[var(--surface)] p-6"
             style="width:min(440px,calc(100%-32px));border-radius:var(--r-dialog);box-shadow:var(--shadow-dialog)"
             role="alertdialog" aria-modal="true" aria-labelledby="del-title">
            <div class="w-11 h-11 rounded-full inline-flex items-center justify-center" style="background:var(--danger-tint)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </div>
            <h2 id="del-title" class="font-display text-[22px] font-semibold text-[var(--ink)] mt-3">Obrisati polaznika?</h2>
            <p class="text-[14px] text-[var(--ink2)] mt-2">
                Zapis <strong class="text-[var(--ink)]">{{ $studentName }}</strong> bit će uklonjen s popisa.
                Brisanje možeš poništiti samo odmah nakon toga.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" wire:click="cancel"
                        class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">
                    Odustani
                </button>
                <button type="button" wire:click="delete"
                        class="h-11 px-4 rounded-[var(--r-control)] text-white text-[15px] font-semibold transition hover:brightness-110" style="background:var(--danger)">
                    Da, obriši
                </button>
            </div>
        </div>
    </div>

    {{-- Undo toast (auto-dismiss after 7 s) --}}
    <div x-data="{ timer: null }"
         x-effect="$wire.showUndo ? (clearTimeout(timer), timer = setTimeout(() => $wire.dismissUndo(), 7000)) : clearTimeout(timer)"
         x-cloak x-show="$wire.showUndo"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed left-1/2 -translate-x-1/2 bottom-6 z-[60]" role="status" aria-live="polite">
        <div class="rounded-[var(--r-card)] pl-4 pr-2 py-1 text-[14px] font-medium text-white flex items-center gap-3" style="background:var(--ink);box-shadow:var(--shadow-toast)">
            <span>Obrisano: {{ $deletedName }}</span>
            <button type="button" wire:click="undo" class="h-11 px-2 inline-flex items-center underline font-semibold text-white">Poništi</button>
        </div>
    </div>
</div>
