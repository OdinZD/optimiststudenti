<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Season;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class SeasonTest extends TestCase
{
    public function test_current_season_start_year(): void
    {
        $this->assertSame(2025, Season::current(CarbonImmutable::parse('2026-06-30'))); // Jun → previous Sep
        $this->assertSame(2025, Season::current(CarbonImmutable::parse('2026-08-31'))); // Aug (off-season) → previous
        $this->assertSame(2026, Season::current(CarbonImmutable::parse('2026-09-01'))); // Sep → same year
        $this->assertSame(2026, Season::current(CarbonImmutable::parse('2026-12-31')));
    }

    public function test_contains_boundaries(): void
    {
        $this->assertFalse(Season::contains(2025, CarbonImmutable::parse('2025-08-31'))); // before 1 Sep
        $this->assertTrue(Season::contains(2025, CarbonImmutable::parse('2025-09-01')));  // start, inclusive
        $this->assertTrue(Season::contains(2025, CarbonImmutable::parse('2026-06-30')));  // last day in season
        $this->assertFalse(Season::contains(2025, CarbonImmutable::parse('2026-07-01'))); // 1 Jul, exclusive
        $this->assertFalse(Season::contains(2025, CarbonImmutable::parse('2026-08-15'))); // off-season (summer)
    }

    public function test_label_and_slug(): void
    {
        $this->assertSame('2025/2026', Season::label(2025));
        $this->assertSame('2025-2026', Season::slug(2025));
    }
}
