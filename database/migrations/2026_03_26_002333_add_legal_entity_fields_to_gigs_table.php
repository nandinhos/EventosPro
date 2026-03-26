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
        Schema::table('gigs', function (Blueprint $table) {
            $table->unsignedBigInteger('legal_entity_id')->nullable()->after('id');
            $table->enum('contract_data_status', ['New', 'Legacy'])->default('New')->after('contract_status');

            $table->foreign('legal_entity_id')->references('id')->on('legal_entities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table) {
            $table->dropForeign(['legal_entity_id']);
            $table->dropColumn(['legal_entity_id', 'contract_data_status']);
        });
    }
};
