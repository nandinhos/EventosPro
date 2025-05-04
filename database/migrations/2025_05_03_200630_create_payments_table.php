<?php
// Código completo da migration create_payments_table (Item 6 da resposta anterior, ajustado FK para gigs)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete(); // FK para a nova tabela gigs
            $table->decimal('received_value', 12, 2);
            $table->date('received_date')->nullable();
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('currency', 10)->default('BRL');
            $table->decimal('exchange_rate', 10, 6)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('pendente')->index(); // Status do pagamento
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};