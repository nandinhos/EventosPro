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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // Chaves estrangeiras (tabelas bookers, artists, contracts já existem)
            $table->foreignId('booker_id')->constrained('bookers')->onDelete('cascade');
            $table->foreignId('main_artist_id')->constrained('artists')->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');

            $table->string('name')->nullable();
            $table->string('location_text'); // Localização (texto livre)
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('type', 100)->nullable();
            $table->string('status', 50)->default('planejado');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};