@props(['kyu' => null, 'showLabel' => true])

@php
    $value = $kyu === null ? null : (int) $kyu;
    $belt = $value === null ? null : \App\Enums\Belt::from($value);
    $label = $belt?->label() ?? \App\Enums\Belt::NOT_ENTERED_LABEL;

    // Stripe fill + border, mirroring the approved prototype's beltStyle().
    [$background, $border] = match (true) {
        $value === null => ['var(--surface)', '1px dashed var(--belt-none-border)'],
        $value === 0    => ['var(--surface)', '1px solid var(--belt-none-border)'],
        $value === 9    => ['linear-gradient(90deg, var(--belt-9-a) 50%, var(--belt-9-b) 50%)', '1px solid var(--belt-border)'],
        $value === 1    => ['linear-gradient(90deg, var(--belt-1-a) 50%, var(--belt-1-b) 50%)', '1px solid var(--belt-border)'],
        default         => ['var(--belt-' . $value . ')', '1px solid var(--belt-border)'],
    };
@endphp

<span class="inline-flex items-center gap-2">
    <span class="inline-block shrink-0" aria-hidden="true"
          style="width:30px;height:10px;border-radius:3px;background:{{ $background }};border:{{ $border }}"></span>
    @if ($showLabel)
        <span class="text-[14px] {{ $value === null ? 'text-[var(--ink3)]' : 'text-[var(--ink)]' }}">{{ $label }}</span>
    @endif
</span>
