@props(['status'])

@php
    use App\Support\MedicalStatus;

    // Status is conveyed by text as well as colour (SPEC §7 — never colour alone).
    [$background, $foreground, $dot] = match ($status) {
        MedicalStatus::Expired      => ['var(--st-expired-bg)', 'var(--st-expired-fg)', 'var(--dot-expired)'],
        MedicalStatus::SoonExpiring => ['var(--st-soon-bg)', 'var(--st-soon-fg)', 'var(--st-soon-fg)'],
        MedicalStatus::Valid        => ['var(--st-valid-bg)', 'var(--st-valid-fg)', 'var(--st-valid-fg)'],
        default                     => ['var(--st-neutral-bg)', 'var(--st-neutral-fg)', 'var(--st-neutral-fg)'],
    };
@endphp

<span class="inline-flex items-center gap-1.5 rounded-[var(--r-pill)] px-2 py-1 text-[13px] font-medium"
      style="background:{{ $background }};color:{{ $foreground }}">
    <span class="w-1.5 h-1.5 rounded-full shrink-0" aria-hidden="true" style="background:{{ $dot }}"></span>
    {{ $status->label() }}
</span>
