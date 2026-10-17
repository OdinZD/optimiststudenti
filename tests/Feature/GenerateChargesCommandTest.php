<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class GenerateChargesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.payments' => true]); // komanda provjerava flag u runtime-u
    }

    private function monthly(string $from): void
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        StudentFeePlan::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'valid_from' => $from]);
    }

    public function test_generates_for_a_given_period(): void
    {
        $this->monthly('2026-09-01');

        $this->artisan('clanarine:zaduzi', ['period' => '2026-10'])
            ->expectsOutputToContain('stvoreno: 1')
            ->assertSuccessful();

        $this->assertSame(1, Charge::where('period', '2026-10')->count());
    }

    public function test_defaults_to_current_month(): void
    {
        Carbon::setTestNow('2026-10-05');
        $this->monthly('2026-09-01');

        $this->artisan('clanarine:zaduzi')->assertSuccessful();

        $this->assertSame(1, Charge::where('period', '2026-10')->count());
        Carbon::setTestNow();
    }

    public function test_rejects_an_invalid_period(): void
    {
        $this->artisan('clanarine:zaduzi', ['period' => '2026/10'])->assertFailed();
    }

    public function test_does_nothing_when_the_flag_is_off(): void
    {
        config(['features.payments' => false]);
        $this->monthly('2026-09-01');

        $this->artisan('clanarine:zaduzi', ['period' => '2026-10'])->assertSuccessful();

        $this->assertSame(0, Charge::count());
    }
}
