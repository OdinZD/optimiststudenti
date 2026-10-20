<div>
    {{-- Header --}}
    <a href="{{ route('rosters') }}" class="inline-flex items-center gap-1 text-[14px] font-semibold text-[var(--acc)] hover:underline">&larr; Popisi za natjecanje</a>
    <div class="mt-2 flex items-start justify-between gap-4 flex-wrap">
        <div class="min-w-0">
            <h1 class="font-display text-[34px] font-semibold tracking-[-0.02em] text-[var(--ink)] leading-tight">{{ $roster->name }}</h1>
            <p class="text-[15px] text-[var(--ink2)] mt-0.5">
                @if ($roster->event_date) {{ $roster->event_date->format('d.m.Y.') }} · @endif prijava: {{ $entries->count() }} · potvrđeno: {{ $confirmedCount }}
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <label class="inline-flex items-center gap-2 text-[14px] text-[var(--ink2)]">
                <input type="checkbox" wire:model="onlyConfirmed" class="w-5 h-5"> Samo potvrđeni
            </label>
            <button type="button" wire:click="export" class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[15px] transition hover:brightness-110">Izvezi u Excel</button>
            <button type="button" wire:click="openEdit" aria-label="Uredi popis" class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] border border-[var(--line2)] text-[var(--ink2)] hover:bg-[var(--tint)]"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
            <button type="button" wire:click="confirmDelete" aria-label="Obriši popis" class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] border border-[var(--line2)] text-[var(--danger)] hover:bg-[var(--danger-tint)]"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
    </div>

    {{-- Add children --}}
    <div class="mt-5 bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-4 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr))]">
        <div>
            <label for="ros-search" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Dodaj dijete</label>
            <input id="ros-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Pretraži po imenu ili prezimenu"
                   class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
            @if ($matches->isNotEmpty())
                <ul class="mt-2 flex flex-col divide-y divide-[var(--line)] border border-[var(--line)] rounded-[var(--r-control)]">
                    @foreach ($matches as $m)
                        <li class="flex items-center justify-between gap-3 px-3 py-1.5" wire:key="match-{{ $m->id }}">
                            <span class="text-[14px] text-[var(--ink)]">{{ $m->last_name }} {{ $m->first_name }}</span>
                            @if (in_array($m->id, $onRoster, true))
                                <span class="text-[13px] text-[var(--ink3)]">Već na popisu</span>
                            @else
                                <button type="button" wire:click="addStudent({{ $m->id }})" class="h-9 px-3 rounded-[var(--r-control)] text-[13px] font-semibold text-[var(--acc)] hover:bg-[var(--tint)]">Dodaj</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @elseif (trim($search) !== '')
                <p class="mt-2 text-[13px] text-[var(--ink3)]">Nema rezultata.</p>
            @endif
        </div>
        <div>
            <label for="ros-quick" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Brzo dodavanje po grupi ili uzrastu</label>
            <div class="flex gap-2 flex-wrap">
                <select id="ros-quick" wire:model.live="quick" class="h-11 flex-1 min-w-[180px] rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)]">
                    <option value="">Odaberi...</option>
                    <optgroup label="Grupa">
                        @foreach ($groups as $g)
                            <option value="g:{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Uzrast">
                        @foreach ($ageOptions as $key => $label)
                            <option value="a:{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <button type="button" wire:click="addQuick" @disabled($quick === '' || $quickMissing === 0)
                        class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[15px] transition hover:brightness-110 disabled:opacity-50">Dodaj sve ({{ $quickMissing }})</button>
            </div>
            <p class="mt-1.5 text-[12px] text-[var(--ink3)]">Dodaju se samo oni koji još nisu na popisu; disciplinu odabireš poslije.</p>
        </div>
    </div>

    {{-- Entries: desktop table --}}
    <div class="relative mt-4 bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] overflow-x-auto max-[760px]:hidden">
        <table class="w-full min-w-[960px] text-left text-[14px]">
            <thead>
                <tr class="text-[12px] uppercase tracking-wide text-[var(--ink3)] border-b border-[var(--line)]">
                    <th class="px-4 py-3 font-semibold">Prezime Ime</th>
                    <th class="px-2 py-3 font-semibold">Uzrast</th>
                    <th class="px-2 py-3 font-semibold w-[150px]">Disciplina</th>
                    <th class="px-2 py-3 font-semibold w-[160px]">Kategorija</th>
                    <th class="px-2 py-3 font-semibold">Napomena</th>
                    <th class="px-2 py-3 font-semibold text-center">Potvrđeno</th>
                    <th class="px-2 py-3"><span class="sr-only">Radnje</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $e)
                    <tr class="border-b border-[var(--line)] last:border-b-0" wire:key="row-{{ $e->id }}">
                        <td class="px-4 py-2 font-semibold text-[var(--ink)] whitespace-nowrap">{{ $e->student->last_name }} {{ $e->student->first_name }}</td>
                        <td class="px-2 py-2 text-[var(--ink2)] whitespace-nowrap">{{ $ageLabels[$e->id] }}</td>
                        <td class="px-2 py-2"><select wire:key="disc-{{ $e->id }}-{{ $rev }}" wire:change="setDiscipline({{ $e->id }}, $event.target.value)" aria-label="Disciplina: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2.5 text-[14px] text-[var(--ink)]">
                                    <option value="" @selected($e->discipline === null)>Disciplina</option>
                                    @foreach ($disciplines as $d)
                                        <option value="{{ $d->value }}" @selected($e->discipline === $d)>{{ $d->label() }}</option>
                                    @endforeach
                                </select></td>
                        <td class="px-2 py-2"><input type="text" maxlength="60" value="{{ $e->category }}" wire:change="setCategory({{ $e->id }}, $event.target.value)" aria-label="Kategorija: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2.5 text-[14px] text-[var(--ink)]"></td>
                        <td class="px-2 py-2"><input type="text" maxlength="255" value="{{ $e->note }}" wire:change="setNote({{ $e->id }}, $event.target.value)" aria-label="Napomena: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2.5 text-[14px] text-[var(--ink)]"></td>
                        <td class="px-2 py-2 text-center"><input type="checkbox" @checked($e->confirmed) wire:change="setConfirmed({{ $e->id }}, $event.target.checked)" aria-label="Potvrđeno: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="w-5 h-5"></td>
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <button type="button" wire:click="addDiscipline({{ $e->id }})" class="h-10 px-3 rounded-[var(--r-control)] text-[13px] font-semibold text-[var(--acc)] hover:bg-[var(--tint)]">+ disciplina</button>
                            <button type="button" wire:click="remove({{ $e->id }})" aria-label="Ukloni: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] hover:bg-[var(--danger-tint)]"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-[var(--ink2)]">Popis je prazan. Dodaj djecu pretragom ili brzim dodavanjem iznad.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Entries: mobile cards --}}
    <div class="mt-4 flex flex-col gap-3 min-[761px]:hidden">
        @forelse ($entries as $e)
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-4 flex flex-col gap-2.5" wire:key="card-{{ $e->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[15px] font-semibold text-[var(--ink)]">{{ $e->student->last_name }} {{ $e->student->first_name }}</div>
                        <div class="text-[13px] text-[var(--ink3)]">{{ $ageLabels[$e->id] }}</div>
                    </div>
                    <button type="button" wire:click="remove({{ $e->id }})" aria-label="Ukloni: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] hover:bg-[var(--danger-tint)]"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
                </div>
                <select wire:key="disc-{{ $e->id }}-{{ $rev }}" wire:change="setDiscipline({{ $e->id }}, $event.target.value)" aria-label="Disciplina: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2.5 text-[14px] text-[var(--ink)]">
                                    <option value="" @selected($e->discipline === null)>Disciplina</option>
                                    @foreach ($disciplines as $d)
                                        <option value="{{ $d->value }}" @selected($e->discipline === $d)>{{ $d->label() }}</option>
                                    @endforeach
                                </select>
                <input type="text" maxlength="60" value="{{ $e->category }}" placeholder="Kategorija" wire:change="setCategory({{ $e->id }}, $event.target.value)" aria-label="Kategorija: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2.5 text-[14px] text-[var(--ink)]">
                <input type="text" maxlength="255" value="{{ $e->note }}" placeholder="Napomena" wire:change="setNote({{ $e->id }}, $event.target.value)" aria-label="Napomena: {{ $e->student->last_name }} {{ $e->student->first_name }}" class="h-10 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-2.5 text-[14px] text-[var(--ink)]">
                <div class="flex items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-[14px] text-[var(--ink)]">
                        <input type="checkbox" @checked($e->confirmed) wire:change="setConfirmed({{ $e->id }}, $event.target.checked)" class="w-5 h-5"> Potvrđeno
                    </label>
                    <button type="button" wire:click="addDiscipline({{ $e->id }})" class="h-10 px-3 rounded-[var(--r-control)] text-[13px] font-semibold text-[var(--acc)] hover:bg-[var(--tint)]">+ disciplina</button>
                </div>
            </div>
        @empty
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-6 text-center text-[var(--ink2)]">Popis je prazan. Dodaj djecu pretragom ili brzim dodavanjem iznad.</div>
        @endforelse
    </div>

    {{-- Drawer: novo / uredi natjecanje --}}
    <div x-cloak x-show="$wire.open" class="fixed inset-0 z-50" @keydown.escape.window="$wire.open && $wire.close()">
        <div x-show="$wire.open" x-transition.opacity.duration.200ms class="absolute inset-0" style="background:var(--overlay-drawer)" @click="$wire.close()"></div>
        <div x-show="$wire.open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 h-full bg-[var(--surface)] flex flex-col" style="width:min(520px,100%);box-shadow:var(--shadow-drawer)"
             role="dialog" aria-modal="true" aria-labelledby="ros-title">
            <form wire:submit="save" class="flex flex-col h-full">
                <header class="flex items-center justify-between gap-4 px-6 h-[68px] border-b border-[var(--line)] shrink-0">
                    <h2 id="ros-title" class="font-display text-[24px] font-semibold text-[var(--ink)]">{{ 'Uredi popis' }}</h2>
                    <button type="button" wire:click="close" aria-label="Zatvori" class="w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] hover:bg-[var(--bg)]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto px-6 py-5 flex flex-col gap-4">
                    <div>
                        <label for="r-name" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Naziv *</label>
                        <input id="r-name" type="text" wire:model.blur="name" maxlength="120"
                               class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                               style="border-color:{{ $errors->has('name') ? 'var(--danger)' : 'var(--line2)' }}">
                        @error('name') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="r-date" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Datum natjecanja</label>
                        <input id="r-date" type="date" wire:model.blur="eventDate"
                               class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                               style="border-color:{{ $errors->has('eventDate') ? 'var(--danger)' : 'var(--line2)' }}">
                        @error('eventDate') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <footer class="shrink-0 border-t border-[var(--line)] px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" wire:click="close" class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                    <button type="submit" class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[15px] font-semibold transition hover:brightness-110">{{ 'Spremi promjene' }}</button>
                </footer>
            </form>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div x-cloak x-show="$wire.confirmingDelete" class="fixed inset-0 z-[55]" @keydown.escape.window="$wire.confirmingDelete && $wire.cancelDelete()">
        <div x-show="$wire.confirmingDelete" x-transition.opacity.duration.150ms class="absolute inset-0" style="background:var(--overlay-dialog)" @click="$wire.cancelDelete()"></div>
        <div x-show="$wire.confirmingDelete" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-[var(--surface)] p-6" style="width:min(440px,calc(100%-32px));border-radius:var(--r-dialog);box-shadow:var(--shadow-dialog)"
             role="alertdialog" aria-modal="true" aria-labelledby="ros-del-title">
            <div class="w-11 h-11 rounded-full inline-flex items-center justify-center" style="background:var(--danger-tint)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </div>
            <h2 id="ros-del-title" class="font-display text-[22px] font-semibold text-[var(--ink)] mt-3">Obrisati popis?</h2>
            <p class="text-[14px] text-[var(--ink2)] mt-2">
                Popis <strong class="text-[var(--ink)]">{{ $roster->name }}</strong>: {{ $entries->count() }} prijava bit će obrisano. Polaznici ostaju u aplikaciji. Ovo se ne može poništiti.
            </p>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" wire:click="cancelDelete" class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                <button type="button" wire:click="delete" class="h-11 px-4 rounded-[var(--r-control)] text-white text-[15px] font-semibold transition hover:brightness-110" style="background:var(--danger)">Da, obriši</button>
            </div>
        </div>
    </div>
</div>
