<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use App\Models\TrainingGroup;
use App\Models\TrainingSlot;
use Illuminate\Database\Seeder;

/**
 * Canonical locations + training groups + training slots (termini) — the source of truth (not PII, version-controlled).
 * Rows not listed here are pruned; students referencing them are unlinked via nullOnDelete,
 * not deleted. Groups are left empty for trainers to fill in via the app.
 */
final class ReferenceSeeder extends Seeder
{
    /** @var list<array{slug: string, name: string}> */
    private const LOCATIONS = [
        ['slug' => 'os-stanovi', 'name' => 'OŠ Stanovi'],
        ['slug' => 'visnjik-pon-sri', 'name' => 'PS Višnjik'],
        ['slug' => 'os-k-krstica', 'name' => 'OŠ K. Krstića'],
    ];

    /** @var list<array{slug: string, name: string, description: ?string}> */
    private const GROUPS = [
        ['slug' => 'pocetnici', 'name' => 'Početnici', 'description' => '6–8 godina'],
        ['slug' => 'stariji-pocetnici', 'name' => 'Stariji početnici', 'description' => '8 i više godina'],
        ['slug' => 'kata-napredni', 'name' => 'Kata napredni', 'description' => 'Napredna skupina'],
        ['slug' => 'kumite-stariji', 'name' => 'Kumite stariji', 'description' => null],
        ['slug' => 'kumite-mladi', 'name' => 'Kumite mlađi', 'description' => null],
    ];

    /**
     * Termini (location + days + time) and the groups that train in each. A student's slots are derived from his groups.
     *
     * @var list<array{slug: string, location: string, days: string, starts: string, ends: string, groups: list<string>}>
     */
    private const SLOTS = [
        ['slug' => 'krstica-uto-cet', 'location' => 'os-k-krstica', 'days' => 'Uto/Čet', 'starts' => '17:00', 'ends' => '18:00', 'groups' => ['pocetnici']],
        ['slug' => 'stanovi-uto-cet', 'location' => 'os-stanovi', 'days' => 'Uto/Čet', 'starts' => '20:00', 'ends' => '21:00', 'groups' => ['kata-napredni', 'kumite-stariji']],
        ['slug' => 'visnjik-pon', 'location' => 'visnjik-pon-sri', 'days' => 'Pon', 'starts' => '20:00', 'ends' => '21:00', 'groups' => ['stariji-pocetnici', 'kumite-stariji']],
        ['slug' => 'visnjik-sri', 'location' => 'visnjik-pon-sri', 'days' => 'Sri', 'starts' => '20:00', 'ends' => '21:00', 'groups' => ['stariji-pocetnici', 'kumite-mladi']],
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

        foreach (self::SLOTS as $order => $slot) {
            $model = TrainingSlot::updateOrCreate(['slug' => $slot['slug']], [
                'location_id' => Location::where('slug', $slot['location'])->value('id'),
                'days' => $slot['days'],
                'starts_at' => $slot['starts'],
                'ends_at' => $slot['ends'],
                'sort_order' => $order,
            ]);
            $model->groups()->sync(TrainingGroup::whereIn('slug', $slot['groups'])->pluck('id')->all());
        }

        TrainingSlot::whereNotIn('slug', array_column(self::SLOTS, 'slug'))->delete();
        Location::whereNotIn('slug', array_column(self::LOCATIONS, 'slug'))->delete();
        TrainingGroup::whereNotIn('slug', array_column(self::GROUPS, 'slug'))->delete();
    }
}
