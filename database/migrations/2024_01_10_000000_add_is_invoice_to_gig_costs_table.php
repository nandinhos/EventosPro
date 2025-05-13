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
        Schema::table('gig_costs', function (Blueprint $table) {
            $table->boolean('is_invoice')->default(false)->after('is_confirmed')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gig_costs', function (Blueprint $table) {
            $table->dropColumn('is_invoice');
        });
    }
};