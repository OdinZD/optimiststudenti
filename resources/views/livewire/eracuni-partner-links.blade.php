<div class="max-w-[860px]">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-[34px] font-semibold tracking-[-0.02em] text-[var(--ink)] leading-tight">Povezivanje roditelja</h1>
            <p class="text-[15px] text-[var(--ink2)] mt-0.5 mb-5">Članarine · poveži platitelje (roditelje) s partnerima u eRačunima</p>
        </div>
        <button type="button" wire:click="refreshPartners" wire:loading.attr="disabled"
                class="h-11 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[14px] font-semibold text-[var(--ink)] transition hover:bg-[var(--bg)] disabled:opacity-60">
            <span wire:loading.remove wire:target="refreshPartners">Osvježi iz eRačuna</span>
            <span wire:loading wire:target="refreshPartners">Pokrećem…</span>
        </button>
    </div>

    @unless ($hasCredentials)
        <div class="rounded-[var(--r-control)] px-4 py-3 mb-5 text-[14px]" style="background:var(--danger-tint);color:var(--danger)">
            Nisu upisane eRačuni vjerodajnice. Trenutno radi <strong>mock</strong> (lažni) klijent — podaci ispod su primjeri.
            Vjerodajnice se postavljaju u @if (Route::has('fee-settings'))<a href="{{ route('fee-settings') }}" class="underline">Postavkama</a>@else Postavkama @endif.
        </div>
    @endunless

    <p class="text-[13px] text-[var(--ink3)] mb-4">
        @if ($fetchedAt)
            Zadnje dohvaćeno: <strong>{{ $fetchedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</strong> · {{ $partnerCount }} partnera ·
        @else
            Partneri još nisu dohvaćeni ·
        @endif
        povezano {{ $linkedCount }}, za povezivanje {{ $unlinkedCount }}
    </p>

    @if ($rows->isEmpty())
        <div class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-8 text-center text-[15px] text-[var(--ink2)]">
            Nema roditelja s aktivnom tarifom „račun na e-mail". Dodaj način plaćanja djeci u karticama.
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($rows as $row)
                @php($c = $row['candidate'])
                <section class="bg-[var(--surface)] rounded-[var(--r-card)] border border-[var(--line)] p-4 flex items-center justify-between gap-4 flex-wrap">
                    <div class="min-w-[200px]">
                        <div class="text-[15px] font-semibold text-[var(--ink)]">{{ $c->name }}</div>
                        <div class="text-[13px] text-[var(--ink2)]">{{ $c->email }}</div>
                    </div>

                    @if ($row['link'])
                        {{-- Povezano --}}
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 h-7 text-[13px] font-semibold" style="background:var(--tint);color:var(--acc)">
                                ✓ {{ $row['link']->eracuni_partner_name ?: 'Partner #'.$row['link']->eracuni_partner_id }}
                            </span>
                            <button type="button" wire:click="unlink({{ $row['link']->id }})"
                                    class="h-9 px-3 rounded-[var(--r-control)] text-[13px] font-semibold text-[var(--ink2)] transition hover:bg-[var(--bg)]">Razveži</button>
                        </div>
                    @elseif ($row['proposal'])
                        {{-- Prijedlog sparivanja --}}
                        @php($p = $row['proposal'])
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="text-right">
                                <div class="text-[14px] font-semibold text-[var(--ink)]">{{ $p->partner->name }}</div>
                                <div class="text-[12px] text-[var(--ink3)]">prijedlog {{ $p->reasonLabel() }}</div>
                            </div>
                            <button type="button" wire:click="link('{{ $c->email }}', '{{ $p->partner->id }}')"
                                    class="h-9 px-4 rounded-[var(--r-control)] bg-[var(--acc)] text-white text-[13px] font-semibold transition hover:brightness-110">Poveži</button>
                            <button type="button" wire:click="reject('{{ $c->email }}')"
                                    class="h-9 px-3 rounded-[var(--r-control)] text-[13px] font-semibold text-[var(--ink2)] transition hover:bg-[var(--bg)]">Odbij</button>
                        </div>
                    @else
                        {{-- Bez prijedloga (ili odbijen) → ponudi stvaranje novog --}}
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-[13px] text-[var(--ink3)]">nema para</span>
                            <button type="button" wire:click="createPartner('{{ $c->email }}')"
                                    class="h-9 px-4 rounded-[var(--r-control)] border border-[var(--line2)] bg-[var(--surface)] text-[13px] font-semibold text-[var(--ink)] transition hover:bg-[var(--bg)]">Stvori novog partnera</button>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
