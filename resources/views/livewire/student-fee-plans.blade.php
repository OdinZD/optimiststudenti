<div>
    {{-- Postojeće članarine --}}
    @if ($assignments->isNotEmpty())
        <div class="flex flex-col gap-2 mb-4">
            @foreach ($assignments as $a)
                <div class="rounded-[var(--r-control)] border border-[var(--line)] px-3 py-2.5 flex items-center justify-between gap-3" wire:key="sfp-{{ $a->id }}">
                    <div class="min-w-0">
                        <div class="text-[14px] font-semibold text-[var(--ink)] truncate">
                            {{ $a->feePlan->name }}
                            <span class="text-[var(--ink3)] font-normal">· {{ $a->feePlan->type->label() }}</span>
                        </div>
                        <div class="text-[13px] text-[var(--ink3)] mt-0.5">
                            {{ \App\Support\Money::format($a->effectiveAmountCents()) }}
                            @if ($a->discount_percent !== null) <span class="text-[var(--acc)]">(−{{ $a->discount_percent }}%)</span>
                            @elseif ($a->discount_cents !== null) <span class="text-[var(--acc)]">(−{{ \App\Support\Money::format($a->discount_cents) }})</span>
                            @endif
                            · {{ $a->valid_from->format('d.m.Y.') }} – {{ $a->valid_until?->format('d.m.Y.') ?? 'dalje' }}
                            @if ($a->payer_name) · {{ $a->payer_name }} @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" wire:click="editAssignment({{ $a->id }})" aria-label="Uredi članarinu"
                                class="w-9 h-9 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--ink2)] hover:bg-[var(--tint)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                        <button type="button" wire:click="deleteAssignment({{ $a->id }})" aria-label="Ukloni članarinu"
                                class="w-9 h-9 inline-flex items-center justify-center rounded-[var(--r-control)] text-[var(--danger)] hover:bg-[var(--danger-tint)]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Forma: dodaj / uredi članarinu --}}
    <div class="rounded-[var(--r-control)] border border-[var(--line)] p-3 flex flex-col gap-3">
        <div class="text-[13px] font-semibold text-[var(--ink2)]">{{ $editingId ? 'Uredi članarinu' : 'Dodaj članarinu' }}</div>

        <div>
            <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Tarifa *</label>
            <select wire:model="feePlanId"
                    class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                    style="border-color:{{ $errors->has('feePlanId') ? 'var(--danger)' : 'var(--line2)' }}">
                <option value="">— odaberi —</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $plan->type->label() }} · {{ \App\Support\Money::format($plan->amount_cents) }}</option>
                @endforeach
            </select>
            @error('feePlanId') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(160px,100%),1fr))]">
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Popust</label>
                <select wire:model.live="discountType"
                        class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    <option value="none">Bez popusta</option>
                    <option value="percent">Postotak (%)</option>
                    <option value="fixed">Fiksni iznos (EUR)</option>
                </select>
            </div>
            @if ($discountType !== 'none')
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">{{ $discountType === 'percent' ? 'Postotak' : 'Iznos (EUR)' }}</label>
                    <input type="text" inputmode="decimal" wire:model.blur="discountValue"
                           placeholder="{{ $discountType === 'percent' ? 'npr. 50' : 'npr. 10,00' }}"
                           class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                </div>
            @endif
        </div>

        <div>
            <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Platitelj</label>
            <input type="text" wire:model="payerName" placeholder="Ime platitelja"
                   class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
        </div>

        <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(160px,100%),1fr))]">
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Vrijedi od *</label>
                <input type="date" wire:model.blur="validFrom"
                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                       style="border-color:{{ $errors->has('validFrom') ? 'var(--danger)' : 'var(--line2)' }}">
                @error('validFrom') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Vrijedi do</label>
                <input type="date" wire:model.blur="validUntil"
                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                       style="border-color:{{ $errors->has('validUntil') ? 'var(--danger)' : 'var(--line2)' }}">
                @error('validUntil') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="save"
                    class="h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:brightness-110">
                {{ $editingId ? 'Spremi promjene' : 'Dodaj' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="cancelEdit"
                        class="h-10 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">
                    Odustani
                </button>
            @endif
        </div>
    </div>
</div>
