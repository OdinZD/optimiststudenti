<div>
    {{-- Postojeći rezultati --}}
    @if ($results->isNotEmpty())
        <div class="flex flex-col gap-2 mb-4">
            @foreach ($results as $r)
                <div class="rounded-[var(--r-control)] border border-[var(--line)] px-3 py-2 flex items-center justify-between gap-3" wire:key="res-{{ $r->id }}">
                    <div class="min-w-0">
                        <div class="text-[14px] font-semibold text-[var(--ink)] truncate">
                            {{ $r->competition->name }}
                            <span class="text-[var(--ink3)] font-normal">· {{ $r->competition->held_on->format('d.m.Y.') }}</span>
                        </div>
                        @php
                            $boutsText = null;
                            if ($r->bouts !== null || $r->wins !== null || $r->losses !== null) {
                                $total = $r->bouts ?? (($r->wins ?? 0) + ($r->losses ?? 0));
                                $boutsText = $total.' borbi';
                                if ($r->wins !== null || $r->losses !== null) {
                                    $boutsText .= ' ('.($r->wins ?? 0).'–'.($r->losses ?? 0).')';
                                }
                            }
                        @endphp
                        <div class="text-[13px] text-[var(--ink2)] mt-0.5 flex flex-wrap gap-x-2">
                            <span class="font-medium">{{ $r->discipline->label() }}</span>
                            @if ($r->category)<span class="text-[var(--ink3)]">{{ $r->category }}</span>@endif
                            @if ($r->place)<span>· {{ $r->place }}. mjesto</span>@endif
                            @if ($r->medal)<span style="color:var(--acc)">{{ $r->medal->label() }}</span>@endif
                            @if ($boutsText)<span class="text-[var(--ink3)]">· {{ $boutsText }}</span>@endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" wire:click="editResult({{ $r->id }})" aria-label="Uredi rezultat"
                                class="w-9 h-9 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] hover:bg-[var(--tint)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                        <button type="button" wire:click="deleteResult({{ $r->id }})" aria-label="Obriši rezultat"
                                class="w-9 h-9 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] hover:bg-[var(--danger-tint)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Dodaj / uredi rezultat --}}
    @if ($competitions->isEmpty())
        <p class="text-[13px] text-[var(--ink3)]">Za unos rezultata prvo dodaj natjecanje na stranici <span class="font-semibold">Natjecanja</span>.</p>
    @else
        <div class="rounded-[var(--r-control)] p-4" style="background:var(--bg)">
            <div class="text-[13px] font-semibold text-[var(--ink2)] mb-3">{{ $editingId ? 'Uredi rezultat' : 'Dodaj rezultat' }}</div>
            <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr))]">
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Natjecanje *</label>
                    <select wire:model="competitionId" class="h-10 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none" style="border-color:{{ $errors->has('competitionId') ? 'var(--danger)' : 'var(--line2)' }}">
                        <option value="">— odaberi —</option>
                        @foreach ($competitions as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->held_on->format('d.m.Y.') }})</option>
                        @endforeach
                    </select>
                    @error('competitionId') <p class="mt-1 text-[12px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Disciplina *</label>
                    <select wire:model="discipline" class="h-10 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none" style="border-color:{{ $errors->has('discipline') ? 'var(--danger)' : 'var(--line2)' }}">
                        <option value="">— odaberi —</option>
                        @foreach ($disciplines as $d)
                            <option value="{{ $d->value }}">{{ $d->label() }}</option>
                        @endforeach
                    </select>
                    @error('discipline') <p class="mt-1 text-[12px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Kategorija</label>
                    <input type="text" wire:model="category" placeholder="npr. U12 -40kg" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Plasman (mjesto)</label>
                    <input type="number" min="1" max="99" wire:model="place" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Pobjede</label>
                    <input type="number" min="0" max="127" wire:model="wins" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Porazi</label>
                    <input type="number" min="0" max="127" wire:model="losses" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Broj borbi</label>
                    <input type="number" min="0" max="127" wire:model="bouts" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none">
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-[var(--ink2)] mb-1">Napomena</label>
                    <input type="text" wire:model="note" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2 text-[14px] text-[var(--ink)] focus:outline-none">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button type="button" wire:click="save" class="h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:brightness-110">{{ $editingId ? 'Spremi rezultat' : 'Dodaj rezultat' }}</button>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="h-10 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink2)] hover:bg-[var(--surface)]">Odustani</button>
                @endif
            </div>
        </div>
    @endif
</div>
