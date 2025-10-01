<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove a coluna antiga expenses_value_brl.
     */
    public function up(): void
    {
        // Verifica se a coluna existe antes de tentar remover
        if (Schema::hasColumn('gigs', 'expenses_value_brl')) {
            Schema::table('gigs', function (Blueprint $table) {
                $table->dropColumn('expenses_value_brl');
            });
        }
    }

    /**
     * Reverse the migrations.
     * Recria a coluna se a migration for revertida.
     */
    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table) {
            // Adiciona a coluna de volta com as características que ela tinha
            // Coloque depois da coluna 'cache_value_brl' para manter organização aproximada
            $table->decimal('expenses_value_brl', 12, 2)->default(0.00)->nullable()->after('cache_value_brl');
        });
    }
};
