<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\Oib;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OibTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: bool}>
     */
    public static function examples(): array
    {
        return [
            // Valid OIBs (from the normalized dataset / public examples).
            ['20396411832', true],
            ['69435151530', true],  // Croatian Tax Administration published example
            // Wrong check digit.
            ['20396411833', false],
            // Wrong length / non-digits.
            ['1234567890', false],
            ['123456789012', false],
            ['2039641183a', false],
            ['', false],
        ];
    }

    #[DataProvider('examples')]
    public function test_check_digit(string $oib, bool $valid): void
    {
        $this->assertSame($valid, Oib::hasValidCheckDigit($oib));
    }
}
