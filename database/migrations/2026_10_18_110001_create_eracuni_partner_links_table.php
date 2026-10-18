<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Veza platitelja (roditelja) s partnerom u eRačunima. Jedan link po e-mailu.
        Schema::create('eracuni_partner_links', function (Blueprint $table): void {
            $table->id();
            $table->string('payer_email')->unique();
            $table->string('payer_name')->nullable();
            $table->string('oib', 20)->nullable(); // OIB roditelja — zasad se ne puni
            $table->string('eracuni_partner_id');
            $table->string('eracuni_partner_name')->nullable();
            $table->string('linked_by')->nullable(); // ime korisnika koji je potvrdio vezu
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eracuni_partner_links');
    }
};
