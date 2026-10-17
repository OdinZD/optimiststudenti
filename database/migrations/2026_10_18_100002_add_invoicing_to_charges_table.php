<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table): void {
            $table->string('nacin_placanja', 12)->nullable()->after('student_fee_plan_id'); // kopija s dodjele
            $table->string('eracuni_invoice_number')->nullable()->after('eracuni_invoice_id');
            $table->string('invoice_status', 10)->default('none')->after('eracuni_invoice_number'); // none|queued|issued|failed
            $table->timestamp('last_sent_at')->nullable()->after('invoice_status');
        });

        // Backfill nacin_placanja iz dodjele (portabilno — korelirani podupit; radi na sqlite i MariaDB).
        // invoice_status postojećima = 'none' (default stupca pokriva postojeće retke).
        DB::statement('update charges set nacin_placanja = (select nacin_placanja from student_fee_plans where student_fee_plans.id = charges.student_fee_plan_id) where student_fee_plan_id is not null');
    }

    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table): void {
            $table->dropColumn(['nacin_placanja', 'eracuni_invoice_number', 'invoice_status', 'last_sent_at']);
        });
    }
};
