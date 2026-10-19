<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Discipline;
use App\Models\CompetitionRosterEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Rows for the competition roster Excel export. Pure/array-based like CompetitionExport,
 * so columns, ordering and the age-as-of-event-date rule are unit-testable.
 */
final class CompetitionRosterExport
{
    public const HEADERS = [
        'Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija', 'Pojas',
        'Disciplina', 'Kategorija', 'Napomena', 'Potvrđeno',
    ];

    /**
     * @param  Collection<int, CompetitionRosterEntry>  $entries  entries with `student` loaded
     * @param  CarbonImmutable  $asOf  event date (or today) — the age category is evaluated on this day
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public static function build(Collection $entries, CarbonImmutable $asOf, bool $onlyConfirmed = false): array
    {
        $rows = $entries
            ->filter(static fn (CompetitionRosterEntry $e): bool => $e->student !== null)
            ->when($onlyConfirmed, static fn (Collection $c): Collection => $c->filter(
                static fn (CompetitionRosterEntry $e): bool => $e->confirmed,
            ))
            ->sort(static fn (CompetitionRosterEntry $a, CompetitionRosterEntry $b): int => self::compare($a, $b))
            ->map(static function (CompetitionRosterEntry $e) use ($asOf): array {
                $s = $e->student;

                return [
                    $s->last_name,
                    $s->first_name,
                    $s->birth_date?->format('d.m.Y.') ?? '',
                    AgeCategory::for($s->birth_date, $asOf)?->label ?? 'Bez datuma rođenja',
                    $s->belt_kyu?->label() ?? 'Nije uneseno',
                    $e->discipline?->label() ?? '',
                    $e->category ?? '',
                    $e->note ?? '',
                    $e->confirmed ? 'Da' : 'Ne',
                ];
            })
            ->values()
            ->all();

        return ['headers' => self::HEADERS, 'rows' => $rows];
    }

    /** Croatian alphabet by last then first name, then kate, kumite, kata_team, no discipline. */
    public static function compare(CompetitionRosterEntry $a, CompetitionRosterEntry $b): int
    {
        return CroatianCollator::compare($a->student->last_name, $b->student->last_name)
            ?: CroatianCollator::compare($a->student->first_name, $b->student->first_name)
            ?: self::disciplineRank($a->discipline) <=> self::disciplineRank($b->discipline);
    }

    private static function disciplineRank(?Discipline $discipline): int
    {
        if ($discipline === null) {
            return 99;
        }

        return (int) array_search($discipline, Discipline::options(), true);
    }
}
