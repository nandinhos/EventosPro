<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona índices compostos para otimizar queries frequentes em relatórios financeiros.
     */
    public function up(): void
    {
        // Índice composto para queries de gigs por data e status de pagamento do artista
        // Usado em: relatórios financeiros, dashboard, listagens de eventos
        Schema::table('gigs', function (Blueprint $table) {
            $table->index(['gig_date', 'artist_payment_status'], 'idx_gigs_date_payment_status');
        });

        // Índice composto para queries de pagamentos por status e data de vencimento
        // Usado em: relatório de vencimentos, dashboard de contas a receber
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['due_date', 'confirmed_at'], 'idx_payments_due_date_confirmed');
        });

        // Índice composto para queries de despesas por gig e confirmação
        // Usado em: cálculos financeiros, relatórios de despesas
        Schema::table('gig_costs', function (Blueprint $table) {
            $table->index(['gig_id', 'is_confirmed'], 'idx_gig_costs_gig_confirmed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table) {
            $table->dropIndex('idx_gigs_date_payment_status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_due_date_confirmed');
        });

        Schema::table('gig_costs', function (Blueprint $table) {
            $table->dropIndex('idx_gig_costs_gig_confirmed');
        });
    }
};
