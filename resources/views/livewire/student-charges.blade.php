<div>
    {{-- Postojeća zaduženja --}}
    @if ($charges->isNotEmpty())
        <div class="flex flex-col gap-2 mb-4">
            @foreach ($charges as $c)
                <div class="rounded-[var(--r-control)] border border-[var(--line)] px-3 py-2.5 flex items-center justify-between gap-3" wire:key="charge-{{ $c->id }}">
                    <div class="min-w-0">
                        <div class="text-[14px] font-semibold text-[var(--ink)] truncate">
                            {{ $c->period }} · {{ \App\Support\Money::format($c->amount_cents) }}
                            @if ($c->isWrittenOff())
                                <span class="ml-1 inline-block rounded-[var(--r-tag)] px-1.5 text-[11px] font-semibold align-middle" style="background:var(--neutral-pill);color:var(--ink2)">otpisano</span>
                            @endif
                        </div>
                        <div class="text-[13px] text-[var(--ink3)] mt-0.5">
                            {{ $c->feePlan->name }}@if ($c->due_date) · rok {{ $c->due_date->format('d.m.Y.') }}@endif
                            @if ($c->reference) · {{ $c->reference }}@endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Ručno jednokratno zaduženje (npr. upisnina) --}}
    <div class="rounded-[var(--r-control)] border border-[var(--line)] p-3 flex flex-col gap-3">
        <div class="text-[13px] font-semibold text-[var(--ink2)]">Dodaj zaduženje (ručno)</div>

        <div>
            <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Tarifa *</label>
            <select wire:model.live="feePlanId"
                    class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                    style="border-color:{{ $errors->has('feePlanId') ? 'var(--danger)' : 'var(--line2)' }}">
                <option value="">— odaberi —</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $plan->type->label() }} · {{ \App\Support\Money::format($plan->amount_cents) }}</option>
                @endforeach
            </select>
            @error('feePlanId') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(140px,100%),1fr))]">
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Mjesec *</label>
                <input type="month" wire:model="period"
                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                       style="border-color:{{ $errors->has('period') ? 'var(--danger)' : 'var(--line2)' }}">
                @error('period') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Iznos (EUR) *</label>
                <input type="text" inputmode="decimal" wire:model.blur="amount" placeholder="npr. 20,00"
                       class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                       style="border-color:{{ $errors->has('amount') ? 'var(--danger)' : 'var(--line2)' }}">
                @error('amount') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Rok plaćanja</label>
                <input type="date" wire:model="dueDate"
                       class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
            </div>
        </div>

        <div>
            <button type="button" wire:click="save"
                    class="h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:brightness-110">
                Dodaj zaduženje
            </button>
        </div>
    </div>
</div>
