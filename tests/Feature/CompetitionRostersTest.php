<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\CompetitionRosterShow;
use App\Livewire\CompetitionRostersList;
use App\Models\CompetitionRoster;
use App\Models\Student;
use App\Models\TrainingGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CompetitionRostersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function child(string $last, string $first, ?string $birth = '2015-05-05', ?int $group = null): Student
    {
        $student = Student::create(['last_name' => $last, 'first_name' => $first, 'birth_date' => $birth]);

        // Group membership is a many-to-many (student_training_group), no longer a column on students.
        if ($group !== null) {
            $student->trainingGroups()->attach($group);
        }

        return $student;
    }

    public function test_pages_render(): void
    {
        $roster = CompetitionRoster::create(['name' => 'Open Zadar']);

        $this->get(route('rosters'))->assertOk()->assertSee('Open Zadar');
        $this->get(route('rosters.show', $roster))->assertOk()->assertSee('Open Zadar');
    }

    public function test_creates_roster_with_optional_date_and_requires_name(): void
    {
        Livewire::test(CompetitionRostersList::class)
            ->call('openCreate')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required'])
            ->set('name', 'Open Zadar 2026')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('competition_rosters', ['name' => 'Open Zadar 2026', 'event_date' => null]);

        Livewire::test(CompetitionRostersList::class)
            ->call('openCreate')
            ->set('name', 'Turnir')
            ->set('eventDate', '2026-11-14')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('2026-11-14', CompetitionRoster::where('name', 'Turnir')->first()->event_date->format('Y-m-d'));
    }

    public function test_edits_and_deletes_roster_cascading_entries(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $roster->entries()->create(['student_id' => $this->child('Anđić', 'Laura')->id]);

        Livewire::test(CompetitionRostersList::class)
            ->call('openEdit', $roster->id)
            ->set('name', 'B')
            ->call('save')
            ->call('confirmDelete', $roster->id)
            ->assertSet('deleteEntries', 1)
            ->call('delete');

        $this->assertDatabaseMissing('competition_rosters', ['id' => $roster->id]);
        $this->assertDatabaseCount('competition_roster_entries', 0);
    }

    public function test_search_ignores_diacritics_and_adds_child(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $child = $this->child('Anđić', 'Laura');

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->set('search', 'Andic')
            ->assertSee('Anđić')
            ->call('addStudent', $child->id)
            ->call('addStudent', $child->id); // second click is a no-op

        $this->assertSame(1, $roster->entries()->count());
        $this->assertNull($roster->entries()->first()->discipline);
    }

    public function test_duplicate_discipline_rejected_but_second_discipline_allowed(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $child = $this->child('Anđić', 'Laura');
        $first = $roster->entries()->create(['student_id' => $child->id, 'discipline' => 'kate']);
        $second = $roster->entries()->create(['student_id' => $child->id]);

        $component = Livewire::test(CompetitionRosterShow::class, ['roster' => $roster]);

        $component->call('setDiscipline', $second->id, 'kate')
            ->assertSet('rev', 1) // odbijena promjena → <select> se ponovno stvori u pregledniku
            ->assertDispatched('toast', message: 'Dijete je već na popisu za tu disciplinu');
        $this->assertNull($second->fresh()->discipline);

        $component->call('setDiscipline', $second->id, 'kumite');
        $this->assertSame('kumite', $second->fresh()->discipline->value);

        // clearing to null is fine while no other null row exists
        $component->call('setDiscipline', $first->id, '');
        $this->assertNull($first->fresh()->discipline);
    }

    public function test_two_null_discipline_rows_are_a_duplicate(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $child = $this->child('Anđić', 'Laura');
        $roster->entries()->create(['student_id' => $child->id]);
        $other = $roster->entries()->create(['student_id' => $child->id, 'discipline' => 'kate']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('setDiscipline', $other->id, '')
            ->assertDispatched('toast', message: 'Dijete je već na popisu za tu disciplinu');

        $this->assertSame('kate', $other->fresh()->discipline->value);
    }

    public function test_add_discipline_button(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $child = $this->child('Anđić', 'Laura');
        $entry = $roster->entries()->create(['student_id' => $child->id]);

        $c = Livewire::test(CompetitionRosterShow::class, ['roster' => $roster]);

        $c->call('addDiscipline', $entry->id)->assertDispatched('toast'); // lone undecided row
        $this->assertSame(1, $roster->entries()->count());

        $entry->update(['discipline' => 'kate']);
        $c->call('addDiscipline', $entry->id)->call('addDiscipline', $entry->id);
        $this->assertEqualsCanonicalizing(
            ['kate', 'kumite', 'kata_team'],
            $roster->entries()->pluck('discipline')->map(fn ($d) => $d->value)->all(),
        );

        $c->call('addDiscipline', $entry->id)->assertDispatched('toast', message: 'Anđić Laura je već na popisu za sve discipline');
        $this->assertSame(3, $roster->entries()->count());
    }

    public function test_quick_add_by_group_adds_only_missing(): void
    {
        $group = TrainingGroup::create(['slug' => 'pocetnici', 'name' => 'Početnici']);
        $other = TrainingGroup::create(['slug' => 'napredni', 'name' => 'Napredni']);
        $a = $this->child('Anić', 'Ana', group: $group->id);
        $this->child('Babić', 'Bruno', group: $group->id);
        $this->child('Cerić', 'Cvita', group: $other->id);
        $gone = $this->child('Dukić', 'Dino', group: $group->id);
        $gone->delete();

        $roster = CompetitionRoster::create(['name' => 'A']);
        $roster->entries()->create(['student_id' => $a->id, 'discipline' => 'kate']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->set('quick', 'g:'.$group->id)
            ->assertSee('Dodaj sve (1)')
            ->call('addQuick')
            ->assertSee('Dodaj sve (0)');

        $this->assertSame(2, $roster->entries()->count());
        $this->assertSame(1, $roster->entries()->where('student_id', $a->id)->count());
    }

    public function test_quick_add_by_age_category(): void
    {
        CarbonImmutable::setTestNow('2026-10-19');
        $this->child('Anić', 'Ana', '2016-01-01');   // 10 -> U12
        $this->child('Babić', 'Bruno', '2016-03-03'); // 10 -> U12
        $this->child('Cerić', 'Cvita', '2010-01-01'); // 16 -> U18
        $roster = CompetitionRoster::create(['name' => 'A']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->set('quick', 'a:u12')
            ->assertSee('Dodaj sve (2)')
            ->call('addQuick');

        $this->assertSame(2, $roster->entries()->count());
    }

    public function test_inline_update_persists_and_validates(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $entry = $roster->entries()->create(['student_id' => $this->child('Anđić', 'Laura')->id]);

        $c = Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('setDiscipline', $entry->id, 'kumite')
            ->call('setCategory', $entry->id, ' Kadetkinje -47 ')
            ->call('setNote', $entry->id, 'Vozi roditelj')
            ->call('setConfirmed', $entry->id, true);

        $fresh = $entry->fresh();
        $this->assertSame('kumite', $fresh->discipline->value);
        $this->assertSame('Kadetkinje -47', $fresh->category);
        $this->assertSame('Vozi roditelj', $fresh->note);
        $this->assertTrue($fresh->confirmed);

        $c->call('setDiscipline', $entry->id, 'judo')->assertDispatched('toast');
        $c->call('setCategory', $entry->id, str_repeat('x', 61))->assertDispatched('toast', message: 'Najviše 60 znakova');
        $c->call('setNote', $entry->id, str_repeat('x', 256))->assertDispatched('toast', message: 'Najviše 255 znakova');

        $fresh = $entry->fresh();
        $this->assertSame('kumite', $fresh->discipline->value);
        $this->assertSame('Kadetkinje -47', $fresh->category);
        $this->assertSame('Vozi roditelj', $fresh->note);
    }

    public function test_cannot_touch_entry_of_another_roster(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $foreign = CompetitionRoster::create(['name' => 'B'])->entries()->create(['student_id' => $this->child('Anić', 'Ana')->id]);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])->call('remove', $foreign->id); // Livewire turns the 404 into a response

        $this->assertDatabaseHas('competition_roster_entries', ['id' => $foreign->id]);
    }

    public function test_remove_entry_toasts(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $entry = $roster->entries()->create(['student_id' => $this->child('Anđić', 'Laura')->id]);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('remove', $entry->id)
            ->assertDispatched('toast', message: 'Uklonjeno: Anđić Laura');

        $this->assertDatabaseCount('competition_roster_entries', 0);
    }

    public function test_soft_deleted_student_hidden_and_not_counted(): void
    {
        $roster = CompetitionRoster::create(['name' => 'Open']);
        $gone = $this->child('Skrivić', 'Sanja');
        $kept = $this->child('Vidić', 'Vera');
        $roster->entries()->create(['student_id' => $gone->id, 'confirmed' => true]);
        $roster->entries()->create(['student_id' => $kept->id]);
        $gone->delete();

        Livewire::test(CompetitionRostersList::class)
            ->assertSee('prijava: 1 · potvrđeno: 0');

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->assertDontSee('Skrivić')
            ->assertSee('Vidić');
    }

    public function test_export_downloads_and_empty_roster_toasts(): void
    {
        $roster = CompetitionRoster::create(['name' => 'Open Zadar']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('export')
            ->assertNoFileDownloaded()
            ->assertDispatched('toast', message: 'Nema prijava za izvoz.');

        $roster->entries()->create(['student_id' => $this->child('Anđić', 'Laura')->id]);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('export')
            ->assertFileDownloaded('popis-open-zadar-'.CarbonImmutable::now()->format('Y-m-d').'.xlsx');

        // nothing confirmed + "samo potvrđeni" => nothing to export
        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->set('onlyConfirmed', true)
            ->call('export')
            ->assertNoFileDownloaded()
            ->assertDispatched('toast', message: 'Nema prijava za izvoz.');
    }

    public function test_edit_roster_from_show_page(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('openEdit')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required'])
            ->set('name', 'Novo')
            ->set('eventDate', '2026-12-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Novo', $roster->fresh()->name);
    }

    public function test_delete_roster_from_show_page_redirects(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->call('delete')
            ->assertRedirect(route('rosters'));

        $this->assertDatabaseMissing('competition_rosters', ['id' => $roster->id]);
    }
}
