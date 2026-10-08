<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Student;
use App\Models\TrainingGroup;
use Illuminate\Database\Seeder;

/**
 * Imports the 79 students (SPEC §8). Idempotent: keyed on OIB, or on
 * last name + first name + location when no OIB is recorded.
 */
final class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $data = PolazniciDataset::read($this->command);

        if ($data === null) {
            return;
        }

        $locationIds = Location::pluck('id', 'slug');
        $groupIds = TrainingGroup::pluck('id', 'slug');

        foreach ($data['students'] as $row) {
            $attributes = [
                'last_name' => $row['last_name'],
                'first_name' => $row['first_name'],
                'birth_date' => $row['birth_date'] ?? null,
                'oib' => $row['oib'] ?? null,
                'location_id' => $locationIds[$row['location'] ?? null] ?? null,
                'training_group_id' => $groupIds[$row['training_group'] ?? null] ?? null,
                'belt_kyu' => $row['belt_kyu'] ?? null,
                'next_grade_status' => $row['next_grade_status'] ?? 'none',
                'next_grade_kyu' => $row['next_grade_kyu'] ?? null,
                'medical_valid_until' => $row['medical_valid_until'] ?? null,
                'has_no_medical' => $row['has_no_medical'] ?? false,
                'parent_name' => $row['parent_name'] ?? null,
                'parent_phone' => $row['parent_phone'] ?? null,
                'parent_email' => $row['parent_email'] ?? null,
                'note' => $row['note'] ?? null,
                'flag_t' => $row['flag_t'] ?? false,
            ];

            $key = $attributes['oib'] !== null
                ? ['oib' => $attributes['oib']]
                : [
                    'last_name' => $attributes['last_name'],
                    'first_name' => $attributes['first_name'],
                    'location_id' => $attributes['location_id'],
                ];

            Student::updateOrCreate($key, $attributes);
        }
    }
}
