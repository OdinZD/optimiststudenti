<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use LogicException;

/**
 * WKF/HKS age category and the date a student crosses into the next one.
 *
 * Never stored — always derived from birth_date and "today" (SPEC §4.1).
 * Logic mirrors the approved prototype (docs/design/source/Main.dc.html):
 * age is an explicit integer (not Carbon's float diffInYears) and the
 * crossover uses EDATE-style no-overflow month arithmetic.
 */
final class AgeCategory
{
    /** @var list<array{key: string, label: string, max: int, months: int}> */
    private const TIERS = [
        ['key' => 'ispod', 'label' => 'Ispod U8',            'max' => 6,   'months' => 72],
        ['key' => 'u08',   'label' => 'U08 · Cicibani',      'max' => 8,   'months' => 96],
        ['key' => 'u10',   'label' => 'U10 · Mlađi učenici', 'max' => 10,  'months' => 120],
        ['key' => 'u12',   'label' => 'U12 · Učenici',       'max' => 12,  'months' => 144],
        ['key' => 'u14',   'label' => 'U14 · Mlađi kadeti',  'max' => 14,  'months' => 168],
        ['key' => 'u16',   'label' => 'U16 · Kadeti',        'max' => 16,  'months' => 192],
        ['key' => 'u18',   'label' => 'U18 · Juniori',       'max' => 18,  'months' => 216],
        ['key' => 'u21',   'label' => 'U21',                 'max' => 21,  'months' => 252],
        ['key' => 'sen',   'label' => 'Seniori',             'max' => 999, 'months' => 0],
    ];

    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly int $age,
        public readonly ?CarbonImmutable $crossover,
    ) {}

    /** Null birth date => no category (filter "Bez datuma rođenja"). */
    public static function for(?CarbonInterface $birthDate, CarbonInterface $today): ?self
    {
        if ($birthDate === null) {
            return null;
        }

        $age = self::ageInYears($birthDate, $today);

        foreach (self::TIERS as $tier) {
            if ($age < $tier['max']) {
                $crossover = $tier['months'] > 0
                    ? CarbonImmutable::instance($birthDate)->addMonthsNoOverflow($tier['months'])
                    : null;

                return new self($tier['key'], $tier['label'], $age, $crossover);
            }
        }

        throw new LogicException('No age tier matched; the final tier should be unbounded.');
    }

    /** Completed years, counting a birthday today as already reached. */
    private static function ageInYears(CarbonInterface $birthDate, CarbonInterface $today): int
    {
        $age = $today->year - $birthDate->year;

        $birthdayNotYetReached = $today->month < $birthDate->month
            || ($today->month === $birthDate->month && $today->day < $birthDate->day);

        return $birthdayNotYetReached ? $age - 1 : $age;
    }
}
