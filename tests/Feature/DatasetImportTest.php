<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Belt;
use App\Enums\NextGradeStatus;
use App\Models\Student;
use App\Support\AgeCategory;
use App\Support\Diacritics;
use App\Support\MedicalStatus;
use Carbon\CarbonImmutable;
use Database\Seeders\PolazniciDataset;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks the SPEC §9 acceptance counts against the real dataset (today = 2026-10-07).
 * Skipped when the gitignored PII file is absent (e.g. CI).
 */
final class DatasetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(database_path(PolazniciDataset::PATH))) {
            $this->markTestSkipped('Student dataset (gitignored PII) not present.');
        }

        CarbonImmutable::setTestNow('2026-10-07');
        $this->seed([ReferenceSeeder::class, StudentSeeder::class]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_imports_all_79_students(): void
    {
        $this->assertSame(79, Student::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed([ReferenceSeeder::class, StudentSeeder::class]);

        $this->assertSame(79, Student::count());
    }

    public function test_age_category_chip_counts(): void
    {
        $today = CarbonImmutable::now();
        $byKey = [];
        $noBirthDate = 0;

        foreach (Student::all() as $student) {
            $category = AgeCategory::for($student->birth_date, $today);

            if ($category === null) {
                $noBirthDate++;

                continue;
            }

            $byKey[$category->key] = ($byKey[$category->key] ?? 0) + 1;
        }

        $this->assertSame(7, $byKey['u08']);
        $this->assertSame(14, $byKey['u10']);
        $this->assertSame(20, $byKey['u12']);
        $this->assertSame(14, $byKey['u14']);
        $this->assertSame(4, $byKey['u16']);
        $this->assertSame(5, $byKey['sen']);
        $this->assertSame(15, $noBirthDate);
    }

    public function test_attention_chip_and_search_counts(): void
    {
        $today = CarbonImmutable::now();

        $expired = Student::all()
            ->filter(fn (Student $s): bool => MedicalStatus::for($s->medical_valid_until, $s->has_no_medical, $today) === MedicalStatus::Expired)
            ->count();

        $kardum = Student::all()
            ->filter(fn (Student $s): bool => str_contains($s->search_haystack, Diacritics::normalize('kardum')))
            ->count();

        $this->assertSame(28, $expired, 'Liječnički istekao');
        $this->assertSame(4, Student::where('next_grade_status', NextGradeStatus::ToRegister->value)->count(), 'Treba upisati');
        $this->assertSame(3, $kardum, 'Pretraga "kardum"');
        $this->assertSame(19, Student::where('belt_kyu', Belt::Kyu6->value)->count(), '6. kyu');
    }
}
