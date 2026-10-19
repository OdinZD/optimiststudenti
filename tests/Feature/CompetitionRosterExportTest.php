<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CompetitionRoster;
use App\Models\Student;
use App\Support\CompetitionRosterExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompetitionRosterExportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function entries(CompetitionRoster $roster)
    {
        return $roster->entries()->with('student')->get();
    }

    public function test_headers_values_and_croatian_sort_then_discipline_order(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $c = Student::create(['last_name' => 'Čaić', 'first_name' => 'Ana', 'birth_date' => '2015-05-05']);
        $d = Student::create(['last_name' => 'Dukić', 'first_name' => 'Dino']);
        $b = Student::create(['last_name' => 'Babić', 'first_name' => 'Bruno', 'birth_date' => '2015-05-05']);
        $roster->entries()->create(['student_id' => $d->id]);
        $roster->entries()->create(['student_id' => $c->id, 'discipline' => 'kumite', 'category' => 'U12 -40', 'note' => 'x', 'confirmed' => true]);
        $roster->entries()->create(['student_id' => $c->id, 'discipline' => 'kate']);
        $roster->entries()->create(['student_id' => $b->id]);

        $export = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::parse('2026-10-19'));

        $this->assertSame(
            ['Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija', 'Pojas', 'Disciplina', 'Kategorija', 'Napomena', 'Potvrđeno'],
            $export['headers'],
        );
        // Č sorts after C and before D in the Croatian alphabet; Babić first.
        $this->assertSame(['Babić', 'Čaić', 'Čaić', 'Dukić'], array_column($export['rows'], 0));
        $this->assertSame(['Kate', 'Kumite'], [$export['rows'][1][5], $export['rows'][2][5]]);
        $this->assertSame(['Ana', '05.05.2015.', 'Kumite', 'U12 -40', 'x', 'Da'], [
            $export['rows'][2][1], $export['rows'][2][2], $export['rows'][2][5], $export['rows'][2][6], $export['rows'][2][7], $export['rows'][2][8],
        ]);
        $this->assertSame(['Bez datuma rođenja', 'Nije uneseno', '', '', '', 'Ne'], array_slice($export['rows'][3], 3));
    }

    public function test_only_confirmed_filter(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $a = Student::create(['last_name' => 'Anić', 'first_name' => 'Ana']);
        $b = Student::create(['last_name' => 'Babić', 'first_name' => 'Bruno']);
        $roster->entries()->create(['student_id' => $a->id]);
        $roster->entries()->create(['student_id' => $b->id, 'confirmed' => true]);

        $all = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::now());
        $confirmed = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::now(), true);

        $this->assertCount(2, $all['rows']);
        $this->assertSame([['Babić']], array_map(fn (array $r): array => [$r[0]], $confirmed['rows']));
    }

    public function test_age_category_is_evaluated_as_of_given_date(): void
    {
        CarbonImmutable::setTestNow('2026-10-19');
        $roster = CompetitionRoster::create(['name' => 'A']);
        // turns 12 on 2026-12-01: still U12 'Učenici'? age 11 now, 12 at the event
        $s = Student::create(['last_name' => 'Anić', 'first_name' => 'Ana', 'birth_date' => '2014-12-01']);
        $roster->entries()->create(['student_id' => $s->id]);

        $today = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::now());
        $event = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::parse('2027-01-15'));

        $this->assertNotSame($today['rows'][0][3], $event['rows'][0][3]);
        $this->assertSame('U12 · Učenici', $today['rows'][0][3]);
        $this->assertSame('U14 · Mlađi kadeti', $event['rows'][0][3]);
    }
}
