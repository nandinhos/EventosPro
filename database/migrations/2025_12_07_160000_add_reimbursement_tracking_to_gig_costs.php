<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona campos para rastreamento de comprovantes de despesas reembolsáveis.
     */
    public function up(): void
    {
        Schema::table('gig_costs', function (Blueprint $table) {
            // Estágio do workflow de reembolso
            // aguardando_comprovante, comprovante_recebido, conferido, reembolsado
            $table->string('reimbursement_stage', 50)->nullable()->after('is_invoice');
            
            // Tipo de comprovante (recibo, nf, transferencia, outro)
            $table->string('reimbursement_proof_type', 30)->nullable()->after('reimbursement_stage');
            
            // Arquivo do comprovante
            $table->string('reimbursement_proof_file', 500)->nullable()->after('reimbursement_proof_type');
            
            // Data de recebimento do comprovante
            $table->timestamp('reimbursement_proof_received_at')->nullable()->after('reimbursement_proof_file');
            
            // Valor conferido (pode diferir do valor original)
            $table->decimal('reimbursement_value_confirmed', 12, 2)->nullable()->after('reimbursement_proof_received_at');
            
            // Conferência
            $table->timestamp('reimbursement_confirmed_at')->nullable()->after('reimbursement_value_confirmed');
            $table->foreignId('reimbursement_confirmed_by')->nullable()->after('reimbursement_confirmed_at')
                ->constrained('users')->nullOnDelete();
            
            // Notas do reembolso
            $table->text('reimbursement_notes')->nullable()->after('reimbursement_confirmed_by');
            
            // Índice para filtros por estágio
            $table->index('reimbursement_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gig_costs', function (Blueprint $table) {
            $table->dropForeign(['reimbursement_confirmed_by']);
            $table->dropIndex(['reimbursement_stage']);
            
            $table->dropColumn([
                'reimbursement_stage',
                'reimbursement_proof_type',
                'reimbursement_proof_file',
                'reimbursement_proof_received_at',
                'reimbursement_value_confirmed',
                'reimbursement_confirmed_at',
                'reimbursement_confirmed_by',
                'reimbursement_notes',
            ]);
        });
    }
};
