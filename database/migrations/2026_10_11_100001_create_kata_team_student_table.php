<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kata_team_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kata_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->unique(['kata_team_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kata_team_student');
    }
};
