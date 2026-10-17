<div>
    @php
        $badge = fn (\App\Enums\ChargeStatus $s): string => match ($s) {
            \App\Enums\ChargeStatus::Paid => 'background:var(--st-valid-bg);color:var(--st-valid-fg)',
            \App\Enums\ChargeStatus::Partial => 'background:var(--st-soon-bg);color:var(--st-soon-fg)',
            \App\Enums\ChargeStatus::Overdue => 'background:var(--danger-tint);color:var(--danger)',
            default => 'background:var(--neutral-pill);color:var(--ink2)',
        };
    @endphp

    {{-- Saldo --}}
    <div class="flex items-center gap-4 flex-wrap mb-3 text-[14px]">
        <span class="text-[var(--ink2)]">Otvoreno: <strong class="text-[var(--ink)]">{{ \App\Support\Money::format($owedCents) }}</strong></span>
        @if ($unallocatedCents > 0)
            <span class="text-[var(--acc)]">Pretplata: <strong>{{ \App\Support\Money::format($unallocatedCents) }}</strong></span>
        @endif
        @can('manage-payments')
            @if (! $showPayment)
                <button type="button" wire:click="openPayment"
                        class="ml-auto h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:brightness-110">
                    Evidentiraj uplatu
                </button>
            @endif
        @endcan
    </div>

    {{-- Forma uplate --}}
    @can('manage-payments')
        @if ($showPayment)
            <div class="rounded-[var(--r-control)] border border-[var(--line)] p-3 flex flex-col gap-3 mb-4">
                <div class="text-[13px] font-semibold text-[var(--ink2)]">Nova uplata</div>
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(140px,100%),1fr))]">
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Iznos (EUR) *</label>
                        <input type="text" inputmode="decimal" wire:model.blur="payAmount"
                               class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none"
                               style="border-color:{{ $errors->has('payAmount') ? 'var(--danger)' : 'var(--line2)' }}">
                        @error('payAmount') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Način *</label>
                        <select wire:model="payMethod" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                            <option value="bank">Transakcijski račun</option>
                            <option value="cash">Gotovina</option>
                            <option value="other">Ostalo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Datum *</label>
                        <input type="date" wire:model="payPaidAt" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Poziv na broj / napomena</label>
                    <input type="text" wire:model="payBankReference" placeholder="poziv na broj (opcionalno)" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                </div>

                @php $openForPay = $charges->reject(fn ($c) => $c->isWrittenOff() || $c->outstandingCents() <= 0); @endphp
                @if ($openForPay->isNotEmpty())
                    <div>
                        <div class="text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Rasporedi na (najstarije prvo):</div>
                        <div class="flex flex-col gap-1">
                            @foreach ($openForPay->sortBy('period') as $c)
                                <label class="flex items-center gap-2 text-[14px] text-[var(--ink2)] select-none">
                                    <input type="checkbox" value="{{ $c->id }}" wire:model="paySelected" class="w-4 h-4">
                                    {{ $c->period }} · {{ $c->feePlan->name }} · otvoreno {{ \App\Support\Money::format($c->outstandingCents()) }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[12px] text-[var(--ink3)] mt-1">Višak iznad odabranih zaduženja ostaje kao pretplata.</p>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="recordPayment" class="h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:brightness-110">Spremi uplatu</button>
                    <button type="button" wire:click="cancelPayment" class="h-10 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                </div>
            </div>
        @endif
    @endcan

    {{-- Zaduženja --}}
    @if ($charges->isNotEmpty())
        <div class="flex flex-col gap-2 mb-4">
            @foreach ($charges as $c)
                @php $status = $c->status(); @endphp
                <div class="rounded-[var(--r-control)] border border-[var(--line)] px-3 py-2.5 flex items-center justify-between gap-3" wire:key="charge-{{ $c->id }}">
                    <div class="min-w-0">
                        <div class="text-[14px] font-semibold text-[var(--ink)] truncate">
                            {{ $c->period }} · {{ \App\Support\Money::format($c->amount_cents) }}
                            <span class="ml-1 inline-block rounded-[var(--r-tag)] px-1.5 text-[11px] font-semibold align-middle" style="{{ $badge($status) }}">{{ $status->label() }}</span>
                        </div>
                        <div class="text-[13px] text-[var(--ink3)] mt-0.5">
                            {{ $c->feePlan->name }}@if ($c->due_date) · rok {{ $c->due_date->format('d.m.Y.') }}@endif
                            @if ($c->outstandingCents() > 0 && $c->outstandingCents() < $c->amount_cents) · otvoreno {{ \App\Support\Money::format($c->outstandingCents()) }}@endif
                            @if ($c->isWrittenOff() && $c->written_off_reason) · {{ $c->written_off_reason }}@endif
                        </div>
                    </div>
                    @can('manage-payments')
                        @if (! $c->isWrittenOff() && $c->outstandingCents() > 0)
                            <button type="button" wire:click="startWriteOff({{ $c->id }})" class="shrink-0 h-9 px-3 rounded-[var(--r-control)] text-[13px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Otpiši</button>
                        @endif
                    @endcan
                </div>

                @if ($writeOffChargeId === $c->id)
                    <div class="rounded-[var(--r-control)] border border-[var(--line)] p-3 flex flex-col gap-2" wire:key="wo-{{ $c->id }}">
                        <label class="block text-[13px] font-semibold text-[var(--ink2)]">Razlog otpisa *</label>
                        <input type="text" wire:model="writeOffReason" class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none" style="border-color:{{ $errors->has('writeOffReason') ? 'var(--danger)' : 'var(--line2)' }}">
                        @error('writeOffReason') <p class="text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="confirmWriteOff" class="h-10 px-4 rounded-[var(--r-control)] text-white text-[14px] font-semibold" style="background:var(--danger)">Otpiši</button>
                            <button type="button" wire:click="cancelWriteOff" class="h-10 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink2)] hover:bg-[var(--bg)]">Odustani</button>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Povijest uplata --}}
    @if ($payments->isNotEmpty())
        <div class="flex flex-col gap-1.5 mb-4">
            <div class="text-[13px] font-semibold text-[var(--ink2)]">Uplate</div>
            @foreach ($payments as $p)
                <div class="flex items-center justify-between gap-3 text-[13px]" wire:key="pay-{{ $p->id }}">
                    <span class="text-[var(--ink2)]">
                        {{ $p->paid_at->format('d.m.Y.') }} · {{ \App\Support\Money::format($p->amount_cents) }}
                        · {{ ['bank' => 'TR', 'cash' => 'gotovina', 'other' => 'ostalo'][$p->method] ?? $p->method }}
                        @if ($p->isReversal()) <span class="text-[var(--ink3)]">(storno)</span> @endif
                    </span>
                    @can('manage-payments')
                        @if (! $p->isReversal() && ! in_array($p->id, $reversedIds, true))
                            <button type="button" wire:click="reversePayment({{ $p->id }})" class="text-[var(--danger)] font-semibold hover:underline">Storniraj</button>
                        @endif
                    @endcan
                </div>
            @endforeach
        </div>
    @endif

    {{-- Ručno jednokratno zaduženje --}}
    @can('assign-fee-plan')
        <div class="rounded-[var(--r-control)] border border-[var(--line)] p-3 flex flex-col gap-3">
            <div class="text-[13px] font-semibold text-[var(--ink2)]">Dodaj zaduženje (ručno)</div>
            <div>
                <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Tarifa *</label>
                <select wire:model.live="mcFeePlanId" class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none" style="border-color:{{ $errors->has('mcFeePlanId') ? 'var(--danger)' : 'var(--line2)' }}">
                    <option value="">— odaberi —</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} · {{ $plan->type->label() }} · {{ \App\Support\Money::format($plan->amount_cents) }}</option>
                    @endforeach
                </select>
                @error('mcFeePlanId') <p class="mt-1.5 text-[13px] font-medium text-[var(--danger)]">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(140px,100%),1fr))]">
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Mjesec *</label>
                    <input type="month" wire:model="mcPeriod" class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none" style="border-color:{{ $errors->has('mcPeriod') ? 'var(--danger)' : 'var(--line2)' }}">
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Iznos (EUR) *</label>
                    <input type="text" inputmode="decimal" wire:model.blur="mcAmount" class="h-11 w-full rounded-[var(--r-control)] border bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none" style="border-color:{{ $errors->has('mcAmount') ? 'var(--danger)' : 'var(--line2)' }}">
                </div>
                <div>
                    <label class="block text-[13px] font-semibold text-[var(--ink2)] mb-1.5">Rok plaćanja</label>
                    <input type="date" wire:model="mcDueDate" class="h-11 w-full rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] px-3 text-[15px] text-[var(--ink)] focus:outline-none">
                </div>
            </div>
            <div>
                <button type="button" wire:click="addCharge" class="h-10 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[14px] font-semibold transition hover:brightness-110">Dodaj zaduženje</button>
            </div>
        </div>
    @endcan
</div>
