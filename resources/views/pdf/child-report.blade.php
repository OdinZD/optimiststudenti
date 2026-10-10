<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28mm 18mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #14171F; font-size: 11px; line-height: 1.4; }
        .logo { text-align: left; margin: 0 0 8px; }
        .club { color: #2D3E7B; font-size: 13px; font-weight: bold; letter-spacing: .3px; }
        .title { font-size: 20px; font-weight: bold; margin: 2px 0 0; }
        .child { font-size: 16px; margin: 14px 0 0; }
        .muted { color: #5B6376; }
        .rule { border-bottom: 2px solid #2D3E7B; margin: 6px 0 0; height: 0; }

        .summary { width: 100%; border-collapse: collapse; margin: 14px 0 18px; }
        .summary td { background: #F4F5F7; border: 3px solid #fff; padding: 8px 10px; text-align: center; }
        .summary .num { font-size: 18px; font-weight: bold; color: #2D3E7B; }
        .summary .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .4px; color: #5B6376; }

        .disc { font-size: 13px; font-weight: bold; margin: 16px 0 6px; color: #14171F; }
        table.results { width: 100%; border-collapse: collapse; }
        table.results th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .4px;
            color: #5B6376; border-bottom: 1px solid #E1E4EA; padding: 5px 6px; }
        table.results td { padding: 6px; border-bottom: 1px solid #E1E4EA; vertical-align: top; }
        .medal { font-weight: bold; color: #2D3E7B; }
        .foot { position: fixed; bottom: -16mm; left: 0; right: 0; color: #9AA2B2; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @php($logo = public_path('images/KKoptimist.png'))
    @if (is_file($logo))
        <div class="logo"><img src="{{ $logo }}" height="80" alt=""></div>
    @endif
    <div class="club">{{ $club }}</div>
    <div class="title">Izvješće o natjecanjima — {{ $year }}.</div>
    <div class="rule"></div>
    <div class="child"><strong>{{ $childName }}</strong></div>

    <table class="summary">
        <tr>
            <td><div class="num">{{ $summary['competitions'] }}</div><div class="lbl">Natjecanja</div></td>
            <td><div class="num">{{ $summary['gold'] }}</div><div class="lbl">Zlato</div></td>
            <td><div class="num">{{ $summary['silver'] }}</div><div class="lbl">Srebro</div></td>
            <td><div class="num">{{ $summary['bronze'] }}</div><div class="lbl">Bronca</div></td>
            <td><div class="num">{{ $summary['bouts'] }}</div><div class="lbl">Ukupno borbi</div></td>
        </tr>
    </table>

    @if (! $hasResults)
        <p class="muted">Za {{ $year }}. godinu nema zabilježenih natjecanja.</p>
    @else
        @foreach ($disciplines as $discipline)
            <div class="disc">{{ $discipline['label'] }}</div>
            <table class="results">
                <thead>
                    <tr>
                        <th style="width:30%">Natjecanje</th>
                        <th style="width:14%">Datum</th>
                        <th style="width:14%">Grad</th>
                        <th style="width:16%">Kategorija</th>
                        <th style="width:12%">Plasman</th>
                        <th style="width:14%">Borbe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($discipline['rows'] as $row)
                        <tr>
                            <td>{{ $row['competition'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['city'] ?? '—' }}</td>
                            <td>{{ $row['category'] ?? '—' }}</td>
                            <td>
                                @if ($row['place']){{ $row['place'] }}. mjesto @endif
                                @if ($row['medal'])<span class="medal">{{ $row['medal'] }}</span>@endif
                                @unless ($row['place'] || $row['medal'])—@endunless
                            </td>
                            <td>
                                {{ $row['bouts'] }}
                                @if ($row['wins'] !== null || $row['losses'] !== null)
                                    <span class="muted">({{ $row['wins'] ?? 0 }}–{{ $row['losses'] ?? 0 }})</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="foot">{{ $club }} · izvješće generirano {{ now()->format('d.m.Y.') }}</div>
</body>
</html>
