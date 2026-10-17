<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table): void {
            $table->string('nacin_placanja', 12)->nullable()->after('payer_name'); // racun_email | gotovina (NULL = nije postavljeno)
            $table->string('payer_email')->nullable()->after('nacin_placanja');     // obavezan kad je racun_email
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table): void {
            $table->dropColumn(['nacin_placanja', 'payer_email']);
        });
    }
};
