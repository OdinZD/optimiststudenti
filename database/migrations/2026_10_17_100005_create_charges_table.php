<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained();              // bez cascade: djeca su soft-delete, zapis ostaje
            $table->foreignId('fee_plan_id')->constrained();
            $table->foreignId('student_fee_plan_id')->nullable()->constrained()->nullOnDelete(); // dodjela (null = ručno)
            $table->string('period', 7);                                 // 'YYYY-MM'
            $table->integer('amount_cents');                             // već s popustom
            $table->date('due_date')->nullable();
            $table->string('reference', 40)->nullable();                 // poziv na broj
            $table->timestamp('written_off_at')->nullable();
            $table->foreignId('written_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('written_off_reason')->nullable();
            $table->string('eracuni_invoice_id')->nullable();            // za kasniju integraciju s eRačunima
            $table->timestamps();

            $table->unique(['student_id', 'fee_plan_id', 'period']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
