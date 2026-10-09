<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-[34px] font-semibold tracking-[-0.02em] text-[var(--ink)] leading-tight">Polaznici</h1>
            <p class="text-[15px] text-[var(--ink2)] mt-0.5">{{ $subtitle }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="export" wire:target="export" wire:loading.attr="disabled"
                    class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] font-semibold text-[15px] text-[var(--ink2)] transition hover:bg-[var(--bg)] inline-flex items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>
                <span wire:loading.remove wire:target="export">Izvezi u Excel</span>
                <span wire:loading wire:target="export">Izvoz…</span>
            </button>
            <button type="button" wire:click="$dispatch('new-student', { locationSlug: @js($location) })"
                    class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[15px] transition hover:brightness-110">
                + Novi polaznik
            </button>
        </div>
    </div>

    {{-- Age chips --}}
    <div class="flex gap-2 mt-5 overflow-x-auto pb-1">
        @foreach ($ageChips as $chip)
            @php $active = $ageKey === $chip['key']; @endphp
            <button type="button"
                    wire:click="{{ $chip['key'] === '' ? "\$set('ageKey', '')" : "setAge('{$chip['key']}')" }}"
                    class="h-10 shrink-0 inline-flex items-center gap-1.5 rounded-[var(--r-pill)] px-3.5 text-[14px] font-semibold transition"
                    @style([
                        'background:var(--acc);color:#fff' => $active,
                        'background:var(--surface);color:var(--ink);border:1px solid var(--line2)' => ! $active,
                    ])>
                {{ $chip['label'] }}
                <span @class(['opacity-80' => $active, 'text-[var(--ink3)]' => ! $active])>{{ $chip['count'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-3 mt-3">
        <div class="relative grow max-w-[300px] min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ink3)" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Ime, roditelj, OIB…"
                   aria-label="Pretraga polaznika"
                   class="w-full h-11 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] pl-9 pr-3 text-[15px] text-[var(--ink)] focus:outline-none">
        </div>

        <select wire:model.live="location" aria-label="Lokacija"
                class="h-11 max-w-[190px] rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] max-[760px]:max-w-none max-[760px]:w-full">
            <option value="">Sve lokacije</option>
            @foreach ($locations as $loc)
                <option value="{{ $loc->slug }}">{{ $loc->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="group" aria-label="Grupa"
                class="h-11 max-w-[190px] rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] max-[760px]:max-w-none max-[760px]:w-full">
            <option value="">Sve grupe</option>
            @foreach ($groups as $grp)
                <option value="{{ $grp->slug }}">{{ $grp->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="belt" aria-label="Pojas"
                class="h-11 max-w-[190px] rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] max-[760px]:max-w-none max-[760px]:w-full">
            <option value="">Svi pojasevi</option>
            @foreach ($belts as $b)
                <option value="{{ $b->value }}">{{ $b->label() }}</option>
            @endforeach
        </select>

        <button type="button" wire:click="$toggle('expiredOnly')" aria-pressed="{{ $expiredOnly ? 'true' : 'false' }}"
                class="h-11 inline-flex items-center gap-2 rounded-[var(--r-pill)] px-3.5 text-[14px] font-semibold transition"
                @style([
                    'background:var(--acc);color:#fff' => $expiredOnly,
                    'background:var(--surface);color:var(--ink);border:1px solid var(--line2)' => ! $expiredOnly,
                ])>
            <span class="w-2 h-2 rounded-full" style="background:var(--dot-expired)" aria-hidden="true"></span>
            Liječnički istekao <span @class(['opacity-80' => $expiredOnly, 'text-[var(--ink3)]' => ! $expiredOnly])>{{ $expiredCount }}</span>
        </button>

        <button type="button" wire:click="$toggle('toRegisterOnly')" aria-pressed="{{ $toRegisterOnly ? 'true' : 'false' }}"
                class="h-11 inline-flex items-center gap-2 rounded-[var(--r-pill)] px-3.5 text-[14px] font-semibold transition"
                @style([
                    'background:var(--acc);color:#fff' => $toRegisterOnly,
                    'background:var(--surface);color:var(--ink);border:1px solid var(--line2)' => ! $toRegisterOnly,
                ])>
            <span class="w-2 h-2 rounded-full" style="background:var(--dot-register)" aria-hidden="true"></span>
            Treba upisati <span @class(['opacity-80' => $toRegisterOnly, 'text-[var(--ink3)]' => ! $toRegisterOnly])>{{ $toRegisterCount }}</span>
        </button>

        @if ($filtersActive)
            <button type="button" wire:click="resetFilters"
                    class="h-11 px-2 text-[14px] font-semibold text-[var(--acc)] transition hover:underline">
                Poništi filtre
            </button>
        @endif
    </div>

    {{-- Desktop table --}}
    <div class="mt-4 bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] overflow-hidden max-[760px]:hidden">
        <table class="w-full border-collapse">
            <thead>
                <tr class="text-left text-[12px] font-semibold uppercase tracking-[0.05em] text-[var(--ink3)]">
                    <th class="px-4 py-3 font-semibold" style="width:250px">Polaznik</th>
                    <th class="px-4 py-3 font-semibold" style="width:210px">Pojas</th>
                    <th class="px-4 py-3 font-semibold" style="width:180px">Grupa</th>
                    <th class="px-4 py-3 font-semibold" style="width:190px">Uzrast</th>
                    <th class="px-4 py-3 font-semibold" style="width:150px">Liječnički</th>
                    <th class="px-4 py-3 font-semibold" style="width:200px">Roditelj / kontakt</th>
                    <th class="px-4 py-3 font-semibold" style="width:104px"><span class="sr-only">Radnje</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $s)
                    @php $age = $s->age_category; @endphp
                    <tr class="border-t border-[var(--line)] transition hover:bg-[var(--row-hover)]" wire:key="row-{{ $s->id }}">
                        <td class="px-4 py-3 align-top">
                            <button type="button" wire:click="$dispatch('edit-student', { id: {{ $s->id }} })"
                                    class="text-left text-[15px] font-semibold text-[var(--ink)]">
                                {{ $s->last_name }} {{ $s->first_name }}
                            </button>
                            @if ($s->flag_t)
                                <span class="ml-1 inline-block rounded-[var(--r-tag)] px-1.5 text-[11px] font-semibold align-middle" style="background:var(--neutral-pill);color:var(--ink2)">t</span>
                            @endif
                            <div class="text-[13px] text-[var(--ink3)] mt-0.5">{{ $s->location?->name ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <x-belt-stripe :kyu="$s->belt_kyu?->value" />
                            <div class="mt-1"><x-next-grade-badge :status="$s->next_grade_status" :kyu="$s->next_grade_kyu" /></div>
                        </td>
                        <td class="px-4 py-3 align-top text-[14px] {{ $s->trainingGroup ? 'text-[var(--ink)]' : 'text-[var(--ink3)]' }}">
                            {{ $s->trainingGroup?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 align-top">
                            @if ($age)
                                <div class="text-[14px] font-medium text-[var(--ink)]">{{ $age->label }}</div>
                                <div class="text-[13px] text-[var(--ink3)] mt-0.5">{{ $s->birth_date->format('d.m.Y.') }} · {{ $age->age }} g.</div>
                            @else
                                <div class="text-[14px] text-[var(--ink3)]">—</div>
                                <div class="text-[13px] text-[var(--ink3)] mt-0.5">Nema datuma rođenja</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <x-medical-pill :status="$s->medical_status" />
                            @php
                                $status = $s->medical_status;
                                $date = $s->medical_valid_until?->format('d.m.Y.');
                            @endphp
                            @if ($date && in_array($status, [\App\Support\MedicalStatus::Valid, \App\Support\MedicalStatus::SoonExpiring], true))
                                <div class="text-[13px] text-[var(--ink3)] mt-1">do {{ $date }}</div>
                            @elseif ($date && $status === \App\Support\MedicalStatus::Expired)
                                <div class="text-[13px] text-[var(--ink3)] mt-1">{{ $date }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top text-[14px]">
                            @if ($s->parent_name)
                                <div class="text-[var(--ink)]">{{ $s->parent_name }}</div>
                            @endif
                            @if ($s->parent_phone)
                                <div class="text-[var(--ink3)] {{ $s->parent_name ? 'mt-0.5' : '' }}">{{ $s->formatted_phone }}</div>
                            @endif
                            @unless ($s->parent_name || $s->parent_phone)
                                <span class="text-[var(--ink3)]">—</span>
                            @endunless
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center gap-1 justify-end">
                                <button type="button" wire:click="$dispatch('edit-student', { id: {{ $s->id }} })"
                                        class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] transition hover:bg-[var(--tint)]"
                                        aria-label="Uredi: {{ $s->last_name }} {{ $s->first_name }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                </button>
                                <button type="button" wire:click="$dispatch('confirm-delete-student', { id: {{ $s->id }} })"
                                        class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] transition hover:bg-[var(--danger-tint)]"
                                        aria-label="Obriši: {{ $s->last_name }} {{ $s->first_name }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center">
                            <div class="font-display text-[20px] font-semibold text-[var(--ink)]">Nema polaznika za zadane filtre</div>
                            <p class="text-[14px] text-[var(--ink2)] mt-1">Pokušaj s drugim pojmom ili makni neki filter.</p>
                            @if ($filtersActive)
                                <button type="button" wire:click="resetFilters" class="mt-3 h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[14px]">Poništi filtre</button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="mt-4 flex flex-col gap-3 min-[761px]:hidden">
        @forelse ($rows as $s)
            @php $age = $s->age_category; @endphp
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-4" wire:key="card-{{ $s->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <button type="button" wire:click="$dispatch('edit-student', { id: {{ $s->id }} })"
                                class="text-left text-[15px] font-semibold text-[var(--ink)]">
                            {{ $s->last_name }} {{ $s->first_name }}
                        </button>
                        @if ($s->flag_t)
                            <span class="ml-1 inline-block rounded-[var(--r-tag)] px-1.5 text-[11px] font-semibold align-middle" style="background:var(--neutral-pill);color:var(--ink2)">t</span>
                        @endif
                        <div class="text-[13px] text-[var(--ink3)] mt-0.5">{{ $s->location?->name ?? '—' }}</div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" wire:click="$dispatch('edit-student', { id: {{ $s->id }} })"
                                class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)]" aria-label="Uredi: {{ $s->last_name }} {{ $s->first_name }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                        <button type="button" wire:click="$dispatch('confirm-delete-student', { id: {{ $s->id }} })"
                                class="w-11 h-11 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)]" aria-label="Obriši: {{ $s->last_name }} {{ $s->first_name }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3 flex-wrap">
                    <x-belt-stripe :kyu="$s->belt_kyu?->value" />
                    <x-next-grade-badge :status="$s->next_grade_status" :kyu="$s->next_grade_kyu" />
                </div>
                <div class="flex items-center gap-2 mt-2 flex-wrap text-[14px]">
                    <span class="text-[var(--ink)]">{{ $age?->label ?? 'Bez datuma rođenja' }}</span>
                    <span class="inline-flex items-center gap-1 text-[var(--ink2)]">
                        <span>Liječnički:</span> <x-medical-pill :status="$s->medical_status" />
                    </span>
                </div>
            </div>
        @empty
            <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-8 text-center">
                <div class="font-display text-[20px] font-semibold text-[var(--ink)]">Nema polaznika za zadane filtre</div>
                <p class="text-[14px] text-[var(--ink2)] mt-1">Pokušaj s drugim pojmom ili makni neki filter.</p>
                @if ($filtersActive)
                    <button type="button" wire:click="resetFilters" class="mt-3 h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white font-semibold text-[14px]">Poništi filtre</button>
                @endif
            </div>
        @endforelse
    </div>

    <livewire:student-form />
    <livewire:student-delete />
</div>
