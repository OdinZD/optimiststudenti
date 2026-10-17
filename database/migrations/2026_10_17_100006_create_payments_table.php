<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->integer('amount_cents');                      // signed: negativan za storno
            $table->date('paid_at');
            $table->string('method', 10);                         // bank | cash | other
            $table->string('source', 12)->default('manual');      // manual | bank_import | eracuni
            $table->string('bank_reference')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_payment_id')->nullable()->constrained('payments')->nullOnDelete(); // storno
            $table->timestamps();

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
