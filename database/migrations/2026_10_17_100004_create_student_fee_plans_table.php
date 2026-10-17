<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_plan_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('discount_percent')->nullable(); // 0–100; popust postotak ILI...
            $table->integer('discount_cents')->nullable();               // ...fiksni iznos u centima (ne oba)
            $table->date('valid_from');
            $table->date('valid_until')->nullable();                     // null = aktivno
            $table->string('payer_name', 150)->nullable();               // ime platitelja (default roditelj)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_plans');
    }
};
