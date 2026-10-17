<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('amount_cents');   // iznos u centima (EUR)
            $table->string('type', 10);        // monthly | yearly | one_time
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_plans');
    }
};
