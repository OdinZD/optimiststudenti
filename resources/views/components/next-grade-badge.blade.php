@props(['status', 'kyu' => null])

@php
    use App\Enums\NextGradeStatus;
@endphp

@if ($status === NextGradeStatus::ToRegister)
    <span class="inline-block rounded-[var(--r-tag)] px-2 py-0.5 text-[12px] font-semibold"
          style="background:var(--st-soon-bg);color:var(--st-soon-fg)">Treba upisati</span>
@elseif ($status === NextGradeStatus::Registered && $kyu !== null)
    <span class="inline-block rounded-[var(--r-tag)] px-2 py-0.5 text-[12px] font-semibold"
          style="background:var(--tint);color:var(--acc)">Upisan: {{ $kyu }}. kyu</span>
@endif
