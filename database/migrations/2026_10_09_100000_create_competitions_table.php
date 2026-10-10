<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('held_on');
            $table->string('city', 100)->nullable();
            $table->timestamps();

            $table->index('held_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
