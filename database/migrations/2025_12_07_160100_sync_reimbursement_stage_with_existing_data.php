<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Inicializa o estágio de reembolso para despesas já marcadas como is_invoice.
     */
    public function up(): void
    {
        // Não executar em ambiente de testes
        if (app()->environment('testing')) {
            return;
        }
        
        // Despesas reembolsáveis (is_invoice = true) de gigs já pagas ao artista = reembolsado
        $paidCount = DB::table('gig_costs')
            ->join('gigs', 'gig_costs.gig_id', '=', 'gigs.id')
            ->where('gig_costs.is_invoice', true)
            ->where('gigs.artist_payment_status', 'pago')
            ->whereNull('gig_costs.deleted_at')
            ->whereNull('gig_costs.reimbursement_stage')
            ->update([
                'gig_costs.reimbursement_stage' => 'reembolsado',
                'gig_costs.updated_at' => now(),
            ]);
        
        // Despesas reembolsáveis de gigs pendentes = aguardando_comprovante
        $pendingCount = DB::table('gig_costs')
            ->join('gigs', 'gig_costs.gig_id', '=', 'gigs.id')
            ->where('gig_costs.is_invoice', true)
            ->where('gigs.artist_payment_status', '!=', 'pago')
            ->whereNull('gig_costs.deleted_at')
            ->whereNull('gig_costs.reimbursement_stage')
            ->update([
                'gig_costs.reimbursement_stage' => 'aguardando_comprovante',
                'gig_costs.updated_at' => now(),
            ]);
        
        // Log para debug
        if ($paidCount > 0 || $pendingCount > 0) {
            \Illuminate\Support\Facades\Log::info(
                "[Migration] Reimbursement stages initialized: {$paidCount} marked as 'reembolsado', {$pendingCount} marked as 'aguardando_comprovante'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Limpar os estágios
        DB::table('gig_costs')
            ->whereNotNull('reimbursement_stage')
            ->update([
                'reimbursement_stage' => null,
                'updated_at' => now(),
            ]);
    }
};
