<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use App\Models\TrainingGroup;
use Illuminate\Database\Seeder;

/**
 * Canonical locations + training groups — the source of truth (not PII, version-controlled).
 * Rows not listed here are pruned; students referencing them are unlinked via nullOnDelete,
 * not deleted. Groups are left empty for trainers to fill in via the app.
 */
final class ReferenceSeeder extends Seeder
{
    /** @var list<array{slug: string, name: string}> */
    private const LOCATIONS = [
        ['slug' => 'os-stanovi', 'name' => 'OŠ Stanovi'],
        ['slug' => 'visnjik-pon-sri', 'name' => 'Višnjik · pon. i sri., 20–21 h'],
        ['slug' => 'os-k-krstica', 'name' => 'OŠ K. Krstića'],
    ];

    /** @var list<array{slug: string, name: string, description: string}> */
    private const GROUPS = [
        ['slug' => 'pocetnici', 'name' => 'Početnici', 'description' => '6–8 godina'],
        ['slug' => 'stariji-pocetnici', 'name' => 'Stariji početnici', 'description' => '8 i više godina'],
    ];

    public function run(): void
    {
        foreach (self::LOCATIONS as $location) {
            Location::updateOrCreate(['slug' => $location['slug']], ['name' => $location['name']]);
        }

        foreach (self::GROUPS as $group) {
            TrainingGroup::updateOrCreate(
                ['slug' => $group['slug']],
                ['name' => $group['name'], 'description' => $group['description']],
            );
        }

        Location::whereNotIn('slug', array_column(self::LOCATIONS, 'slug'))->delete();
        TrainingGroup::whereNotIn('slug', array_column(self::GROUPS, 'slug'))->delete();
    }
}
