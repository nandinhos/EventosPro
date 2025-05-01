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
        Schema::create('event_artist', function (Blueprint $table) {
            // Chaves estrangeiras (tabelas events e artists já existem neste ponto da execução)
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('artist_id')->constrained('artists')->onDelete('cascade');

            // Chave primária composta
            $table->primary(['event_id', 'artist_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_artist');
    }
};