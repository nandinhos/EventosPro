<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->foreignId('booker_id')->nullable()->constrained('bookers')->nullOnDelete();

            $table->string('contract_number', 100)->nullable();
            $table->date('contract_date')->nullable();
            $table->date('gig_date')->index();
            $table->text('location_event_details');

            $table->decimal('cache_value', 12, 2);
            $table->string('currency', 10)->default('BRL');
            $table->decimal('exchange_rate', 10, 6)->nullable();
            $table->decimal('cache_value_brl', 12, 2);
            $table->decimal('expenses_value_brl', 12, 2)->default(0.00)->nullable(); // Mantem nullable

            $table->string('agency_commission_type', 10)->nullable()->default('percent');
            $table->decimal('agency_commission_rate', 5, 2)->nullable()->default(20.00);
            $table->decimal('agency_commission_value', 12, 2)->nullable();
            $table->string('booker_commission_type', 10)->nullable()->default('percent');
            $table->decimal('booker_commission_rate', 5, 2)->nullable()->default(5.00);
            $table->decimal('booker_commission_value', 12, 2)->nullable();
            $table->decimal('liquid_commission_value', 12, 2)->nullable();

            // --- Adicionar esta linha ---
            $table->string('contract_status', 50)->default('n/a')->index(); // Status do CONTRATO FORMAL

            $table->string('payment_status', 50)->default('a_vencer')->index();
            $table->string('artist_payment_status', 50)->default('pendente')->index();
            $table->string('booker_payment_status', 50)->default('pendente')->index();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
