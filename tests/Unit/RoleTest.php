<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function test_only_admin_and_blagajnik_may_manage_payments(): void
    {
        $this->assertTrue(Role::Admin->canManagePayments());
        $this->assertTrue(Role::Blagajnik->canManagePayments());
        $this->assertFalse(Role::Trener->canManagePayments());
    }

    public function test_labels_are_croatian(): void
    {
        $this->assertSame('Administrator', Role::Admin->label());
        $this->assertSame('Blagajnik', Role::Blagajnik->label());
        $this->assertSame('Trener', Role::Trener->label());
    }
}
