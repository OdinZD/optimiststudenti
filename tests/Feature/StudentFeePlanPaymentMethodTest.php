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
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

final class StudentFeePlanPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    public function test_payer_email_is_required_for_racun_email(): void
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']); // bez parent_email

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $plan->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2026-09-01')
            ->set('nacinPlacanja', 'racun_email')
            ->set('payerEmail', '')
            ->call('save')
            ->assertHasErrors('payerEmail');

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $plan->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2026-09-01')
            ->set('nacinPlacanja', 'racun_email')
            ->set('payerEmail', 'roditelj@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_fee_plans', [
            'student_id' => $student->id, 'nacin_placanja' => 'racun_email', 'payer_email' => 'roditelj@example.com',
        ]);
    }

    public function test_gotovina_does_not_require_email(): void
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);

        Livewire::test(StudentFeePlans::class, ['studentId' => $student->id])
            ->set('feePlanId', (string) $plan->id)
            ->set('discountType', 'none')
            ->set('validFrom', '2026-09-01')
            ->set('nacinPlacanja', 'gotovina')
            ->set('payerEmail', '')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_missing_payment_method_is_detected(): void
    {
        Carbon::setTestNow('2026-10-18');
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);

        $missing = Student::create(['last_name' => 'Bez', 'first_name' => 'Nacina']);
        StudentFeePlan::create(['student_id' => $missing->id, 'fee_plan_id' => $plan->id, 'valid_from' => '2026-09-01', 'nacin_placanja' => null]);

        $ok = Student::create(['last_name' => 'Ima', 'first_name' => 'Nacin']);
        StudentFeePlan::create(['student_id' => $ok->id, 'fee_plan_id' => $plan->id, 'valid_from' => '2026-09-01', 'nacin_placanja' => 'gotovina']);

        $this->assertTrue($missing->missingPaymentMethod());
        $this->assertFalse($ok->missingPaymentMethod());

        Carbon::setTestNow();
    }
}
