<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AgeCategory;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AgeCategoryTest extends TestCase
{
    private const TODAY = '2026-10-07';

    /**
     * The exact examples from SPEC §4.1 (today = 2026-10-07).
     *
     * @return list<array{0: string, 1: int, 2: string, 3: ?string}>
     */
    public static function specExamples(): array
    {
        return [
            // birth_date,   age, label,                   crossover
            ['2020-10-08', 5,  'Ispod U8',            '2026-10-08'],
            ['2020-10-07', 6,  'U08 · Cicibani',      '2028-10-07'],
            ['2018-10-13', 7,  'U08 · Cicibani',      '2026-10-13'],
            ['2017-10-10', 8,  'U10 · Mlađi učenici', '2027-10-10'],
            ['2015-03-05', 11, 'U12 · Učenici',       '2027-03-05'],
            ['2013-12-30', 12, 'U14 · Mlađi kadeti',  '2027-12-30'],
            ['2011-05-07', 15, 'U16 · Kadeti',        '2027-05-07'],
            ['2008-02-29', 18, 'U21',                 '2029-02-28'],
            ['2003-06-19', 23, 'Seniori',             null],
        ];
    }

    #[DataProvider('specExamples')]
    public function test_category_and_crossover(string $birthDate, int $age, string $label, ?string $crossover): void
    {
        $category = AgeCategory::for(
            CarbonImmutable::parse($birthDate),
            CarbonImmutable::parse(self::TODAY),
        );

        $this->assertNotNull($category);
        $this->assertSame($age, $category->age);
        $this->assertSame($label, $category->label);
        $this->assertSame($crossover, $category->crossover?->format('Y-m-d'));
    }

    public function test_missing_birth_date_has_no_category(): void
    {
        $this->assertNull(AgeCategory::for(null, CarbonImmutable::parse(self::TODAY)));
    }

    /** A mistyped/ancient year (e.g. 0198) must resolve to Seniori, never throw. */
    public function test_extreme_age_resolves_to_seniori(): void
    {
        $category = AgeCategory::for(CarbonImmutable::parse('0198-11-25'), CarbonImmutable::parse(self::TODAY));

        $this->assertNotNull($category);
        $this->assertSame('sen', $category->key);
        $this->assertNull($category->crossover);
    }

    /** A far-future date (negative age, e.g. mid-typing) must not throw. */
    public function test_future_birth_date_does_not_throw(): void
    {
        $category = AgeCategory::for(CarbonImmutable::parse('9999-01-01'), CarbonImmutable::parse(self::TODAY));

        $this->assertNotNull($category);
    }

    private function noticeFor(string $birthDate): ?array
    {
        return AgeCategory::for(CarbonImmutable::parse($birthDate), CarbonImmutable::parse(self::TODAY))
            ?->crossoverWithin(CarbonImmutable::parse(self::TODAY), 6);
    }

    public function test_crossover_within_returns_months_days_and_next_label(): void
    {
        // born 2015-01-19 → U12; crosses to U14 on 2027-01-19 = 3 mj 12 d from 2026-10-07
        $notice = $this->noticeFor('2015-01-19');

        $this->assertNotNull($notice);
        $this->assertSame(3, $notice['months']);
        $this->assertSame(12, $notice['days']);
        $this->assertSame('U14 · Mlađi kadeti', $notice['nextLabel']);
        $this->assertSame('3 mj 12 d', $notice['remaining']);
    }

    public function test_crossover_exactly_at_window_is_included_without_zero_days(): void
    {
        // crosses exactly 6 months out (2027-04-07) → included, "6 mj"
        $notice = $this->noticeFor('2015-04-07');

        $this->assertNotNull($notice);
        $this->assertSame(6, $notice['months']);
        $this->assertSame(0, $notice['days']);
        $this->assertSame('6 mj', $notice['remaining']);
    }

    public function test_crossover_just_beyond_window_is_null(): void
    {
        // crosses one day past the 6-month window (2027-04-08) → no notice
        $this->assertNull($this->noticeFor('2015-04-08'));
    }

    public function test_crossover_within_days_only_drops_the_months_part(): void
    {
        // crosses in 10 days (2026-10-17) → "10 d", no "0 mj"
        $notice = $this->noticeFor('2014-10-17');

        $this->assertNotNull($notice);
        $this->assertSame(0, $notice['months']);
        $this->assertSame(10, $notice['days']);
        $this->assertSame('10 d', $notice['remaining']);
    }

    public function test_u21_crossover_targets_seniori(): void
    {
        // born 2006-01-19 → U21; crosses to Seniori on 2027-01-19
        $notice = $this->noticeFor('2006-01-19');

        $this->assertNotNull($notice);
        $this->assertSame('Seniori', $notice['nextLabel']);
    }

    public function test_seniori_has_no_crossover_notice(): void
    {
        $this->assertNull($this->noticeFor('2003-06-19'));
    }

    public function test_missing_birth_date_has_no_crossover_notice(): void
    {
        $this->assertNull(
            AgeCategory::for(null, CarbonImmutable::parse(self::TODAY))
                ?->crossoverWithin(CarbonImmutable::parse(self::TODAY), 6),
        );
    }
}
