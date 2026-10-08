<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MedicalStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MedicalStatusTest extends TestCase
{
    private const TODAY = '2026-10-07';

    /**
     * The exact examples from SPEC §4.2 (today = 2026-10-07).
     *
     * @return list<array{0: string, 1: MedicalStatus}>
     */
    public static function specExamples(): array
    {
        return [
            ['2026-10-06', MedicalStatus::Expired],       // day before today
            ['2026-10-07', MedicalStatus::SoonExpiring],  // today (inclusive)
            ['2026-11-06', MedicalStatus::SoonExpiring],  // +30 days (spans DST change)
            ['2026-11-07', MedicalStatus::Valid],         // +31 days
            ['2026-07-14', MedicalStatus::Expired],
        ];
    }

    #[DataProvider('specExamples')]
    public function test_status_from_valid_until(string $validUntil, MedicalStatus $expected): void
    {
        $status = MedicalStatus::for(
            CarbonImmutable::parse($validUntil),
            hasNoMedical: false,
            today: CarbonImmutable::parse(self::TODAY),
        );

        $this->assertSame($expected, $status);
    }

    public function test_no_medical_flag_wins_over_date(): void
    {
        $status = MedicalStatus::for(
            CarbonImmutable::parse('2027-01-01'),
            hasNoMedical: true,
            today: CarbonImmutable::parse(self::TODAY),
        );

        $this->assertSame(MedicalStatus::None, $status);
    }

    public function test_null_date_is_unknown(): void
    {
        $status = MedicalStatus::for(null, false, CarbonImmutable::parse(self::TODAY));

        $this->assertSame(MedicalStatus::Unknown, $status);
    }
}
