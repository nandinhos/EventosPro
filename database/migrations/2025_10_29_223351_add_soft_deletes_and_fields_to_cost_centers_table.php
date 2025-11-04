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
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index()->after('description');
            $table->string('color', 7)->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'color']);
            $table->dropSoftDeletes();
        });
    }
};
