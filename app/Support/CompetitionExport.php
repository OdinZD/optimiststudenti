<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds the competition roster rows (SPEC: essentials only — no OIB, no medical).
 * Pure/array-based so the column set and values are unit-testable without a spreadsheet.
 */
final class CompetitionExport
{
    public const HEADERS = ['Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija', 'Pojas'];

    /**
     * @param  Collection<int, Student>  $students
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public static function build(Collection $students, CarbonImmutable $today): array
    {
        $rows = $students->map(static function (Student $student) use ($today): array {
            $category = AgeCategory::for($student->birth_date, $today);

            return [
                $student->last_name,
                $student->first_name,
                $student->birth_date?->format('d.m.Y.') ?? '',
                $category?->label ?? 'Bez datuma rođenja',
                $student->belt_kyu?->label() ?? 'Nije uneseno',
            ];
        })->values()->all();

        return ['headers' => self::HEADERS, 'rows' => $rows];
    }
}
