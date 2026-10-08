<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\StudentForm;
use App\Models\Location;
use App\Models\Student;
use App\Models\TrainingGroup;
use App\Models\User;
use App\Support\AgeCategory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class StudentFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-07');
        Location::create(['slug' => 'os-stanovi', 'name' => 'OŠ Stanovi']);
        Location::create(['slug' => 'visnjik-cetvrtak', 'name' => 'Višnjik · četvrtak']);
        TrainingGroup::create(['slug' => 'kate', 'name' => 'Kate']);
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_name_fields_are_required(): void
    {
        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->call('save')
            ->assertHasErrors(['lastName' => 'required', 'firstName' => 'required'])
            ->assertSet('submitted', true);
    }

    public function test_rejects_invalid_and_future_values(): void
    {
        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('lastName', 'Testić')
            ->set('firstName', 'Ana')
            ->set('oib', '123')
            ->set('birthDate', '2099-01-01')
            ->set('parentEmail', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['oib', 'birthDate', 'parentEmail']);
    }

    public function test_rejects_duplicate_oib(): void
    {
        Student::create(['last_name' => 'Prvi', 'first_name' => 'A', 'oib' => '69435151530']);

        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('lastName', 'Drugi')
            ->set('firstName', 'B')
            ->set('oib', '69435151530')
            ->call('save')
            ->assertHasErrors(['oib' => 'unique']);
    }

    public function test_creates_a_student(): void
    {
        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('lastName', 'Testić')
            ->set('firstName', 'Ana')
            ->set('belt', '6')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('students-changed')
            ->assertDispatched('toast')
            ->assertSet('open', false);

        $this->assertDatabaseHas('students', [
            'last_name' => 'Testić',
            'first_name' => 'Ana',
            'belt_kyu' => 6,
        ]);
    }

    public function test_edits_an_existing_student(): void
    {
        $student = Student::create(['last_name' => 'Horvat', 'first_name' => 'Ivo']);

        Livewire::test(StudentForm::class)
            ->call('openEdit', $student->id)
            ->assertSet('lastName', 'Horvat')
            ->set('firstName', 'Ivan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Ivan', $student->fresh()->first_name);
    }

    public function test_live_age_category_updates_with_birth_date(): void
    {
        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('birthDate', '2013-12-30')
            ->assertViewHas('ageCategory', fn (?AgeCategory $c): bool => $c?->label === 'U14 · Mlađi kadeti'
                && $c->crossover?->format('d.m.Y.') === '30.12.2027.');
    }

    public function test_create_defaults(): void
    {
        $osStanovi = (string) Location::where('slug', 'os-stanovi')->value('id');

        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->assertSet('locationId', $osStanovi)
            ->assertSet('nextGradeStatus', 'none');
    }

    public function test_registered_suggests_previous_kyu(): void
    {
        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('belt', '6')
            ->call('selectNextGrade', 'registered')
            ->assertSet('nextGradeKyu', '5');

        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->call('selectNextGrade', 'registered')
            ->assertSet('nextGradeKyu', '9');
    }

    public function test_no_medical_clears_the_date(): void
    {
        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('medicalValidUntil', '2027-01-01')
            ->set('hasNoMedical', true)
            ->assertSet('medicalValidUntil', '');
    }
}
