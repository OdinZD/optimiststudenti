<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\KataTeam;
use App\Models\Student;
use App\Support\AgeCategory;
use App\Support\KataTeamExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KataTeamExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-10-11');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_only_teams_with_three_plus_members_are_exported_sorted_croatian(): void
    {
        $alfa = KataTeam::create(['name' => 'Alfa', 'color' => '#2D3E7B']);
        $beta = KataTeam::create(['name' => 'Beta', 'color' => '#C23B30']); // only 2 → excluded

        // deliberately non-alphabetical to prove the Croatian sort
        $zubak = Student::create(['last_name' => 'Zubak', 'first_name' => 'A', 'birth_date' => '2014-03-01']);
        $andic = Student::create(['last_name' => 'Anđić', 'first_name' => 'Laura', 'birth_date' => '2014-05-15']);
        $covic = Student::create(['last_name' => 'Čović', 'first_name' => 'M']); // no birth date
        $alfa->students()->attach([$zubak->id, $andic->id, $covic->id]);

        $beta->students()->attach([
            Student::create(['last_name' => 'X', 'first_name' => 'Y'])->id,
            Student::create(['last_name' => 'W', 'first_name' => 'V'])->id,
        ]);

        $export = KataTeamExport::build(KataTeam::with('students')->orderBy('name')->get(), CarbonImmutable::now());

        $this->assertSame(['Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija'], $export['headers']);
        $this->assertCount(1, $export['sections']); // Beta (2 members) excluded

        $section = $export['sections'][0];
        $this->assertSame('Alfa', $section['title']);
        $this->assertSame('#2D3E7B', $section['color']);

        // members sorted by Croatian alphabet: Anđić (A) < Čović (Č) < Zubak (Z)
        $this->assertSame(['Anđić', 'Čović', 'Zubak'], array_column($section['rows'], 0));

        // Anđić row: prezime, ime, datum, uzrast (uzrast derived via AgeCategory)
        $this->assertSame('Laura', $section['rows'][0][1]);
        $this->assertSame('15.05.2014.', $section['rows'][0][2]);
        $this->assertSame(
            AgeCategory::for(CarbonImmutable::parse('2014-05-15'), CarbonImmutable::now())?->label,
            $section['rows'][0][3],
        );

        // Čović has no birth date → blank date + "Bez datuma rođenja"
        $this->assertSame(['Čović', 'M', '', 'Bez datuma rođenja'], $section['rows'][1]);
    }

    public function test_no_competition_ready_team_yields_no_sections(): void
    {
        $team = KataTeam::create(['name' => 'Alfa', 'color' => '#2D3E7B']);
        $team->students()->attach(Student::create(['last_name' => 'A', 'first_name' => 'x'])->id);

        $export = KataTeamExport::build(KataTeam::with('students')->orderBy('name')->get(), CarbonImmutable::now());

        $this->assertSame([], $export['sections']);
    }
}
