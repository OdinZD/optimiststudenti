<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\KataTeam;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds the kata-team competition export: one section per team that has at least
 * three members (competition-ready), each member with name, birth date and age
 * category. Pure/array-based so filtering, grouping and sort order are testable.
 */
final class KataTeamExport
{
    public const HEADERS = ['Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija'];

    /** A team needs at least this many members to compete. */
    private const MIN_MEMBERS = 3;

    /**
     * @param  Collection<int, KataTeam>  $teams  with 'students' eager-loaded
     * @return array{headers: list<string>, sections: list<array{title: string, color: string, rows: list<list<string>>}>}
     */
    public static function build(Collection $teams, CarbonImmutable $today): array
    {
        $sections = $teams
            ->filter(fn (KataTeam $team): bool => $team->students->count() >= self::MIN_MEMBERS)
            ->map(fn (KataTeam $team): array => [
                'title' => $team->name,
                'color' => $team->color,
                'rows' => $team->students
                    ->sort(fn (Student $a, Student $b): int => CroatianCollator::compare($a->last_name, $b->last_name)
                        ?: CroatianCollator::compare($a->first_name, $b->first_name))
                    ->map(fn (Student $s): array => [
                        $s->last_name,
                        $s->first_name,
                        $s->birth_date?->format('d.m.Y.') ?? '',
                        AgeCategory::for($s->birth_date, $today)?->label ?? 'Bez datuma rođenja',
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return ['headers' => self::HEADERS, 'sections' => $sections];
    }
}
