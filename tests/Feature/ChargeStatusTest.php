<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\FeePlan;
use App\Models\Student;
use App\Support\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ChargeStatusTest extends TestCase
{
    use RefreshDatabase;

    private FeePlan $plan;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20');
        $this->plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $this->student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function charge(string $period, ?string $due): Charge
    {
        return Charge::create([
            'student_id' => $this->student->id, 'fee_plan_id' => $this->plan->id,
            'period' => $period, 'amount_cents' => 2500, 'due_date' => $due,
        ]);
    }

    public function test_statuses_are_computed(): void
    {
        // neplaćeno (rok u budućnosti)
        $this->assertSame(ChargeStatus::Unpaid, $this->charge('2026-10', '2026-10-31')->status());

        // kasni (rok prošao, ništa plaćeno)
        $this->assertSame(ChargeStatus::Overdue, $this->charge('2026-09', '2026-09-30')->status());

        // plaćeno
        $paid = $this->charge('2026-08', '2026-08-31');
        PaymentRecorder::record($this->student, 2500, 'bank', 'manual', '2026-08-05', null, null, null, [$paid->id]);
        $this->assertSame(ChargeStatus::Paid, $paid->refresh()->status());

        // djelomično (rok u budućnosti)
        $partial = $this->charge('2026-11', '2026-11-30');
        PaymentRecorder::record($this->student, 1000, 'bank', 'manual', '2026-10-05', null, null, null, [$partial->id]);
        $this->assertSame(ChargeStatus::Partial, $partial->refresh()->status());

        // otpisano
        $wo = $this->charge('2026-07', '2026-07-31');
        PaymentRecorder::writeOff($wo, 'razlog', null);
        $this->assertSame(ChargeStatus::WrittenOff, $wo->refresh()->status());
    }
}
