<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Karate season: runs 1 September → 1 July of the next year (July/August are off-season —
 * no competitions, no training). A season is identified by its start year (the September year),
 * e.g. start 2025 = season "2025/2026" = 01.09.2025.–01.07.2026.
 */
final class Season
{
    /** Season currently in progress on the given day. */
    public static function current(CarbonInterface $today): int
    {
        return $today->month >= 9 ? $today->year : $today->year - 1;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} [from inclusive, to exclusive]
     */
    public static function window(int $start): array
    {
        return [
            CarbonImmutable::create($start, 9, 1, 0, 0, 0),
            CarbonImmutable::create($start + 1, 7, 1, 0, 0, 0),
        ];
    }

    public static function contains(int $start, CarbonInterface $date): bool
    {
        [$from, $to] = self::window($start);

        return $date >= $from && $date < $to;
    }

    /** Human label, e.g. "2025/2026". */
    public static function label(int $start): string
    {
        return $start.'/'.($start + 1);
    }

    /** Filename-safe label, e.g. "2025-2026". */
    public static function slug(int $start): string
    {
        return $start.'-'.($start + 1);
    }
}
