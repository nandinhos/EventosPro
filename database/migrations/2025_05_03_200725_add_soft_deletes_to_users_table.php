<?php
// Código completo da migration add_soft_deletes_to_users_table (Item 11 da resposta anterior)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deleted_at')) { // Verifica se a coluna já não existe
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             if (Schema::hasColumn('users', 'deleted_at')) { // Verifica se a coluna existe antes de remover
                $table->dropSoftDeletes();
            }
        });
    }
};