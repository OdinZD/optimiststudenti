<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\ClubBillingSettings;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Support\ChargeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChargeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Student, 1: FeePlan} */
    private function monthlyAssignment(string $from, ?string $until = null, ?int $discountPercent = null): array
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        StudentFeePlan::create([
            'student_id' => $student->id,
            'fee_plan_id' => $plan->id,
            'valid_from' => $from,
            'valid_until' => $until,
            'discount_percent' => $discountPercent,
        ]);

        return [$student, $plan];
    }

    public function test_generates_a_charge_per_active_monthly_assignment(): void
    {
        [$student, $plan] = $this->monthlyAssignment('2026-09-01');

        $result = ChargeGenerator::generate('2026-10');

        $this->assertTrue($result['billed']);
        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('charges', [
            'student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-10', 'amount_cents' => 2500,
        ]);
    }

    public function test_is_idempotent(): void
    {
        $this->monthlyAssignment('2026-09-01');

        $first = ChargeGenerator::generate('2026-10');
        $second = ChargeGenerator::generate('2026-10');

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(1, Charge::count());
    }

    public function test_discount_is_snapshotted_on_the_charge(): void
    {
        [$student] = $this->monthlyAssignment('2026-09-01', null, 20);

        ChargeGenerator::generate('2026-10');

        $this->assertSame(2000, Charge::firstWhere('student_id', $student->id)->amount_cents); // 2500 − 20%
    }

    public function test_month_outside_billing_months_is_skipped(): void
    {
        ClubBillingSettings::create(['currency' => 'EUR', 'billing_months' => [9, 10, 11, 12, 1, 2, 3, 4, 5, 6]]);
        $this->monthlyAssignment('2025-09-01');

        $result = ChargeGenerator::generate('2026-07'); // srpanj nije mjesec naplate

        $this->assertFalse($result['billed']);
        $this->assertSame(0, Charge::count());
    }

    public function test_child_without_active_assignment_is_skipped(): void
    {
        Student::create(['last_name' => 'Bez', 'first_name' => 'Tarife']);

        $result = ChargeGenerator::generate('2026-10');

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, Charge::count());
    }

    public function test_tariff_starting_mid_month_is_charged_for_that_month(): void
    {
        [$student] = $this->monthlyAssignment('2026-10-15'); // počinje sredinom mjeseca

        $result = ChargeGenerator::generate('2026-10');

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('charges', ['student_id' => $student->id, 'period' => '2026-10']);
    }

    public function test_assignment_that_ended_before_period_is_not_charged(): void
    {
        $this->monthlyAssignment('2025-09-01', '2026-06-30'); // isteklo prije listopada 2026

        $result = ChargeGenerator::generate('2026-10');

        $this->assertSame(0, $result['created']);
    }
}
