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
        Schema::table('service_takers', function (Blueprint $table) {
            $table->string('state_registration')->nullable()->after('document'); // Inscrição Estadual (IE)
            $table->string('municipal_registration')->nullable()->after('state_registration'); // Inscrição Municipal (IM)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_takers', function (Blueprint $table) {
            $table->dropColumn(['state_registration', 'municipal_registration']);
        });
    }
};
