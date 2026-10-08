<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\StudentsList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PolazniciDataset;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * List screen behaviour against the real dataset (today = 2026-10-07).
 * Skipped when the gitignored PII file is absent.
 */
final class StudentsListTest extends TestCase
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
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_full_page_renders(): void
    {
        $this->get('/polaznici')
            ->assertOk()
            ->assertSee('Polaznici')
            ->assertSee('Antišin')
            ->assertSee('79 upisanih polaznika');
    }

    public function test_lists_all_sorted_by_croatian_alphabet(): void
    {
        Livewire::test(StudentsList::class)
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->count() === 79)
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->first()->last_name === 'Anđić')
            ->assertSeeInOrder(['Anđić', 'Antišin', 'Ažić']);
    }

    public function test_age_filter_counts(): void
    {
        Livewire::test(StudentsList::class)
            ->set('ageKey', 'u12')
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->count() === 20);
    }

    public function test_belt_and_attention_filters(): void
    {
        Livewire::test(StudentsList::class)
            ->set('belt', '6')
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->count() === 19);

        Livewire::test(StudentsList::class)
            ->set('expiredOnly', true)
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->count() === 28);

        Livewire::test(StudentsList::class)
            ->set('toRegisterOnly', true)
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->count() === 4);
    }

    public function test_diacritic_insensitive_search(): void
    {
        Livewire::test(StudentsList::class)
            ->set('search', 'kardum')
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->count() === 3);
    }

    public function test_age_chips_show_present_categories_with_counts(): void
    {
        Livewire::test(StudentsList::class)
            ->assertViewHas('ageChips', function (array $chips): bool {
                $byKey = collect($chips)->keyBy('key');

                return $byKey['']['count'] === 79
                    && $byKey['u08']['count'] === 7
                    && $byKey['u10']['count'] === 14
                    && $byKey['u12']['count'] === 20
                    && $byKey['u14']['count'] === 14
                    && $byKey['u16']['count'] === 4
                    && $byKey['sen']['count'] === 5
                    && $byKey[StudentsList::NO_BIRTH_DATE]['count'] === 15
                    && ! $byKey->has('ispod')
                    && ! $byKey->has('u18');
            });
    }

    public function test_subtitle_switches_when_filtering(): void
    {
        Livewire::test(StudentsList::class)
            ->set('ageKey', 'u12')
            ->assertViewHas('subtitle', 'Prikazano 20 od 79');
    }
}
