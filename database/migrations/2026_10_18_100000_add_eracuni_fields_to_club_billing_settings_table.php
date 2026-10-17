<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_billing_settings', function (Blueprint $table): void {
            $table->text('invoice_line_template')->nullable();          // npr. "Članarina {mjesec} {godina} – {ime djeteta}"
            $table->unsignedSmallInteger('reminder_days_after_due')->nullable();

            // eRačuni pristupni podaci — šifrirani (encrypted cast), nikad plain u bazi.
            $table->text('eracuni_username')->nullable();
            $table->text('eracuni_secret_key')->nullable();
            $table->text('eracuni_token')->nullable();
            $table->text('eracuni_endpoint')->nullable();
            $table->string('eracuni_environment', 12)->nullable();      // test | production
        });
    }

    public function down(): void
    {
        Schema::table('club_billing_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_line_template', 'reminder_days_after_due',
                'eracuni_username', 'eracuni_secret_key', 'eracuni_token', 'eracuni_endpoint', 'eracuni_environment',
            ]);
        });
    }
};
