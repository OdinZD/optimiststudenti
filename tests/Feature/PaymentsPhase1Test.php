<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ClubBillingSettings;
use App\Models\User;
use App\Support\Auditer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Faza 1 (temelji): feature flag, uloge + Gate, postavke, audit.
 * S isključenim flagom modul ne dodaje nikakvu rutu/navigaciju/ekran — to štiti
 * cijeli postojeći set testova (koji i dalje mora biti zelen).
 */
final class PaymentsPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_payments_feature_flag_defaults_off(): void
    {
        $this->assertFalse(config('features.payments'));
    }

    public function test_new_user_defaults_to_trener_role(): void
    {
        $user = User::factory()->create()->refresh();

        $this->assertSame(Role::Trener, $user->role);
        $this->assertFalse($user->canManagePayments());
    }

    public function test_manage_payments_gate_allows_admin_and_blagajnik_only(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $cashier = User::factory()->create(['role' => Role::Blagajnik]);
        $trainer = User::factory()->create(['role' => Role::Trener]);

        $this->assertTrue(Gate::forUser($admin)->allows('manage-payments'));
        $this->assertTrue(Gate::forUser($cashier)->allows('manage-payments'));
        $this->assertFalse(Gate::forUser($trainer)->allows('manage-payments'));
    }

    public function test_club_billing_settings_current_returns_the_single_row(): void
    {
        $this->assertNull(ClubBillingSettings::current());

        $row = ClubBillingSettings::create(['currency' => 'EUR']);

        $this->assertTrue(ClubBillingSettings::current()->is($row));
    }

    public function test_auditer_writes_an_append_only_entry(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($user);

        $log = Auditer::log('test.action', $user, ['x' => 1], ['x' => 2]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'test.action', 'user_id' => $user->id]);
        $this->assertSame(['x' => 1], $log->old_values);
        $this->assertSame(['x' => 2], $log->new_values);
        $this->assertSame(User::class, $log->auditable_type);
        $this->assertNotNull($log->created_at);
    }
}
