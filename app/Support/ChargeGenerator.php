<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\FeePlanType;
use App\Models\Charge;
use App\Models\ClubBillingSettings;
use App\Models\StudentFeePlan;
use Carbon\CarbonImmutable;

/**
 * Creates monthly membership charges for a period ('YYYY-MM'): one per active monthly
 * assignment whose validity covers that month, but only when the month is a billing
 * month (club settings). Idempotent — re-running creates no duplicates.
 */
final class ChargeGenerator
{
    /** @return array{billed: bool, created: int, skipped: int} */
    public static function generate(string $period): array
    {
        $start = CarbonImmutable::parse($period.'-01')->startOfMonth();
        $end = $start->endOfMonth();
        $settings = ClubBillingSettings::current();

        if (! self::isBillingMonth((int) $start->format('n'), $settings)) {
            return ['billed' => false, 'created' => 0, 'skipped' => 0];
        }

        $dueDate = self::dueDate($start, $settings);
        $model = $settings?->model;

        $assignments = StudentFeePlan::query()
            ->whereHas('feePlan', fn ($q) => $q->where('type', FeePlanType::Monthly->value))
            ->whereHas('student') // izostavi soft-deleted djecu
            ->where('valid_from', '<=', $end->toDateString())
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $start->toDateString()))
            ->with('feePlan')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($assignments as $assignment) {
            $charge = Charge::firstOrCreate(
                ['student_id' => $assignment->student_id, 'fee_plan_id' => $assignment->fee_plan_id, 'period' => $period],
                [
                    'student_fee_plan_id' => $assignment->id,
                    'amount_cents' => $assignment->effectiveAmountCents(),
                    'due_date' => $dueDate?->toDateString(),
                    'reference' => BillingReference::for($assignment->student_id, $period, $model),
                ],
            );

            $charge->wasRecentlyCreated ? $created++ : $skipped++;
        }

        return ['billed' => true, 'created' => $created, 'skipped' => $skipped];
    }

    private static function isBillingMonth(int $month, ?ClubBillingSettings $settings): bool
    {
        $months = $settings?->billing_months;

        if ($months === null || $months === []) {
            return true; // nije postavljeno → naplaćuj svaki mjesec
        }

        return in_array($month, array_map('intval', $months), true);
    }

    private static function dueDate(CarbonImmutable $start, ?ClubBillingSettings $settings): ?CarbonImmutable
    {
        $day = $settings?->due_day;

        if ($day === null) {
            return null;
        }

        return $start->day(min($day, $start->daysInMonth));
    }
}
