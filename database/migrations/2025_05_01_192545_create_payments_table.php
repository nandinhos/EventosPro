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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Chave estrangeira (tabela contracts já existe)
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');

            $table->string('description', 255)->nullable();
            $table->decimal('value', 12, 2);
            $table->date('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->string('status', 50)->default('pendente');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};