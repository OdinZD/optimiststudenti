<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\BillingReference;
use PHPUnit\Framework\TestCase;

final class BillingReferenceTest extends TestCase
{
    public function test_control_digit_mod11_matches_hub_specification(): void
    {
        // težine 2,3,…,9,2,… zdesna; 1·8+2·7+0·6+2·5+6·4+1·3+0·2 = 59; 11 − (59 mod 11) = 7
        $this->assertSame(7, BillingReference::controlDigitMod11('1202610'));
        // ostatak 0 → kontrolna 0
        $this->assertSame(0, BillingReference::controlDigitMod11('1234567890'));
    }

    public function test_reference_has_no_control_digit_for_hr00_or_unknown(): void
    {
        $this->assertSame('1-202610', BillingReference::for(1, '2026-10', 'HR00'));
        $this->assertSame('1-202610', BillingReference::for(1, '2026-10', null));
    }

    public function test_reference_appends_control_digit_for_hr01(): void
    {
        $this->assertSame('1-2026107', BillingReference::for(1, '2026-10', 'HR01'));
    }
}
