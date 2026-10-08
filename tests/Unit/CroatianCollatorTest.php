<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\CroatianCollator;
use PHPUnit\Framework\TestCase;

final class CroatianCollatorTest extends TestCase
{
    public function test_sorts_in_croatian_alphabet(): void
    {
        $names = ['Ažić', 'Antišin', 'Anđić', 'Čović', 'Cvitan'];
        usort($names, fn (string $a, string $b): int => CroatianCollator::compare($a, $b));

        // č sorts after c; đ after d; ž near the end (SPEC §4.4).
        $this->assertSame(['Anđić', 'Antišin', 'Ažić', 'Cvitan', 'Čović'], $names);
    }

    public function test_returns_zero_for_equal_strings(): void
    {
        $this->assertSame(0, CroatianCollator::compare('Kardum', 'Kardum'));
    }
}
