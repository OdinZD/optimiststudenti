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
        // Č sorts after C and before D in the Croatian alphabet; Babić first. Čaić is merged into ONE row.
        $this->assertSame(['Babić', 'Čaić', 'Dukić'], array_column($export['rows'], 0));
        $this->assertSame(['Ana', '05.05.2015.', 'Kate/Kumite', 'U12 -40', 'x', 'Kate: Ne / Kumite: Da'], [
            $export['rows'][1][1], $export['rows'][1][2], $export['rows'][1][5], $export['rows'][1][6], $export['rows'][1][7], $export['rows'][1][8],
        ]);
        $this->assertSame(['Bez datuma rođenja', 'Nije uneseno', '', '', '', 'Ne'], array_slice($export['rows'][2], 3));
    }

    public function test_one_row_per_child_merges_disciplines_with_labels_when_values_differ(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $s = Student::create(['last_name' => 'Anić', 'first_name' => 'Ana']);
        $roster->entries()->create(['student_id' => $s->id, 'discipline' => 'kumite', 'category' => '-40 kg', 'note' => 'isto', 'confirmed' => true]);
        $roster->entries()->create(['student_id' => $s->id, 'discipline' => 'kate', 'category' => 'U12', 'note' => 'isto', 'confirmed' => true]);

        $rows = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::now())['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame('Kate/Kumite', $rows[0][5]);
        $this->assertSame('Kate: U12 / Kumite: -40 kg', $rows[0][6]); // različito → s oznakom discipline
        $this->assertSame('isto', $rows[0][7]);                       // isto → samo vrijednost
        $this->assertSame('Da', $rows[0][8]);                         // obje potvrđene
    }

    public function test_only_confirmed_keeps_just_the_confirmed_discipline_of_a_child(): void
    {
        $roster = CompetitionRoster::create(['name' => 'A']);
        $s = Student::create(['last_name' => 'Anić', 'first_name' => 'Ana']);
        $roster->entries()->create(['student_id' => $s->id, 'discipline' => 'kate', 'confirmed' => true]);
        $roster->entries()->create(['student_id' => $s->id, 'discipline' => 'kumite', 'confirmed' => false]);

        $rows = CompetitionRosterExport::build($this->entries($roster), CarbonImmutable::now(), true)['rows'];

        $this->assertCount(1, $rows);
        $this->assertSame(['Kate', 'Da'], [$rows[0][5], $rows[0][8]]);
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
