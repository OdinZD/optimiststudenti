<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Reference data must exist before students reference it; one transaction (SPEC §8).
        DB::transaction(function (): void {
            $this->call([
                TrainerSeeder::class,
                ReferenceSeeder::class,
                StudentSeeder::class,
            ]);
        });
    }
}
