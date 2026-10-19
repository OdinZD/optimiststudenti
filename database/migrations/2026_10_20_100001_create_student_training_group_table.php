<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dijete može biti u više grupa (isti uzorak kao kata_team_student).
        Schema::create('student_training_group', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_group_id')->constrained()->cascadeOnDelete();

            $table->unique(['student_id', 'training_group_id']);
        });

        // Postojeća jedna grupa po djetetu prelazi u vezu. students.training_group_id ostaje (add-only), samo se više ne uređuje.
        DB::statement('insert into student_training_group (student_id, training_group_id) select id, training_group_id from students where training_group_id is not null');
    }

    public function down(): void
    {
        Schema::dropIfExists('student_training_group');
    }
};
