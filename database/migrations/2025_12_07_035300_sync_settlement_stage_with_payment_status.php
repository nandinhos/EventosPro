<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Sincroniza settlement_stage com artist_payment_status.
     * 
     * Regras:
     * 1. Gig paga + Settlement existe com stage errado → Atualiza stage para 'pago'
     * 2. Gig paga + SEM settlement → Cria settlement como 'pago'
     */
    public function up(): void
    {
        // Não rodar durante testes (ambiente testing usa banco vazio)
        if (app()->environment('testing')) {
            return;
        }

        Log::info('Iniciando migração de sincronização de settlement_stage...');

        // 1. Atualizar settlements existentes onde gig está paga
        $updated = DB::table('settlements')
            ->join('gigs', 'settlements.gig_id', '=', 'gigs.id')
            ->where('gigs.artist_payment_status', 'pago')
            ->where('gigs.deleted_at', null)
            ->where('settlements.deleted_at', null)
            ->where('settlements.settlement_stage', '!=', 'pago')
            ->update([
                'settlements.settlement_stage' => 'pago',
                'settlements.settlement_sent_at' => DB::raw('COALESCE(settlements.settlement_sent_at, NOW())'),
                'settlements.documentation_received_at' => DB::raw('COALESCE(settlements.documentation_received_at, NOW())'),
                'settlements.updated_at' => now(),
            ]);

        Log::info("Atualizados {$updated} settlements para stage 'pago'");

        // 2. Criar settlements para gigs pagas que não têm settlement
        $gigsWithoutSettlement = DB::table('gigs')
            ->leftJoin('settlements', 'gigs.id', '=', 'settlements.gig_id')
            ->where('gigs.artist_payment_status', 'pago')
            ->where('gigs.deleted_at', null)
            ->whereNull('settlements.id')
            ->select('gigs.id', 'gigs.gig_date')
            ->get();

        $created = 0;
        foreach ($gigsWithoutSettlement as $gig) {
            DB::table('settlements')->insert([
                'gig_id' => $gig->id,
                'settlement_stage' => 'pago',
                'settlement_date' => $gig->gig_date,
                'settlement_sent_at' => now(),
                'documentation_received_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        Log::info("Criados {$created} novos settlements para gigs pagas");

        Log::info('Migração concluída com sucesso!');
    }

    /**
     * Reverter a migração.
     * Volta todos os settlements de gigs pagas para aguardando_conferencia.
     */
    public function down(): void
    {
        // Reverte para o estado anterior (aguardando_conferencia)
        DB::table('settlements')
            ->join('gigs', 'settlements.gig_id', '=', 'gigs.id')
            ->where('gigs.artist_payment_status', 'pago')
            ->where('gigs.deleted_at', null)
            ->where('settlements.deleted_at', null)
            ->update([
                'settlements.settlement_stage' => 'aguardando_conferencia',
                'settlements.updated_at' => now(),
            ]);
    }
};
