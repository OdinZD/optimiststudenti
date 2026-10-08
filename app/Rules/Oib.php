<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Croatian OIB: 11 digits with an ISO 7064 MOD 11,10 check digit (SPEC §4.6).
 */
final class Oib implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $oib = (string) $value;

        if (preg_match('/^\d{11}$/', $oib) !== 1) {
            $fail('OIB mora imati 11 znamenki.');

            return;
        }

        if (! self::hasValidCheckDigit($oib)) {
            $fail('OIB nije ispravan.');
        }
    }

    public static function hasValidCheckDigit(string $oib): bool
    {
        if (preg_match('/^\d{11}$/', $oib) !== 1) {
            return false;
        }

        $remainder = 10;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder + (int) $oib[$i]) % 10;
            $remainder = ($remainder === 0 ? 10 : $remainder) * 2 % 11;
        }

        $control = (11 - $remainder) % 10;

        return $control === (int) $oib[10];
    }
}
