<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use App\Models\TrainingGroup;
use Illuminate\Database\Seeder;

/**
 * Locations and training groups from the normalized Excel export (SPEC §3.1–3.2).
 * Idempotent: keyed on slug.
 */
final class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $data = PolazniciDataset::read($this->command);

        if ($data === null) {
            return;
        }

        foreach ($data['locations'] as $location) {
            Location::updateOrCreate(
                ['slug' => $location['slug']],
                ['name' => $location['name']],
            );
        }

        foreach ($data['training_groups'] as $group) {
            TrainingGroup::updateOrCreate(
                ['slug' => $group['slug']],
                ['name' => $group['name'], 'description' => $group['description'] ?? null],
            );
        }
    }
}
