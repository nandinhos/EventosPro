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
        Schema::table('bookers', function (Blueprint $table) {
            $table->text('contact_info')->nullable()->after('default_commission_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookers', function (Blueprint $table) {
            $table->dropColumn('contact_info');
        });
    }
};
