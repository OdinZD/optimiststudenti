<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\FeePlan;
use App\Models\Student;
use App\Models\StudentFeePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Faza U — migracije (nova polja) + backfill nacin_placanja na zaduženjima. */
final class AlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('club_billing_settings', 'invoice_line_template'));
        $this->assertTrue(Schema::hasColumn('club_billing_settings', 'reminder_days_after_due'));
        $this->assertTrue(Schema::hasColumn('club_billing_settings', 'eracuni_secret_key'));
        $this->assertTrue(Schema::hasColumn('student_fee_plans', 'nacin_placanja'));
        $this->assertTrue(Schema::hasColumn('student_fee_plans', 'payer_email'));
        $this->assertTrue(Schema::hasColumn('charges', 'nacin_placanja'));
        $this->assertTrue(Schema::hasColumn('charges', 'eracuni_invoice_number'));
        $this->assertTrue(Schema::hasColumn('charges', 'invoice_status'));
        $this->assertTrue(Schema::hasColumn('charges', 'last_sent_at'));
        $this->assertTrue(Schema::hasColumn('payments', 'received_by'));
    }

    public function test_invoice_status_defaults_to_none(): void
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        $charge = Charge::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-10', 'amount_cents' => 2500]);

        $this->assertSame('none', $charge->refresh()->invoice_status);
    }

    public function test_backfill_copies_nacin_placanja_from_assignment(): void
    {
        $plan = FeePlan::create(['name' => 'M', 'amount_cents' => 2500, 'type' => 'monthly', 'active' => true]);
        $student = Student::create(['last_name' => 'A', 'first_name' => 'B']);
        $sfp = StudentFeePlan::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'valid_from' => '2026-09-01', 'nacin_placanja' => 'gotovina']);

        $linked = Charge::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-10', 'amount_cents' => 2500, 'student_fee_plan_id' => $sfp->id]);
        $manual = Charge::create(['student_id' => $student->id, 'fee_plan_id' => $plan->id, 'period' => '2026-11', 'amount_cents' => 2500]); // bez dodjele

        // isti korelirani podupit kao u migraciji 2026_10_18_100002
        DB::statement('update charges set nacin_placanja = (select nacin_placanja from student_fee_plans where student_fee_plans.id = charges.student_fee_plan_id) where student_fee_plan_id is not null');

        $this->assertSame('gotovina', $linked->refresh()->nacin_placanja);
        $this->assertNull($manual->refresh()->nacin_placanja);
    }
}
