<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            // Chave estrangeira (tabela events já existe)
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');

            $table->date('settlement_date');
            $table->decimal('artist_net_amount', 12, 2)->nullable();
            $table->decimal('agency_commission', 12, 2)->nullable();
            $table->decimal('booker_commission', 12, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};