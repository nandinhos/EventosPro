<?php

// Código completo da migration create_taggables_table (Item 9 da resposta anterior)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->morphs('taggable'); // Cria taggable_id (BIGINT UNSIGNED) e taggable_type (VARCHAR)
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']); // Chave primária composta
            // Índice para buscas por modelo (ex: buscar todas as tags de uma Gig específica)
            // $table->index(['taggable_id', 'taggable_type']); // O morphs() já pode criar isso, verificar documentação
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
