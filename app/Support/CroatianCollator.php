<?php

declare(strict_types=1);

namespace App\Support;

use Collator;

/**
 * Croatian-alphabet string comparison (SPEC §4.4).
 *
 * Uses ext-intl's Collator when available. On a host without intl it falls back
 * to a diacritic-folded comparison so the list still renders (order is then
 * approximate — č/ć/š/ž/đ fold onto c/s/z/d — but stable and non-fatal).
 * ext-intl is a declared requirement; this guard is defence-in-depth for shared
 * hosting where the extension might be disabled.
 */
final class CroatianCollator
{
    public static function compare(string $a, string $b): int
    {
        static $collator = false;

        if ($collator === false) {
            $collator = class_exists(Collator::class) ? new Collator('hr_HR') : null;
        }

        if ($collator instanceof Collator) {
            return (int) $collator->compare($a, $b);
        }

        return strcmp(Diacritics::normalize($a), Diacritics::normalize($b));
    }
}
