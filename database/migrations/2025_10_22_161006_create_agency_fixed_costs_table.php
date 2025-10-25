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
        Schema::create('agency_fixed_costs', function (Blueprint $table) {
            $table->id();
            $table->string('description')->comment('Descrição do custo fixo');
            $table->decimal('monthly_value', 15, 2)->comment('Valor mensal do custo fixo em BRL');
            $table->date('reference_month')->comment('Mês de referência (YYYY-MM-01)');
            $table->enum('category', ['administrative', 'operational', 'marketing', 'infrastructure', 'personnel', 'other'])
                ->default('other')
                ->comment('Categoria do custo fixo');
            $table->text('notes')->nullable()->comment('Observações adicionais');
            $table->boolean('is_active')->default(true)->comment('Se o custo está ativo');
            $table->timestamps();
            $table->softDeletes();

            // Índices para performance
            $table->index('reference_month');
            $table->index('is_active');
            $table->index(['reference_month', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_fixed_costs');
    }
};
