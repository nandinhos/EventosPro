<?php
// Código completo da migration create_settlements_table (Item 7 da resposta anterior, ajustado FK para gigs)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete(); // FK para a nova tabela gigs
            $table->date('settlement_date')->index();
            $table->string('artist_payment_proof')->nullable();
            $table->string('booker_commission_proof')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};