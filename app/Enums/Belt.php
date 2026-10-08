<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Belt rank stored in students.belt_kyu.
 * 0 = no belt, 1–9 = kyu. A null column value means "not entered"
 * (handled at the presentation layer, see self::NOT_ENTERED_LABEL).
 */
enum Belt: int
{
    case None = 0;
    case Kyu1 = 1;
    case Kyu2 = 2;
    case Kyu3 = 3;
    case Kyu4 = 4;
    case Kyu5 = 5;
    case Kyu6 = 6;
    case Kyu7 = 7;
    case Kyu8 = 8;
    case Kyu9 = 9;

    public const NOT_ENTERED_LABEL = 'Nije uneseno';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Bez pojasa',
            self::Kyu9 => '9. kyu · bijeložuti',
            self::Kyu8 => '8. kyu · žuti',
            self::Kyu7 => '7. kyu · narančasti',
            self::Kyu6 => '6. kyu · crveni',
            self::Kyu5 => '5. kyu · zeleni',
            self::Kyu4 => '4. kyu · plavi',
            self::Kyu3 => '3. kyu · ljubičasti',
            self::Kyu2 => '2. kyu · smeđi',
            self::Kyu1 => '1. kyu · smeđe crni',
        };
    }

    /** Options for the belt <select>, in display order (9 → 1, then "no belt"). */
    public static function options(): array
    {
        return [
            self::Kyu9, self::Kyu8, self::Kyu7, self::Kyu6, self::Kyu5,
            self::Kyu4, self::Kyu3, self::Kyu2, self::Kyu1, self::None,
        ];
    }
}
