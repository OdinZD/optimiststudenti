<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\StudentCharges;
use App\Models\Charge;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class StudentChargesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    public function test_adds_a_manual_one_time_charge_with_amount_suggested_from_the_tariff(): void
    {
        $plan = FeePlan::create(['name' => 'Upisnina', 'amount_cents' => 3000, 'type' => 'one_time', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);

        Livewire::test(StudentCharges::class, ['studentId' => $student->id])
            ->set('mcFeePlanId', (string) $plan->id) // okida prijedlog iznosa
            ->assertSet('mcAmount', '30,00')
            ->set('mcPeriod', '2026-10')
            ->call('addCharge')
            ->assertHasNoErrors();

        $charge = Charge::firstWhere('student_id', $student->id);
        $this->assertSame(3000, $charge->amount_cents);
        $this->assertNull($charge->student_fee_plan_id); // ručno
        $this->assertSame('2026-10', $charge->period);
    }

    public function test_duplicate_charge_for_same_plan_and_period_is_rejected(): void
    {
        $plan = FeePlan::create(['name' => 'Upisnina', 'amount_cents' => 3000, 'type' => 'one_time', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        Charge::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-10', 'amount_cents' => 3000]);

        Livewire::test(StudentCharges::class, ['studentId' => $student->id])
            ->set('mcFeePlanId', (string) $plan->id)
            ->set('mcPeriod', '2026-10')
            ->set('mcAmount', '30,00')
            ->call('addCharge')
            ->assertHasErrors('mcFeePlanId');

        $this->assertSame(1, Charge::where('student_id', $student->id)->count());
    }
}
