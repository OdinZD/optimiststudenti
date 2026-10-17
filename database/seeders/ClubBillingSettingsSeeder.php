<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ClubBillingSettings;
use Illuminate\Database\Seeder;

/**
 * Seeds the single club billing-settings row with TODO placeholders. The club's
 * real recipient / payment data (IBAN, model, purpose code, due day, billed months,
 * reference rule) must be filled in before the Članarine module goes live — nothing
 * is invented here. Idempotent.
 */
final class ClubBillingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        ClubBillingSettings::query()->firstOrCreate([], [
            'club_name' => null,       // TODO: naziv primatelja (klub)
            'address' => null,         // TODO: adresa primatelja
            'iban' => null,            // TODO: IBAN kluba
            'model' => null,           // TODO: model poziva na broj (npr. HR00)
            'reference_rule' => null,  // TODO: pravilo za poziv na broj
            'purpose_code' => null,    // TODO: šifra namjene
            'due_day' => null,         // TODO: rok plaćanja (dan u mjesecu)
            'billing_months' => null,  // TODO: mjeseci naplate (npr. [9,10,11,12,1,2,3,4,5,6])
            'currency' => 'EUR',
        ]);
    }
}
