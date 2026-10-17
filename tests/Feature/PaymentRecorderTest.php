<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\FeePlan;
use App\Models\Payment;
use App\Models\Student;
use App\Support\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class PaymentRecorderTest extends TestCase
{
    use RefreshDatabase;

    private FeePlan $plan;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $this->student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
    }

    private function charge(string $period, int $amount = 2500, ?string $due = null): Charge
    {
        return Charge::create([
            'student_id' => $this->student->id, 'fee_plan_id' => $this->plan->id,
            'period' => $period, 'amount_cents' => $amount, 'due_date' => $due,
        ]);
    }

    private function pay(int $amount, ?array $chargeIds = null): Payment
    {
        return PaymentRecorder::record($this->student, $amount, 'bank', 'manual', '2026-10-05', null, null, null, $chargeIds);
    }

    public function test_partial_payment_leaves_charge_partially_paid(): void
    {
        $charge = $this->charge('2026-10');

        $this->pay(1000);

        $charge->refresh();
        $this->assertSame(1000, $charge->allocatedCents());
        $this->assertSame(1500, $charge->outstandingCents());
        $this->assertSame(ChargeStatus::Partial, $charge->status());
    }

    public function test_one_payment_settles_three_months(): void
    {
        foreach (['2026-09', '2026-10', '2026-11'] as $m) {
            $this->charge($m);
        }

        $payment = $this->pay(7500);

        $this->assertSame(3, $payment->allocations()->count());
        $this->assertSame(0, Charge::where('student_id', $this->student->id)->get()->sum(fn (Charge $c): int => $c->outstandingCents()));
    }

    public function test_overpayment_stays_as_unallocated_credit(): void
    {
        $this->charge('2026-10');

        $payment = $this->pay(4000);

        $this->assertSame(2500, (int) $payment->allocations()->sum('amount_cents')); // samo zaduženje
        $this->assertSame(4000, $payment->amount_cents);                             // preplata ostaje neraspoređena
    }

    public function test_fifo_allocates_to_the_oldest_charge_first(): void
    {
        $sep = $this->charge('2026-09');
        $oct = $this->charge('2026-10');

        $this->pay(2500);

        $this->assertSame(0, $sep->refresh()->outstandingCents());
        $this->assertSame(2500, $oct->refresh()->outstandingCents());
    }

    public function test_manual_charge_selection_is_respected(): void
    {
        $sep = $this->charge('2026-09');
        $oct = $this->charge('2026-10');

        $this->pay(2500, [$oct->id]); // samo listopad

        $this->assertSame(2500, $sep->refresh()->outstandingCents()); // netaknuto
        $this->assertSame(0, $oct->refresh()->outstandingCents());
    }

    public function test_allocations_never_exceed_payment_or_charge(): void
    {
        $c1 = $this->charge('2026-09');
        $c2 = $this->charge('2026-10');

        $payment = $this->pay(3000);

        $this->assertLessThanOrEqual(3000, (int) $payment->allocations()->sum('amount_cents'));
        $this->assertSame(2500, $c1->refresh()->allocatedCents()); // puno
        $this->assertSame(500, $c2->refresh()->allocatedCents());  // ostatak
        $this->assertLessThanOrEqual($c2->amount_cents, $c2->allocatedCents());
    }

    public function test_write_off_marks_the_charge_and_requires_a_reason(): void
    {
        $charge = $this->charge('2026-10');

        PaymentRecorder::writeOff($charge, 'Socijalni slučaj', null);

        $charge->refresh();
        $this->assertTrue($charge->isWrittenOff());
        $this->assertSame(ChargeStatus::WrittenOff, $charge->status());

        $this->expectException(RuntimeException::class);
        PaymentRecorder::writeOff($this->charge('2026-11'), '   ', null);
    }

    public function test_reversing_a_payment_reopens_the_charge(): void
    {
        $charge = $this->charge('2026-10');
        $payment = $this->pay(2500);
        $this->assertSame(0, $charge->refresh()->outstandingCents());

        $reversal = PaymentRecorder::reverse($payment, null);

        $this->assertSame(-2500, $reversal->amount_cents);
        $this->assertSame($payment->id, $reversal->reversed_payment_id);
        $this->assertSame(2500, $charge->refresh()->outstandingCents()); // ponovno otvoreno
    }

    public function test_a_payment_cannot_be_reversed_twice(): void
    {
        $this->charge('2026-10');
        $payment = $this->pay(2500);
        PaymentRecorder::reverse($payment, null);

        $this->expectException(RuntimeException::class);
        PaymentRecorder::reverse($payment, null);
    }
}
