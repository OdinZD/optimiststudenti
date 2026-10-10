<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\CompetitionsList;
use App\Livewire\StudentResults;
use App\Models\Competition;
use App\Models\CompetitionResult;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CompetitionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_create_and_edit_competition(): void
    {
        Livewire::test(CompetitionsList::class)
            ->call('openCreate')
            ->set('name', 'Zimski kup')
            ->set('heldOn', '2026-02-15')
            ->set('city', 'Zadar')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('competitions', ['name' => 'Zimski kup', 'city' => 'Zadar']);

        $competition = Competition::firstOrFail();
        Livewire::test(CompetitionsList::class)
            ->call('openEdit', $competition->id)
            ->set('name', 'Zimski kup 2026')
            ->call('save');

        $this->assertSame('Zimski kup 2026', $competition->fresh()->name);
    }

    public function test_name_and_date_required(): void
    {
        Livewire::test(CompetitionsList::class)
            ->call('openCreate')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'heldOn' => 'required']);
    }

    public function test_delete_cascades_results(): void
    {
        $child = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        $competition = Competition::create(['name' => 'Kup', 'held_on' => '2026-01-01']);
        CompetitionResult::create(['student_id' => $child->id, 'competition_id' => $competition->id, 'discipline' => 'kate']);

        Livewire::test(CompetitionsList::class)
            ->call('confirmDelete', $competition->id)
            ->assertSet('deleteResults', 1)
            ->call('delete');

        $this->assertDatabaseMissing('competitions', ['id' => $competition->id]);
        $this->assertSame(0, CompetitionResult::count());
    }

    public function test_add_edit_delete_result_for_child(): void
    {
        $child = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        $competition = Competition::create(['name' => 'Kup', 'held_on' => '2026-01-01']);

        $component = Livewire::test(StudentResults::class, ['studentId' => $child->id])
            ->set('competitionId', (string) $competition->id)
            ->set('discipline', 'kumite')
            ->set('place', '1')
            ->set('wins', '3')
            ->set('losses', '0')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('competition_results', [
            'student_id' => $child->id,
            'competition_id' => $competition->id,
            'discipline' => 'kumite',
            'place' => 1,
        ]);

        $result = CompetitionResult::firstOrFail();
        $component->call('editResult', $result->id)->set('place', '2')->call('save');
        $this->assertSame(2, $result->fresh()->place);

        $component->call('deleteResult', $result->id);
        $this->assertSame(0, CompetitionResult::count());
    }

    public function test_result_requires_competition_and_discipline(): void
    {
        $child = Student::create(['last_name' => 'A', 'first_name' => 'B']);

        Livewire::test(StudentResults::class, ['studentId' => $child->id])
            ->call('save')
            ->assertHasErrors(['competitionId', 'discipline']);
    }
}
