<div>
    <div x-cloak x-show="$wire.open" class="fixed inset-0 z-50" @keydown.escape.window="$wire.open && $wire.close()">
        {{-- Overlay --}}
        <div x-show="$wire.open" x-transition.opacity.duration.200ms
             class="absolute inset-0" style="background:var(--overlay-drawer)" @click="$wire.close()"></div>

        {{-- Drawer --}}
        <div x-show="$wire.open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 h-full bg-[var(--surface)] flex flex-col"
             style="width:min(680px,100%);box-shadow:var(--shadow-drawer)"
             role="dialog" aria-modal="true" aria-labelledby="drawer-title">

            <form wire:submit="save" class="flex flex-col h-full">
                {{-- Header --}}
                <header class="flex items-center justify-between gap-4 px-6 h-[68px] border-b border-[var(--line)] shrink-0">
                    <h2 id="drawer-title" class="font-display text-[24px] font-semibold text-[var(--ink)]">
                        {{ $studentId ? 'Uredi polaznika' : 'Novi polaznik' }}
                    </h2>
                    <button type="button" wire:click="close" aria-label="Zatvori"
                            class="w-10 h-10 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] hover:bg-[var(--bg)]">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </header>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 flex flex-col gap-6">
                    {{-- 1 · Osobni podaci --}}
                    <section>
                        <h3 class="font-display text-[16px] font-semibold text-[var(--ink)] mb-3">Osobni podaci</h3>
                        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr))]">
                            <div>
                                <label for="f-last" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Prezime *</label>
                                <input id="f-last" type="text" wire:model.blur="lastName"
                                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                       style="border-color:{{ $errors->has('lastName') ? 'var(--danger)' : 'var(--line2)' }}">
                                @error('lastName') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="f-first" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Ime *</label>
                                <input id="f-first" type="text" wire:model.blur="firstName"
                                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                       style="border-color:{{ $errors->has('firstName') ? 'var(--danger)' : 'var(--line2)' }}">
                                @error('firstName') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="f-birth" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Datum rođenja</label>
                                <input id="f-birth" type="date" wire:model.live="birthDate"
                                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                       style="border-color:{{ $errors->has('birthDate') ? 'var(--danger)' : 'var(--line2)' }}">
                                @error('birthDate') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="f-oib" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">OIB</label>
                                <input id="f-oib" type="text" inputmode="numeric" wire:model.blur="oib" placeholder="11 znamenki"
                                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                       style="border-color:{{ $errors->has('oib') ? 'var(--danger)' : 'var(--line2)' }}">
                                @error('oib') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Live uzrast info box --}}
                        <div class="mt-4 rounded-[var(--r-control)] p-4" style="background:var(--bg)">
                            <div class="flex flex-wrap gap-x-10 gap-y-2">
                                <div>
                                    <div class="text-[12px] font-semibold uppercase tracking-[0.04em] text-[var(--ink3)]">Uzrastna kategorija</div>
                                    <div class="text-[15px] font-semibold text-[var(--ink)] mt-0.5">{{ $ageCategory?->label ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[12px] font-semibold uppercase tracking-[0.04em] text-[var(--ink3)]">Prelazi u višu kategoriju</div>
                                    <div class="text-[15px] font-semibold text-[var(--ink)] mt-0.5">{{ $ageCategory?->crossover?->format('d.m.Y.') ?? '—' }}</div>
                                </div>
                            </div>
                            <p class="text-[13px] text-[var(--ink3)] mt-2">Računa se automatski iz datuma rođenja (WKF/HKS), ne unosi se ručno.</p>
                        </div>
                    </section>

                    {{-- 2 · Karate --}}
                    <section class="border-t border-[var(--line)] pt-5">
                        <h3 class="font-display text-[16px] font-semibold text-[var(--ink)] mb-3">Karate</h3>
                        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr))]">
                            <div>
                                <label for="f-belt" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Pojas</label>
                                <div class="flex items-center gap-2">
                                    <x-belt-stripe :kyu="$belt === '' ? null : (int) $belt" :show-label="false" />
                                    <select id="f-belt" wire:model.live="belt"
                                            class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                                        <option value="">— nije uneseno</option>
                                        @foreach ($belts as $b)
                                            <option value="{{ $b->value }}">{{ $b->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="f-group" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Grupa</label>
                                <select id="f-group" wire:model.live="trainingGroupId"
                                        class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                                    <option value="">— bez grupe</option>
                                    @foreach ($groups as $grp)
                                        <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="f-loc" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Lokacija / termin</label>
                            <select id="f-loc" wire:model.live="locationId"
                                    class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                                <option value="">— nije odabrano</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <span class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Upis sljedećeg stupnja</span>
                            <div class="grid grid-cols-3 gap-1 max-w-[400px] p-1 rounded-[var(--r-control)]" style="background:var(--bg)" role="group" aria-label="Upis sljedećeg stupnja">
                                @foreach (['none' => 'Nije potrebno', 'to_register' => 'Treba upisati', 'registered' => 'Već upisan'] as $val => $lbl)
                                    <button type="button" wire:click="selectNextGrade('{{ $val }}')" aria-pressed="{{ $nextGradeStatus === $val ? 'true' : 'false' }}"
                                            class="h-9 rounded-[8px] text-[14px] font-semibold transition"
                                            @style(['background:var(--acc);color:#fff' => $nextGradeStatus === $val, 'color:var(--ink)' => $nextGradeStatus !== $val])>
                                        {{ $lbl }}
                                    </button>
                                @endforeach
                            </div>
                            @if ($nextGradeStatus === 'registered')
                                <div class="mt-3 max-w-[200px]">
                                    <label for="f-ngk" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Upisan na</label>
                                    <select id="f-ngk" wire:model.live="nextGradeKyu"
                                            class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                            style="border-color:{{ $errors->has('nextGradeKyu') ? 'var(--danger)' : 'var(--line2)' }}">
                                        <option value="">—</option>
                                        @for ($k = 1; $k <= 9; $k++)
                                            <option value="{{ $k }}">{{ $k }}. kyu</option>
                                        @endfor
                                    </select>
                                    @error('nextGradeKyu') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- 3 · Liječnički pregled --}}
                    <section class="border-t border-[var(--line)] pt-5">
                        <h3 class="font-display text-[16px] font-semibold text-[var(--ink)] mb-3">Liječnički pregled</h3>
                        <div>
                            <label for="f-med" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Vrijedi do</label>
                            <div class="flex items-center gap-3 flex-wrap">
                                <input id="f-med" type="date" wire:model.live="medicalValidUntil" @disabled($hasNoMedical)
                                       class="h-11 max-w-[220px] rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none disabled:opacity-50"
                                       style="border-color:{{ $errors->has('medicalValidUntil') ? 'var(--danger)' : 'var(--line2)' }}">
                                <x-medical-pill :status="$medicalStatus" />
                            </div>
                            @error('medicalValidUntil') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 mt-3 text-[14px] text-[var(--ink2)] select-none">
                            <input type="checkbox" wire:model.live="hasNoMedical" class="w-4 h-4"> Nema liječnički pregled
                        </label>
                    </section>

                    {{-- 4 · Roditelj / kontakt --}}
                    <section class="border-t border-[var(--line)] pt-5">
                        <h3 class="font-display text-[16px] font-semibold text-[var(--ink)] mb-3">Roditelj / kontakt</h3>
                        <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr))]">
                            <div>
                                <label for="f-pname" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Roditelj / skrbnik</label>
                                <input id="f-pname" type="text" wire:model.blur="parentName"
                                       class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                            </div>
                            <div>
                                <label for="f-phone" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Mobitel</label>
                                <input id="f-phone" type="tel" wire:model.blur="parentPhone" placeholder="091 234 5678"
                                       class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="f-email" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">E-mail</label>
                            <input id="f-email" type="email" wire:model.blur="parentEmail"
                                   class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                                   style="border-color:{{ $errors->has('parentEmail') ? 'var(--danger)' : 'var(--line2)' }}">
                            @error('parentEmail') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    {{-- 5 · Ostalo --}}
                    <section class="border-t border-[var(--line)] pt-5">
                        <h3 class="font-display text-[16px] font-semibold text-[var(--ink)] mb-3">Ostalo</h3>
                        <div>
                            <label for="f-note" class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Napomena</label>
                            <textarea id="f-note" rows="3" wire:model.blur="note"
                                      class="w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 py-2 text-[15px] text-[var(--ink)] focus:outline-none"
                                      style="border-color:{{ $errors->has('note') ? 'var(--danger)' : 'var(--line2)' }}"></textarea>
                            @error('note') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 mt-3 text-[14px] text-[var(--ink2)] select-none">
                            <input type="checkbox" wire:model="flagT" class="w-4 h-4"> Oznaka „t”
                        </label>
                    </section>

                    {{-- 6 · Natjecanja i rezultati (samo kod uređivanja postojećeg polaznika) --}}
                    @if ($studentId)
                        <section class="border-t border-[var(--line)] pt-5">
                            <h3 class="font-display text-[16px] font-semibold text-[var(--ink)] mb-3">Natjecanja i rezultati</h3>
                            <livewire:student-results :student-id="$studentId" :key="'results-'.$studentId" />
                        </section>
                    @endif
                </div>

                {{-- Footer --}}
                <footer class="shrink-0 border-t border-[var(--line)] px-6 py-4 flex items-center gap-3 flex-wrap">
                    @if ($studentId)
                        <button type="button" wire:click="requestDelete"
                                class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--danger)] inline-flex items-center gap-2"
                                style="border:1px solid var(--danger-border)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            Obriši polaznika
                        </button>
                    @endif

                    <div class="ml-auto flex items-center gap-2">
                        @if ($submitted && $errors->any())
                            <span role="alert" class="text-[13px] font-medium text-[var(--danger)] mr-1">Provjeri označena polja</span>
                        @endif
                        <button type="button" wire:click="close"
                                class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[15px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">
                            Odustani
                        </button>
                        <button type="submit"
                                class="h-11 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[15px] font-semibold transition hover:brightness-110">
                            {{ $studentId ? 'Spremi promjene' : 'Dodaj polaznika' }}
                        </button>
                    </div>
                </footer>
            </form>
        </div>
    </div>
</div>
