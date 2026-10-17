<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_format_uses_croatian_thousands_and_decimal_separators(): void
    {
        $this->assertSame('0,00 €', Money::format(0));
        $this->assertSame('12,50 €', Money::format(1250));
        $this->assertSame('1.234,50 €', Money::format(123450));
    }

    public function test_parse_accepts_croatian_and_plain_dot_forms(): void
    {
        $this->assertSame(0, Money::parse(''));
        $this->assertSame(1250, Money::parse('12,50'));
        $this->assertSame(1250, Money::parse('12.50'));
        $this->assertSame(123450, Money::parse('1.234,50'));
        $this->assertSame(2500, Money::parse('25'));
        $this->assertSame(1000, Money::parse('€ 10,00'));
    }

    public function test_parse_rounds_to_the_cent(): void
    {
        $this->assertSame(1235, Money::parse('12,345')); // 1234.5 → 1235
    }
}
