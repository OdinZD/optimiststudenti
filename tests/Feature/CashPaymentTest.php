<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\StudentCharges;
use App\Models\Charge;
use App\Models\FeePlan;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CashPaymentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Student, 1: Charge} */
    private function studentWithCharge(): array
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        $charge = Charge::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-10', 'amount_cents' => 2500]);

        return [$student, $charge];
    }

    public function test_cash_payment_requires_received_by(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Blagajnik]));
        [$student] = $this->studentWithCharge();

        Livewire::test(StudentCharges::class, ['studentId' => $student->id])
            ->call('openCash')
            ->set('payReceivedBy', '') // izbrisan predloženi primatelj
            ->call('recordPayment')
            ->assertHasErrors('payReceivedBy');
    }

    public function test_cash_payment_creates_cash_payment_with_received_by_and_allocations(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Blagajnik]));
        [$student, $charge] = $this->studentWithCharge();

        Livewire::test(StudentCharges::class, ['studentId' => $student->id])
            ->call('openCash')
            ->set('payReceivedBy', 'Ana Trenerica')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $payment = Payment::firstWhere('student_id', $student->id);
        $this->assertSame('cash', $payment->method);
        $this->assertSame('manual', $payment->source);
        $this->assertSame('Ana Trenerica', $payment->received_by);
        $this->assertSame(2500, $payment->amount_cents);
        $this->assertSame(0, $charge->refresh()->outstandingCents()); // alocirano
    }
}
