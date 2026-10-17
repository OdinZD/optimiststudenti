<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Money is always an integer number of cents (EUR); never float. This helper only
 * formats cents for display and parses user input back to cents.
 */
final class Money
{
    /** Cents → Croatian display, e.g. 123450 → "1.234,50 €". */
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' €';
    }

    /**
     * Parse a user-entered amount to cents. Accepts Croatian ("1.234,50", "12,50")
     * and plain-dot ("12.50") forms, with an optional € and spaces. Rounds to the cent.
     */
    public static function parse(string $input): int
    {
        $s = str_replace(['€', ' ', "\u{00A0}"], '', trim($input));

        if ($s === '') {
            return 0;
        }

        // Both separators present → '.' are thousands, ',' is the decimal.
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
        }

        $s = str_replace(',', '.', $s);

        return (int) round(((float) $s) * 100);
    }
}
