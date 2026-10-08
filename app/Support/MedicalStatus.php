<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Medical-clearance status, derived from medical_valid_until + has_no_medical
 * against "today" (SPEC §4.2). The due date is inclusive.
 *
 * Day differences are computed on UTC midnights so DST transitions (e.g. the
 * late-October change in Europe/Zagreb) cannot shift a whole-day count.
 */
enum MedicalStatus: string
{
    case None = 'nema';
    case Unknown = 'nepoznato';
    case Expired = 'istekao';
    case SoonExpiring = 'uskoro';
    case Valid = 'vrijedi';

    public static function for(?CarbonInterface $validUntil, bool $hasNoMedical, CarbonInterface $today): self
    {
        if ($hasNoMedical) {
            return self::None;
        }

        if ($validUntil === null) {
            return self::Unknown;
        }

        $days = self::wholeDaysBetween($today, $validUntil);

        return match (true) {
            $days < 0 => self::Expired,
            $days <= 30 => self::SoonExpiring,
            default => self::Valid,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::None => 'Nema',
            self::Unknown => 'Nepoznato',
            self::Expired => 'Istekao',
            self::SoonExpiring => 'Ističe uskoro',
            self::Valid => 'Vrijedi',
        };
    }

    private static function wholeDaysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $a = CarbonImmutable::create($from->year, $from->month, $from->day, 0, 0, 0, 'UTC');
        $b = CarbonImmutable::create($to->year, $to->month, $to->day, 0, 0, 0, 'UTC');

        return (int) $a->diffInDays($b, false);
    }
}
