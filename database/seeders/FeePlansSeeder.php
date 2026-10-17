<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FeePlan;
use Illuminate\Database\Seeder;

/**
 * Placeholder tariffs for the Članarine module. Names are placeholders and amounts
 * are TODO (0 cents) — the club's real tariffs and prices must be set before go-live.
 * Nothing is invented here. Idempotent (keyed on name).
 */
final class FeePlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => '1× tjedno', 'type' => 'monthly'],
            ['name' => '2× tjedno', 'type' => 'monthly'],
            ['name' => 'Upisnina', 'type' => 'one_time'],
        ];

        foreach ($plans as $plan) {
            FeePlan::firstOrCreate(
                ['name' => $plan['name']],
                ['amount_cents' => 0, 'type' => $plan['type'], 'active' => true], // TODO: iznos
            );
        }
    }
}
