<?php

// Código completo da migration create_activity_logs_table (Item 10 da resposta anterior)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject'); // Adiciona índice automaticamente (_subject_id_subject_type_index)
            $table->nullableMorphs('causer', 'causer');   // Adiciona índice automaticamente (_causer_id_causer_type_index)
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
