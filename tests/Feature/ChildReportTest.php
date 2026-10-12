<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\StudentsList;
use App\Models\Competition;
use App\Models\CompetitionResult;
use App\Models\Student;
use App\Models\User;
use App\Support\ChildReport;
use App\Support\Season;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class ChildReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_groups_by_discipline_with_summary_and_medals(): void
    {
        // Season 2025/2026 = 01.09.2025.–01.07.2026.
        $child = Student::create(['last_name' => 'Anđić', 'first_name' => 'Laura']);
        $inSeason = Competition::create(['name' => 'Kup A', 'held_on' => '2026-03-01', 'city' => 'Zadar']);
        $prevSeason = Competition::create(['name' => 'Kup B', 'held_on' => '2025-05-01']); // season 2024/2025

        CompetitionResult::create(['student_id' => $child->id, 'competition_id' => $inSeason->id, 'discipline' => 'kate', 'place' => 2, 'wins' => 2, 'losses' => 1]);
        CompetitionResult::create(['student_id' => $child->id, 'competition_id' => $inSeason->id, 'discipline' => 'kumite', 'place' => 1, 'bouts' => 4]);
        CompetitionResult::create(['student_id' => $child->id, 'competition_id' => $prevSeason->id, 'discipline' => 'kate', 'place' => 3]); // other season, excluded

        $data = ChildReport::build($child, 2025);

        $this->assertSame('Anđić Laura', $data['childName']);
        $this->assertSame('2025/2026', $data['season']);
        $this->assertTrue($data['hasResults']);
        $this->assertCount(2, $data['disciplines']); // Kate + Kumite
        $this->assertSame(
            ['competitions' => 1, 'gold' => 1, 'silver' => 1, 'bronze' => 0, 'bouts' => 7],
            $data['summary'],
        );

        $kate = collect($data['disciplines'])->firstWhere('label', 'Kate');
        $this->assertSame('Srebro', $kate['rows'][0]['medal']); // place 2
        $this->assertSame(3, $kate['rows'][0]['bouts']);         // 2 + 1
    }

    public function test_season_without_results_is_empty(): void
    {
        $child = Student::create(['last_name' => 'Horvat', 'first_name' => 'Ivo']);

        $data = ChildReport::build($child, 2025);

        $this->assertFalse($data['hasResults']);
        $this->assertSame(0, $data['summary']['competitions']);
        $this->assertSame([], $data['disciplines']);
    }

    public function test_report_action_downloads_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $child = Student::create(['last_name' => 'Horvat', 'first_name' => 'Ivo']);

        $season = Season::slug(Season::current(CarbonImmutable::now()));

        Livewire::test(StudentsList::class)
            ->call('report', $child->id)
            ->assertFileDownloaded('izvjesce-horvat-ivo-'.$season.'.pdf');
    }
}
