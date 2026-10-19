<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Termin = lokacija + dani + vrijeme (npr. "OŠ Stanovi · Uto/Čet 20–21 h"). Referentni podaci kao lokacije.
        Schema::create('training_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('days', 30);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Koje grupe treniraju u kojem terminu. Dijetetovi termini se izvode iz njegovih grupa.
        Schema::create('group_training_slot', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_slot_id')->constrained()->cascadeOnDelete();

            $table->unique(['training_group_id', 'training_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_training_slot');
        Schema::dropIfExists('training_slots');
    }
};
