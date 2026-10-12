<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Discipline;
use App\Enums\Medal;
use App\Models\CompetitionResult;
use App\Models\Student;

/**
 * Structured data for a child's end-of-season competition report (one karate season,
 * 1 Sep → 1 Jul — see App\Support\Season). Pure arrays so the grouping, totals and medal
 * derivation are unit-testable. $seasonStart is the season's September year (e.g. 2025 = 2025/2026).
 */
final class ChildReport
{
    /**
     * @return array{
     *     childName: string, season: string, club: string,
     *     disciplines: list<array{label: string, rows: list<array<string, string|int|null>>}>,
     *     summary: array{competitions: int, gold: int, silver: int, bronze: int, bouts: int},
     *     hasResults: bool
     * }
     */
    public static function build(Student $child, int $seasonStart): array
    {
        $results = $child->competitionResults()
            ->with('competition')
            ->get()
            ->filter(fn (CompetitionResult $r): bool => Season::contains($seasonStart, $r->competition->held_on))
            ->sortBy(fn (CompetitionResult $r) => $r->competition->held_on->getTimestamp());

        $disciplines = [];
        foreach (Discipline::options() as $discipline) {
            $rows = $results
                ->filter(fn (CompetitionResult $r): bool => $r->discipline === $discipline)
                ->map(fn (CompetitionResult $r): array => [
                    'competition' => $r->competition->name,
                    'date' => $r->competition->held_on->format('d.m.Y.'),
                    'city' => $r->competition->city,
                    'category' => $r->category,
                    'place' => $r->place,
                    'medal' => $r->medal?->label(),
                    'bouts' => self::bouts($r),
                    'wins' => $r->wins,
                    'losses' => $r->losses,
                ])
                ->values()
                ->all();

            if ($rows !== []) {
                $disciplines[] = ['label' => $discipline->label(), 'rows' => $rows];
            }
        }

        return [
            'childName' => "{$child->last_name} {$child->first_name}",
            'season' => Season::label($seasonStart),
            'club' => (string) config('optimist.club_name'),
            'disciplines' => $disciplines,
            'summary' => [
                'competitions' => $results->pluck('competition_id')->unique()->count(),
                'gold' => $results->filter(fn (CompetitionResult $r): bool => $r->medal === Medal::Gold)->count(),
                'silver' => $results->filter(fn (CompetitionResult $r): bool => $r->medal === Medal::Silver)->count(),
                'bronze' => $results->filter(fn (CompetitionResult $r): bool => $r->medal === Medal::Bronze)->count(),
                'bouts' => (int) $results->sum(fn (CompetitionResult $r): int => self::bouts($r)),
            ],
            'hasResults' => $results->isNotEmpty(),
        ];
    }

    private static function bouts(CompetitionResult $r): int
    {
        return $r->bouts ?? (($r->wins ?? 0) + ($r->losses ?? 0));
    }
}
