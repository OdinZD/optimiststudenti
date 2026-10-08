<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Diacritics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiacriticsTest extends TestCase
{
    /**
     * @return list<array{0: ?string, 1: string}>
     */
    public static function examples(): array
    {
        return [
            ['Čulina', 'culina'],
            ['ĆIRIL', 'ciril'],
            ['Žarko Šojat', 'zarko sojat'],
            ['Đakovo', 'dakovo'],
            ['KARDUM', 'kardum'],
            ['Kržić', 'krzic'],
            [null, ''],
            ['', ''],
        ];
    }

    #[DataProvider('examples')]
    public function test_normalize(?string $input, string $expected): void
    {
        $this->assertSame($expected, Diacritics::normalize($input));
    }

    public function test_query_matches_normalized_haystack(): void
    {
        $haystack = Diacritics::normalize('Kardum Karmen OŠ Stanovi');

        $this->assertStringContainsString(Diacritics::normalize('kardum'), $haystack);
    }
}
