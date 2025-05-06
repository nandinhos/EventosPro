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
        Schema::create('gig_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers')->restrictOnDelete(); // Não deleta custo se centro for deletado

            $table->string('description')->nullable(); // Descrição específica
            $table->decimal('value', 12, 2);
            $table->string('currency', 10)->default('BRL');
            $table->date('expense_date')->nullable(); // Data da despesa

            $table->boolean('is_confirmed')->default(false)->index(); // Confirmado/Verificado?
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete(); // Quem confirmou
            $table->timestamp('confirmed_at')->nullable(); // Quando confirmou

            $table->text('notes')->nullable(); // Notas adicionais
            $table->timestamps();
            $table->softDeletes(); // Permitir exclusão lógica de despesas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gig_costs');
    }
};