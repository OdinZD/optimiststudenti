<div>
    @if ($declaration)
        <div class="rounded-[var(--r-control)] border border-[var(--line)] px-3 py-2.5 flex items-center justify-between gap-3">
            <div class="min-w-0 flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-full inline-flex items-center justify-center shrink-0" style="background:var(--st-valid-bg);color:var(--st-valid-fg)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <div class="min-w-0">
                    <div class="text-[14px] font-semibold text-[var(--ink)] truncate">{{ $declaration->original_name }}</div>
                    <div class="text-[13px] text-[var(--ink3)]">Učitano {{ $declaration->created_at->format('d.m.Y.') }}</div>
                </div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <a href="{{ route('izjava', $studentId) }}" target="_blank" rel="noopener"
                   class="h-9 px-3 inline-flex items-center rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Pregledaj</a>
                <button type="button" wire:click="remove" aria-label="Ukloni izjavu"
                        class="w-9 h-9 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] hover:bg-[var(--danger-tint)]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
            </div>
        </div>
    @else
        <p class="text-[13px] text-[var(--ink3)]">Nema učitane izjave.</p>
    @endif

    <div class="mt-3">
        <span class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">{{ $declaration ? 'Zamijeni izjavu' : 'Učitaj izjavu' }} (PDF ili slika, najviše 10 MB)</span>
        <div class="flex items-center gap-2 flex-wrap">
            <input type="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png"
                   class="text-[14px] text-[var(--ink2)] file:mr-3 file:h-9 file:px-3 file:rounded-[var(--r-control)] file:border-0 file:bg-[var(--tint)] file:text-[var(--acc)] file:font-semibold file:cursor-pointer">
            <span wire:loading wire:target="file" class="text-[13px] font-semibold text-[var(--acc)]">Spremam…</span>
        </div>
        <p class="mt-1 text-[12px] text-[var(--ink3)]">Datoteka se sprema automatski čim je odabereš.</p>
        @error('file') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
    </div>
</div>
