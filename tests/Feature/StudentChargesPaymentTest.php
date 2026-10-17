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

final class StudentChargesPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithCharge(): Student
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        Charge::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-10', 'amount_cents' => 2500]);

        return $student;
    }

    public function test_trener_cannot_record_a_payment(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Trener])); // vidi sekciju (assign-fee-plan), ali ne smije uplatu
        $student = $this->studentWithCharge();

        Livewire::test(StudentCharges::class, ['studentId' => $student->id])
            ->call('openPayment')
            ->assertForbidden();
    }

    public function test_blagajnik_can_record_a_payment(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Blagajnik]));
        $student = $this->studentWithCharge();

        Livewire::test(StudentCharges::class, ['studentId' => $student->id])
            ->call('openPayment')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', ['student_id' => $student->id, 'amount_cents' => 2500]);
    }
}
