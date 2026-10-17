<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_billing_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('club_name')->nullable();            // naziv primatelja (klub)
            $table->string('address')->nullable();              // adresa primatelja
            $table->string('iban', 34)->nullable();
            $table->string('model', 7)->nullable();             // model poziva na broj, npr. HR00
            $table->string('reference_rule', 100)->nullable();  // pravilo za poziv na broj (TODO)
            $table->string('purpose_code', 4)->nullable();      // šifra namjene (TODO)
            $table->unsignedTinyInteger('due_day')->nullable(); // rok plaćanja — dan u mjesecu (TODO)
            $table->json('billing_months')->nullable();         // mjeseci naplate (TODO)
            $table->char('currency', 3)->default('EUR');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_billing_settings');
    }
};
