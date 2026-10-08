<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
final class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'birth_date' => fake()->dateTimeBetween('-20 years', '-5 years')->format('Y-m-d'),
            'next_grade_status' => 'none',
            'has_no_medical' => false,
            'flag_t' => false,
        ];
    }
}
