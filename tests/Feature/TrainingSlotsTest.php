<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\CompetitionRosterShow;
use App\Livewire\StudentForm;
use App\Livewire\StudentsList;
use App\Models\CompetitionRoster;
use App\Models\Location;
use App\Models\Student;
use App\Models\TrainingGroup;
use App\Models\TrainingSlot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

final class TrainingSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    private function child(string $last, string $first, string ...$groupSlugs): Student
    {
        $student = Student::create(['last_name' => $last, 'first_name' => $first, 'birth_date' => '2015-05-05']);
        $student->trainingGroups()->attach(TrainingGroup::whereIn('slug', $groupSlugs)->pluck('id'));

        return $student->fresh();
    }

    public function test_seeder_creates_four_slots_with_seven_group_links_and_renames_visnjik(): void
    {
        $this->assertSame(4, TrainingSlot::count());
        $this->assertSame(7, DB::table('group_training_slot')->count());
        $this->assertSame('PS Višnjik', Location::where('slug', 'visnjik-pon-sri')->value('name'));
        $this->assertSame('OŠ Stanovi · Uto/Čet 20–21 h', TrainingSlot::with('location')->where('slug', 'stanovi-uto-cet')->first()->label());
    }

    public function test_student_in_two_groups_derives_union_of_slots_and_both_locations(): void
    {
        $student = $this->child('Anić', 'Ana', 'pocetnici', 'kumite-stariji');

        $this->assertSame(
            ['krstica-uto-cet', 'stanovi-uto-cet', 'visnjik-pon'],
            $student->training_slots->pluck('slug')->all(),
        );
        $this->assertSame(
            ['OŠ K. Krstića', 'OŠ Stanovi', 'PS Višnjik'],
            $student->training_locations->pluck('name')->all(),
        );
    }

    public function test_student_without_groups_falls_back_to_legacy_location(): void
    {
        $student = Student::create(['last_name' => 'Babić', 'first_name' => 'Bruno', 'location_id' => Location::where('slug', 'os-stanovi')->value('id')]);

        $this->assertTrue($student->training_slots->isEmpty());
        $this->assertSame(['OŠ Stanovi'], $student->training_locations->pluck('name')->all());
    }

    public function test_slot_filter_matches_through_groups_and_group_filter_supports_many_groups(): void
    {
        $this->child('Anić', 'Ana', 'pocetnici');                      // Krstića only
        $this->child('Babić', 'Bruno', 'pocetnici', 'kata-napredni'); // Krstića + Stanovi
        $this->child('Cerić', 'Cvita', 'kumite-mladi');                // Višnjik Sri
        $this->child('Dukić', 'Dino');                                 // no group

        Livewire::test(StudentsList::class)
            ->set('slot', 'stanovi-uto-cet')
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->pluck('last_name')->all() === ['Babić'])
            ->set('slot', '')
            ->set('group', 'pocetnici')
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->pluck('last_name')->all() === ['Anić', 'Babić'])
            ->set('group', 'kata-napredni') // Babić matches through his second group
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->pluck('last_name')->all() === ['Babić']);
    }

    public function test_list_shows_legacy_location_dimmed_for_children_without_groups(): void
    {
        $legacy = Student::create(['last_name' => 'Babić', 'first_name' => 'Bruno', 'location_id' => Location::where('slug', 'os-stanovi')->value('id')]);
        $this->child('Anić', 'Ana', 'pocetnici');

        $this->assertTrue($legacy->fresh()->hasLegacyLocationOnly());
        $this->assertFalse(Student::firstWhere('last_name', 'Anić')->hasLegacyLocationOnly());

        Livewire::test(StudentsList::class)
            ->assertSee('OŠ Stanovi (bez grupe)')   // dimmed fallback, clearly marked
            ->assertSee('OŠ K. Krstića');           // derived from the group, no marker
    }

    public function test_list_offers_every_slot_in_the_termin_filter(): void
    {
        Livewire::test(StudentsList::class)
            ->assertSee('Svi termini')
            ->assertSee('OŠ K. Krstića · Uto/Čet 17–18 h')
            ->assertSee('OŠ Stanovi · Uto/Čet 20–21 h')
            ->assertSee('PS Višnjik · Pon 20–21 h')
            ->assertSee('PS Višnjik · Sri 20–21 h');
    }

    public function test_export_with_slot_filter_names_the_file_after_the_slot(): void
    {
        CarbonImmutable::setTestNow('2026-10-19');
        $this->child('Anić', 'Ana', 'kata-napredni');

        Livewire::test(StudentsList::class)
            ->set('slot', 'stanovi-uto-cet')
            ->call('export')
            ->assertFileDownloaded('polaznici-stanovi-uto-cet-2026-10-19.xlsx');

        CarbonImmutable::setTestNow();
    }

    public function test_form_saves_multiple_groups_on_create_and_edit_and_shows_derived_slots(): void
    {
        $pocetnici = TrainingGroup::where('slug', 'pocetnici')->value('id');
        $kumite = TrainingGroup::where('slug', 'kumite-stariji')->value('id');

        Livewire::test(StudentForm::class)
            ->call('openCreate')
            ->set('lastName', 'Anić')
            ->set('firstName', 'Ana')
            ->set('trainingGroupIds', [(string) $pocetnici, (string) $kumite])
            ->assertSee('OŠ K. Krstića · Uto/Čet 17–18 h')
            ->assertSee('PS Višnjik · Pon 20–21 h')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::firstWhere('last_name', 'Anić');
        $this->assertEqualsCanonicalizing([$pocetnici, $kumite], $student->trainingGroups->pluck('id')->all());

        Livewire::test(StudentForm::class)
            ->call('openEdit', $student->id)
            ->assertSet('trainingGroupIds', [(string) min($pocetnici, $kumite), (string) max($pocetnici, $kumite)])
            ->set('trainingGroupIds', [(string) $kumite])
            ->call('save');

        $this->assertSame([$kumite], $student->fresh()->trainingGroups->pluck('id')->all());
    }

    public function test_migration_backfills_pivot_from_the_legacy_group_column(): void
    {
        $group = TrainingGroup::where('slug', 'pocetnici')->value('id');
        $with = Student::create(['last_name' => 'Anić', 'first_name' => 'Ana', 'training_group_id' => $group]);
        Student::create(['last_name' => 'Babić', 'first_name' => 'Bruno']);

        Schema::drop('student_training_group');
        (require base_path('database/migrations/2026_10_20_100001_create_student_training_group_table.php'))->up();

        $this->assertSame(
            [['student_id' => $with->id, 'training_group_id' => $group]],
            DB::table('student_training_group')->get(['student_id', 'training_group_id'])->map(fn ($r): array => (array) $r)->all(),
        );
    }

    public function test_roster_quick_add_by_group_includes_children_who_are_in_several_groups(): void
    {
        $many = $this->child('Anić', 'Ana', 'pocetnici', 'kumite-stariji');
        $this->child('Babić', 'Bruno', 'kumite-mladi');
        $roster = CompetitionRoster::create(['name' => 'A']);

        Livewire::test(CompetitionRosterShow::class, ['roster' => $roster])
            ->set('quick', 'g:'.TrainingGroup::where('slug', 'kumite-stariji')->value('id'))
            ->assertSee('Dodaj sve (1)')
            ->call('addQuick');

        $this->assertSame([$many->id], $roster->entries()->pluck('student_id')->all());
    }
}
