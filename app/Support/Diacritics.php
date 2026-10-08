<?php

declare(strict_types=1);

namespace App\Support;

use Normalizer;

/**
 * Case- and diacritic-insensitive normalization for search (SPEC §4.3):
 * č, ć, š, ž → c, s, z and đ → d. Mirrors the prototype's `norm()`.
 */
final class Diacritics
{
    public static function normalize(?string $value): string
    {
        $value = mb_strtolower((string) $value, 'UTF-8');

        if (class_exists(Normalizer::class)) {
            // Decompose (e.g. č → c + combining caron), then drop the marks.
            $value = Normalizer::normalize($value, Normalizer::FORM_D) ?: $value;
            $value = preg_replace('/\p{Mn}/u', '', $value) ?? $value;
        }

        // đ/Đ is a distinct letter, not a decomposable accent, so map it explicitly.
        return str_replace('đ', 'd', $value);
    }
}
