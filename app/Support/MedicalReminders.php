<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds the medical-clearance reminder list — the children a trainer needs to chase
 * for a renewal: expired, expiring within 30 days, or with no date on file. Includes
 * the parent contact (this is an internal call list, unlike the competition roster).
 * Pure/array-based so the column set, filtering and sort order are unit-testable.
 */
final class MedicalReminders
{
    public const HEADERS = ['Prezime', 'Ime', 'Lokacija', 'Vrijedi do', 'Status', 'Rok', 'Roditelj', 'Telefon'];

    /** Reminder statuses in urgency order (most urgent first). */
    private const ORDER = [
        'istekao' => 0,    // MedicalStatus::Expired
        'uskoro' => 1,     // MedicalStatus::SoonExpiring
        'nepoznato' => 2,  // MedicalStatus::Unknown
    ];

    /**
     * @param  Collection<int, Student>  $students
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public static function build(Collection $students, CarbonImmutable $today): array
    {
        $rows = $students
            ->map(static fn (Student $s): array => [
                'student' => $s,
                'status' => MedicalStatus::for($s->medical_valid_until, $s->has_no_medical, $today),
                'days' => MedicalStatus::daysUntil($s->medical_valid_until, $today),
            ])
            ->filter(static fn (array $r): bool => isset(self::ORDER[$r['status']->value]))
            ->sort(static function (array $a, array $b): int {
                // urgency bucket, then soonest/most-overdue first (both = smallest day count), then name
                return [self::ORDER[$a['status']->value], $a['days'] ?? 0]
                    <=> [self::ORDER[$b['status']->value], $b['days'] ?? 0]
                    ?: CroatianCollator::compare($a['student']->last_name, $b['student']->last_name)
                    ?: CroatianCollator::compare($a['student']->first_name, $b['student']->first_name);
            })
            ->map(static function (array $r): array {
                /** @var Student $s */
                $s = $r['student'];

                return [
                    $s->last_name,
                    $s->first_name,
                    $s->location?->name ?? '—',
                    $s->medical_valid_until?->format('d.m.Y.') ?? '',
                    $r['status']->label(),
                    self::rok($r['status'], $r['days']),
                    $s->parent_name ?? '',
                    $s->formatted_phone,
                ];
            })
            ->values()
            ->all();

        return ['headers' => self::HEADERS, 'rows' => $rows];
    }

    private static function rok(MedicalStatus $status, ?int $days): string
    {
        return match ($status) {
            MedicalStatus::Expired => 'istekao prije '.abs((int) $days).' '.self::dayWord(abs((int) $days)),
            MedicalStatus::SoonExpiring => $days === 0
                ? 'ističe danas'
                : 'ističe za '.$days.' '.self::dayWord((int) $days),
            default => 'nema upisano',
        };
    }

    /** Croatian day pluralisation: 1/21/31… → "dan", else "dana". */
    private static function dayWord(int $n): string
    {
        return ($n % 10 === 1 && $n % 100 !== 11) ? 'dan' : 'dana';
    }
}
