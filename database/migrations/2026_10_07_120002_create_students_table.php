<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();

            // Osobni podaci
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->date('birth_date')->nullable();
            // OIB: 11 digits incl. ISO 7064 MOD 11,10 check digit. Many students have none.
            $table->char('oib', 11)->nullable()->unique();

            // Karate
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('training_group_id')->nullable()->constrained()->nullOnDelete();
            // null = not entered, 0 = no belt, 1–9 = kyu
            $table->tinyInteger('belt_kyu')->nullable();
            $table->string('next_grade_status', 20)->default('none'); // none / to_register / registered
            $table->tinyInteger('next_grade_kyu')->nullable();        // required 1–9 when status = registered

            // Liječnički pregled
            $table->date('medical_valid_until')->nullable();
            $table->boolean('has_no_medical')->default(false);

            // Roditelj / kontakt
            $table->string('parent_name', 150)->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->string('parent_email')->nullable();

            // Ostalo
            $table->text('note')->nullable();
            // Excel "t" marker; meaning still unknown (SPEC §10) — rename once clarified.
            $table->boolean('flag_t')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index('birth_date');
            $table->index('medical_valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
