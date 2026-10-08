<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    /**
     * @return list<array{0: ?string, 1: string}>
     */
    public static function examples(): array
    {
        return [
            ['0912345678', '091 234 5678'],
            ['091 234 5678', '091 234 5678'],
            ['098 177 7865', '098 177 7865'],
            ['+385 91 234 5678', '+385 91 234 5678'], // 12 digits → unchanged
            ['12345', '12345'],                        // too short → unchanged
            [null, ''],
        ];
    }

    #[DataProvider('examples')]
    public function test_format(?string $input, string $expected): void
    {
        $this->assertSame($expected, Phone::format($input));
    }
}
