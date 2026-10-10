<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('discipline', 10);              // kate | kumite
            $table->string('category', 60)->nullable();    // npr. "U12 -40kg"
            $table->tinyInteger('place')->nullable();      // plasman; medalja se računa iz plasmana
            $table->tinyInteger('wins')->nullable();       // pobjede
            $table->tinyInteger('losses')->nullable();     // porazi
            $table->tinyInteger('bouts')->nullable();      // ukupan broj borbi
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('competition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_results');
    }
};
