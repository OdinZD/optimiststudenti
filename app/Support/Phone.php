<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Display formatting for Croatian mobile numbers (SPEC §5.2):
 * exactly 10 digits → "091 234 5678"; anything else is returned unchanged.
 * Mirrors the prototype's `fmtPhone()`.
 */
final class Phone
{
    public static function format(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) === 10) {
            return substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6);
        }

        return (string) $value;
    }
}
