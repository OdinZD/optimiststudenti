<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_roster_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_roster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('discipline', 10)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('confirmed')->default(false);
            $table->timestamps();

            // Not unique on purpose: (roster, student, discipline) uniqueness is enforced in the app,
            // because MariaDB/sqlite treat NULL disciplines as distinct from each other.
            $table->index(['competition_roster_id', 'student_id'], 'roster_entries_roster_student_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_roster_entries');
    }
};
