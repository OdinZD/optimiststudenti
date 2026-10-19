<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Discipline;
use App\Models\CompetitionRosterEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Rows for the competition roster Excel export (one row per child). Pure/array-based like CompetitionExport,
 * so columns, ordering, merging and the age-as-of-event-date rule are unit-testable.
 */
final class CompetitionRosterExport
{
    public const HEADERS = [
        'Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija', 'Pojas',
        'Disciplina', 'Kategorija', 'Napomena', 'Potvrđeno',
    ];

    /**
     * One row per child: a child entered in several disciplines is merged into a single row
     * ("Kate/Kumite"), so the sheet can be sent to the organiser without duplicate names.
     *
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
            ->groupBy(static fn (CompetitionRosterEntry $e): int => $e->student_id)
            ->map(static function (Collection $group) use ($asOf): array {
                $s = $group->first()->student;

                return [
                    $s->last_name,
                    $s->first_name,
                    $s->birth_date?->format('d.m.Y.') ?? '',
                    AgeCategory::for($s->birth_date, $asOf)?->label ?? 'Bez datuma rođenja',
                    $s->belt_kyu?->label() ?? 'Nije uneseno',
                    $group->map(static fn (CompetitionRosterEntry $e): ?string => $e->discipline?->label())->filter()->implode('/'),
                    self::merged($group, static fn (CompetitionRosterEntry $e): string => (string) $e->category),
                    self::merged($group, static fn (CompetitionRosterEntry $e): string => (string) $e->note),
                    self::mergedConfirmed($group),
                ];
            })
            ->values()
            ->all();

        return ['headers' => self::HEADERS, 'rows' => $rows];
    }

    /**
     * Combines a per-entry text. Same or single non-empty value → just the value; different values
     * → "Kate: X / Kumite: Y" so it stays clear which discipline each belongs to.
     *
     * @param  Collection<int, CompetitionRosterEntry>  $group
     * @param  callable(CompetitionRosterEntry): string  $value
     */
    private static function merged(Collection $group, callable $value): string
    {
        $filled = $group->map(static fn (CompetitionRosterEntry $e): array => [$e->discipline?->label(), $value($e)])
            ->filter(static fn (array $pair): bool => trim($pair[1]) !== '');

        if ($filled->pluck(1)->unique()->count() <= 1) {
            return (string) ($filled->first()[1] ?? '');
        }

        return $filled->map(static fn (array $pair): string => ($pair[0] !== null ? $pair[0].': ' : '').$pair[1])->implode(' / ');
    }

    /** "Da" when every entry is confirmed, "Ne" when none, otherwise per discipline ("Kate: Da / Kumite: Ne"). */
    private static function mergedConfirmed(Collection $group): string
    {
        if ($group->every(static fn (CompetitionRosterEntry $e): bool => $e->confirmed)) {
            return 'Da';
        }

        if ($group->every(static fn (CompetitionRosterEntry $e): bool => ! $e->confirmed)) {
            return 'Ne';
        }

        return $group->map(static fn (CompetitionRosterEntry $e): string => ($e->discipline?->label() ?? 'Bez discipline').': '.($e->confirmed ? 'Da' : 'Ne'))->implode(' / ');
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
