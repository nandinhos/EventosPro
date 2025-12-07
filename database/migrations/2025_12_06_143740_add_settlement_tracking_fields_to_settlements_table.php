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
        Schema::table('settlements', function (Blueprint $table) {
            // Estágio do fechamento (workflow)
            $table->string('settlement_stage', 30)
                ->default('aguardando_conferencia')
                ->after('gig_id')
                ->index();

            // Data de envio do fechamento ao artista
            $table->timestamp('settlement_sent_at')->nullable()->after('settlement_stage');

            // Data de recebimento da documentação (NF/Recibo)
            $table->timestamp('documentation_received_at')->nullable()->after('settlement_sent_at');

            // Tipo de documentação: 'nf' ou 'recibo'
            $table->string('documentation_type', 20)->nullable()->after('documentation_received_at');

            // Número da NF/Recibo (opcional - campo de texto)
            $table->string('documentation_number', 100)->nullable()->after('documentation_type');

            // Caminho do arquivo anexado (opcional - upload)
            $table->string('documentation_file_path')->nullable()->after('documentation_number');

            // Notas sobre comunicação com o artista
            $table->text('communication_notes')->nullable()->after('documentation_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropIndex(['settlement_stage']);
            $table->dropColumn([
                'settlement_stage',
                'settlement_sent_at',
                'documentation_received_at',
                'documentation_type',
                'documentation_number',
                'documentation_file_path',
                'communication_notes',
            ]);
        });
    }
};
