<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            // Campo: Data de Vencimento (CAIXA)
            $table->date('due_date')
                ->after('reference_month')
                ->nullable()
                ->comment('Data efetiva de pagamento (caixa)');

            // Campo: Tipo de Custo
            $table->enum('cost_type', ['GIG', 'AGENCY'])
                ->after('due_date')
                ->default('AGENCY')
                ->comment('GIG = operacional/eventos, AGENCY = administrativo');

            // Indexes para performance em consultas
            $table->index('due_date');
            $table->index('cost_type');
        });

        // Data-fix: Preencher registros existentes
        // due_date = reference_month (assume que pagamento ocorre no mês de competência)
        // cost_type = 'AGENCY' (todos os custos existentes são administrativos)
        DB::statement("
            UPDATE agency_fixed_costs
            SET due_date = reference_month,
                cost_type = 'AGENCY'
            WHERE due_date IS NULL
        ");

        // Tornar due_date obrigatório após data-fix
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->date('due_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            // Remover indexes
            $table->dropIndex(['due_date']);
            $table->dropIndex(['cost_type']);

            // Remover colunas
            $table->dropColumn(['due_date', 'cost_type']);
        });
    }
};
