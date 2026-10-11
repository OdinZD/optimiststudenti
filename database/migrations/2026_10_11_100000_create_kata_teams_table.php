<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kata_teams', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('color', 7)->default('#2D3E7B'); // #RRGGBB, used for the list marker
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kata_teams');
    }
};
