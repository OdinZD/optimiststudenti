<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FeePlan;
use App\Models\StudentFeePlan;
use Tests\TestCase;

/**
 * Discount math (no DB): percentage (rounded to the cent) and fixed (capped at the
 * base amount), plus the effective amount = tariff − discount.
 */
final class StudentFeePlanDiscountTest extends TestCase
{
    public function test_percentage_discount_rounds_to_the_cent(): void
    {
        $this->assertSame(123, (new StudentFeePlan(['discount_percent' => 10]))->discountAmountCents(1234)); // 123.4 → 123
        $this->assertSame(34, (new StudentFeePlan(['discount_percent' => 10]))->discountAmountCents(335));   // 33.5 → 34
    }

    public function test_fixed_discount_is_capped_at_the_base_amount(): void
    {
        $this->assertSame(500, (new StudentFeePlan(['discount_cents' => 500]))->discountAmountCents(2000));
        $this->assertSame(300, (new StudentFeePlan(['discount_cents' => 500]))->discountAmountCents(300)); // cap
    }

    public function test_no_discount_returns_zero(): void
    {
        $this->assertSame(0, (new StudentFeePlan)->discountAmountCents(2000));
    }

    public function test_effective_amount_is_tariff_minus_discount_never_below_zero(): void
    {
        $percent = new StudentFeePlan(['discount_percent' => 20]);
        $percent->setRelation('feePlan', new FeePlan(['amount_cents' => 5000]));
        $this->assertSame(4000, $percent->effectiveAmountCents());

        $big = new StudentFeePlan(['discount_cents' => 9999]);
        $big->setRelation('feePlan', new FeePlan(['amount_cents' => 2500]));
        $this->assertSame(0, $big->effectiveAmountCents());
    }
}
