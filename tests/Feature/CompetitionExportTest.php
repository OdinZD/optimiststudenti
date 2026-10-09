<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Support\CompetitionExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompetitionExportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_build_returns_essentials_without_oib_or_medical(): void
    {
        CarbonImmutable::setTestNow('2026-10-07');

        $student = Student::create([
            'last_name' => 'Antišin',
            'first_name' => 'Tonka',
            'birth_date' => '2016-07-15',
            'oib' => '20396411832',
            'belt_kyu' => 7,
            'medical_valid_until' => '2027-01-12',
        ]);

        $export = CompetitionExport::build(collect([$student]), CarbonImmutable::now());

        $this->assertSame(
            ['Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija', 'Pojas'],
            $export['headers'],
        );
        $this->assertSame(
            ['Antišin', 'Tonka', '15.07.2016.', 'U12 · Učenici', '7. kyu · narančasti'],
            $export['rows'][0],
        );

        // OIB and medical data must not leak into the roster.
        $serialized = json_encode($export, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('20396411832', $serialized);
        $this->assertStringNotContainsString('OIB', $serialized);
        $this->assertStringNotContainsString('Liječnički', $serialized);
    }

    public function test_handles_missing_birth_date_and_belt(): void
    {
        $student = Student::create(['last_name' => 'Baždarić', 'first_name' => 'Niko']);

        $export = CompetitionExport::build(collect([$student]), CarbonImmutable::parse('2026-10-07'));

        $this->assertSame(['Baždarić', 'Niko', '', 'Bez datuma rođenja', 'Nije uneseno'], $export['rows'][0]);
    }
}
