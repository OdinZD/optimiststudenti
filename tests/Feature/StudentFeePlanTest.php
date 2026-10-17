<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\StudentFeePlans;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class StudentFeePlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    public function test_assigning_defaults_payer_to_parent_and_stores_percentage_discount(): void
    {
        $plan = FeePlan::create(['name' => '1× tjedno', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'Anđić', 'first_name' => 'Laura', 'parent_name' => 'Ozana Klarić']);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->assertSet('payerName', 'Ozana Klarić') // default iz kartice djeteta
            ->set('feePlanId', (string) $plan->id)
            ->set('discountType', 'percent')
            ->set('discountValue', '20')
            ->set('validFrom', '2026-09-01')
            ->call('save')
            ->assertHasNoErrors();

        $a = StudentFeePlan::firstWhere('student_id', $student->id);
        $this->assertSame(20, $a->discount_percent);
        $this->assertNull($a->discount_cents);
        $this->assertSame('Ozana Klarić', $a->payer_name);
        $this->assertSame(2000, $a->effectiveAmountCents()); // 2500 − 20%
    }

    public function test_trener_may_assign_a_tariff(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Trener]));
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $plan->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2026-09-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_fee_plans', ['student_id' => $student->id, 'fee_plan_id' => $plan->id]);
    }

    public function test_fixed_discount_is_stored_in_cents(): void
    {
        $plan = FeePlan::create(['name' => 'X', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $plan->id)
            ->set('discountType', 'fixed')
            ->set('discountValue', '10,00')
            ->set('validFrom', '2026-09-01')
            ->call('save')
            ->assertHasNoErrors();

        $a = StudentFeePlan::firstWhere('student_id', $student->id);
        $this->assertNull($a->discount_percent);
        $this->assertSame(1000, $a->discount_cents);
        $this->assertSame(1500, $a->effectiveAmountCents());
    }

    public function test_overlapping_monthly_tariff_is_rejected(): void
    {
        $monthly = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        StudentFeePlan::create(['student_id' => $student->id, 'fee_plan_id' => $monthly->id, 'valid_from' => '2026-09-01', 'valid_until' => null]);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $monthly->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2026-10-01') // pada unutar otvorenog razdoblja postojeće
            ->call('save')
            ->assertHasErrors('feePlanId');

        $this->assertSame(1, StudentFeePlan::where('student_id', $student->id)->count());
    }

    public function test_non_overlapping_monthly_tariff_is_allowed(): void
    {
        $monthly = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        StudentFeePlan::create(['student_id' => $student->id, 'fee_plan_id' => $monthly->id, 'valid_from' => '2026-09-01', 'valid_until' => '2027-06-30']);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $monthly->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2027-09-01') // nakon isteka prethodne
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, StudentFeePlan::where('student_id', $student->id)->count());
    }

    public function test_yearly_tariff_does_not_conflict_with_monthly(): void
    {
        $monthly = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $yearly = FeePlan::create(['name' => 'G', 'amount_cents' => 20000, 'type' => 'yearly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        StudentFeePlan::create(['student_id' => $student->id, 'fee_plan_id' => $monthly->id, 'valid_from' => '2026-09-01']);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $yearly->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2026-09-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, StudentFeePlan::where('student_id', $student->id)->count());
    }
}
