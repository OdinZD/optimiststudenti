<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FeePlanType;
use App\Enums\Role;
use App\Livewire\FeePlansList;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

final class FeePlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_fee_plans_gate_is_admin_only(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->create(['role' => Role::Admin]))->allows('manage-fee-plans'));
        $this->assertFalse(Gate::forUser(User::factory()->create(['role' => Role::Blagajnik]))->allows('manage-fee-plans'));
        $this->assertFalse(Gate::forUser(User::factory()->create(['role' => Role::Trener]))->allows('manage-fee-plans'));
    }

    public function test_assign_fee_plan_gate_allows_admin_and_trener_not_cashier(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->create(['role' => Role::Admin]))->allows('assign-fee-plan'));
        $this->assertTrue(Gate::forUser(User::factory()->create(['role' => Role::Trener]))->allows('assign-fee-plan'));
        $this->assertFalse(Gate::forUser(User::factory()->create(['role' => Role::Blagajnik]))->allows('assign-fee-plan'));
    }

    public function test_admin_can_create_edit_and_delete_a_tariff(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        Livewire::test(FeePlansList::class)
            ->set('name', '1× tjedno')
            ->set('amount', '25,00')
            ->set('type', 'monthly')
            ->set('active', true)
            ->call('save')
            ->assertHasNoErrors();

        $plan = FeePlan::firstWhere('name', '1× tjedno');
        $this->assertSame(2500, $plan->amount_cents);
        $this->assertSame(FeePlanType::Monthly, $plan->type);

        Livewire::test(FeePlansList::class)->call('openEdit', $plan->id)->set('amount', '30,00')->call('save')->assertHasNoErrors();
        $this->assertSame(3000, $plan->refresh()->amount_cents);

        Livewire::test(FeePlansList::class)->call('confirmDelete', $plan->id)->call('delete');
        $this->assertDatabaseMissing('fee_plans', ['id' => $plan->id]);
    }

    public function test_tariff_assigned_to_children_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $plan = FeePlan::create(['name' => 'X', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        StudentFeePlan::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'valid_from' => '2026-09-01']);

        Livewire::test(FeePlansList::class)->call('confirmDelete', $plan->id)->call('delete');

        $this->assertDatabaseHas('fee_plans', ['id' => $plan->id]); // blocked — deactivate instead
    }

    public function test_clanarine_nav_is_hidden_when_flag_off(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        $this->get('/polaznici')->assertOk()->assertDontSee('Članarine');
    }
}
