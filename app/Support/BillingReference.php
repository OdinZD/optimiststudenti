<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds the payment reference (poziv na broj) for a charge.
 *
 * Format: "{student_id}-{YYYYMM}". For model HR01 a mod-11 control digit (HUB/NKS
 * "model 01") is appended. The actual model + exact layout are [TODO — potvrditi
 * prema stvarnoj uplatnici kluba]; only HR01 gets a control digit here.
 */
final class BillingReference
{
    public static function for(int $studentId, string $period, ?string $model): string
    {
        $ym = str_replace('-', '', $period); // 'YYYY-MM' → 'YYYYMM'
        $base = $studentId.'-'.$ym;

        if ($model === 'HR01') {
            return $base.self::controlDigitMod11($studentId.$ym);
        }

        return $base; // HR00 / nepoznato → bez kontrolne znamenke
    }

    /**
     * Control digit per HUB/NKS "model 01" (mod 11): digits weighted 2,3,…,9,2,3,…
     * from the right; control = 11 − (sum mod 11); 10 and 11 map to 0.
     */
    public static function controlDigitMod11(string $digits): int
    {
        $sum = 0;
        $weight = 2;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weight;
            $weight = $weight === 9 ? 2 : $weight + 1;
        }

        $control = 11 - ($sum % 11);

        return $control >= 10 ? 0 : $control;
    }
}
