<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only trag svih eRačuni poziva (bez vjerodajnica). Samo created_at.
        Schema::create('eracuni_api_log', function (Blueprint $table): void {
            $table->id();
            $table->string('method', 60);
            $table->boolean('ok')->default(false);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request')->nullable();  // bez username/secretKey/token
            $table->text('response')->nullable();  // skraćeno
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('method');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eracuni_api_log');
    }
};
