<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Student;
use App\Support\MedicalReminders;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Medical-clearance reminder list (today frozen to 2026-10-11).
 */
final class MedicalRemindersTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->today = CarbonImmutable::parse('2026-10-11');
        CarbonImmutable::setTestNow($this->today);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_selects_only_reminder_statuses_in_urgency_order(): void
    {
        // Names are deliberately non-alphabetical so the urgency sort is what we test.
        Student::create(['last_name' => 'Zubak', 'first_name' => 'A', 'medical_valid_until' => '2026-10-01']); // expired 10d
        Student::create(['last_name' => 'Babić', 'first_name' => 'B', 'medical_valid_until' => '2026-10-06']); // expired 5d
        Student::create(['last_name' => 'Matić', 'first_name' => 'C', 'medical_valid_until' => '2026-10-11']); // soon, danas (0d)
        Student::create(['last_name' => 'Anić', 'first_name' => 'D', 'medical_valid_until' => '2026-10-23']);  // soon 12d
        Student::create(['last_name' => 'Perić', 'first_name' => 'E', 'medical_valid_until' => '2026-11-10']); // soon 30d (boundary, included)
        Student::create(['last_name' => 'Kovač', 'first_name' => 'F']);                                        // unknown (no date)
        Student::create(['last_name' => 'Horvat', 'first_name' => 'G', 'medical_valid_until' => '2026-11-11']); // valid 31d → excluded
        Student::create(['last_name' => 'Novak', 'first_name' => 'H', 'has_no_medical' => true]);               // none → excluded

        $export = MedicalReminders::build(Student::all(), $this->today);

        $this->assertSame(
            ['Prezime', 'Ime', 'Lokacija', 'Vrijedi do', 'Status', 'Rok', 'Roditelj', 'Telefon'],
            $export['headers'],
        );

        $order = array_column($export['rows'], 0);
        $this->assertSame(
            ['Zubak', 'Babić', 'Matić', 'Anić', 'Perić', 'Kovač'],
            $order,
            'Expired (most overdue first) → Soon (soonest first) → Unknown; Valid + None excluded.',
        );
    }

    public function test_row_carries_contact_date_status_and_rok(): void
    {
        $location = Location::create(['slug' => 'os-stanovi', 'name' => 'OŠ Stanovi']);

        Student::create([
            'last_name' => 'Babić',
            'first_name' => 'Marko',
            'location_id' => $location->id,
            'medical_valid_until' => '2026-10-06', // expired 5 days
            'parent_name' => 'Ana Babić',
            'parent_phone' => '0912345678',
        ]);

        $export = MedicalReminders::build(Student::all(), $this->today);

        $this->assertSame(
            ['Babić', 'Marko', 'OŠ Stanovi', '06.10.2026.', 'Istekao', 'istekao prije 5 dana', 'Ana Babić', '091 234 5678'],
            $export['rows'][0],
        );
    }

    public function test_rok_text_variants_and_day_boundaries(): void
    {
        Student::create(['last_name' => 'Istekaojedan', 'first_name' => 'x', 'medical_valid_until' => '2026-10-10']); // -1 → "istekao prije 1 dan"
        Student::create(['last_name' => 'Danas', 'first_name' => 'x', 'medical_valid_until' => '2026-10-11']);         // 0  → "ističe danas"
        Student::create(['last_name' => 'Jedan', 'first_name' => 'x', 'medical_valid_until' => '2026-10-12']);         // 1  → "ističe za 1 dan"
        Student::create(['last_name' => 'Jedanaest', 'first_name' => 'x', 'medical_valid_until' => '2026-10-22']);     // 11 → "ističe za 11 dana" (%100==11)
        Student::create(['last_name' => 'Dvadesetjedan', 'first_name' => 'x', 'medical_valid_until' => '2026-11-01']); // 21 → "ističe za 21 dan"
        Student::create(['last_name' => 'Nepoznati', 'first_name' => 'x']);                                            // unknown → "nema upisano"

        $rok = collect(MedicalReminders::build(Student::all(), $this->today)['rows'])
            ->mapWithKeys(fn (array $row): array => [$row[0] => $row[5]]);

        $this->assertSame('istekao prije 1 dan', $rok['Istekaojedan']);
        $this->assertSame('ističe danas', $rok['Danas']);
        $this->assertSame('ističe za 1 dan', $rok['Jedan']);
        $this->assertSame('ističe za 11 dana', $rok['Jedanaest']);
        $this->assertSame('ističe za 21 dan', $rok['Dvadesetjedan']);
        $this->assertSame('nema upisano', $rok['Nepoznati']);
    }
}
