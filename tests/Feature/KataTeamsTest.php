<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\KataTeamsList;
use App\Models\KataTeam;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class KataTeamsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_creates_a_team(): void
    {
        Livewire::test(KataTeamsList::class)
            ->call('openCreate')
            ->set('name', 'Tim A')
            ->set('color', '#2D3E7B')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('open', false);

        $this->assertDatabaseHas('kata_teams', ['name' => 'Tim A', 'color' => '#2D3E7B']);
    }

    public function test_name_is_required_and_color_must_be_hex(): void
    {
        Livewire::test(KataTeamsList::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('color', 'plava')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'color' => 'regex']);
    }

    public function test_edits_a_team(): void
    {
        $team = KataTeam::create(['name' => 'Tim A', 'color' => '#2D3E7B']);

        Livewire::test(KataTeamsList::class)
            ->call('openEdit', $team->id)
            ->assertSet('name', 'Tim A')
            ->set('name', 'Tim B')
            ->set('color', '#C23B30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kata_teams', ['id' => $team->id, 'name' => 'Tim B', 'color' => '#C23B30']);
    }

    public function test_delete_cascades_pivot_but_keeps_students(): void
    {
        $team = KataTeam::create(['name' => 'Tim A', 'color' => '#2D3E7B']);
        $child = Student::create(['last_name' => 'Anđić', 'first_name' => 'Laura']);
        $team->students()->attach($child->id);

        Livewire::test(KataTeamsList::class)
            ->call('confirmDelete', $team->id)
            ->assertSet('deleteMembers', 1)
            ->call('delete');

        $this->assertDatabaseMissing('kata_teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('kata_team_student', ['kata_team_id' => $team->id]);
        $this->assertNotSoftDeleted($child); // student kept
    }

    public function test_render_shows_member_count(): void
    {
        $team = KataTeam::create(['name' => 'Tim A', 'color' => '#2D3E7B']);
        $child = Student::create(['last_name' => 'Anđić', 'first_name' => 'Laura']);
        $team->students()->attach($child->id);

        Livewire::test(KataTeamsList::class)
            ->assertViewHas('teams', fn ($teams): bool => $teams->firstWhere('id', $team->id)->students_count === 1);
    }
}
