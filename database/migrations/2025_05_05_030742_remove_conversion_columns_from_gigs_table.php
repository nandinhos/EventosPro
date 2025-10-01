<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove colunas de conversão e renomeia total_value para cache_value.
     */
    public function up(): void
    {
        // Verifica se a tabela 'gigs' existe antes de tentar modificá-la
        if (Schema::hasTable('gigs')) {
            Schema::table('gigs', function (Blueprint $table) {

                // Remover colunas de conversão (verificar se existem primeiro)
                if (Schema::hasColumn('gigs', 'exchange_rate')) {
                    $table->dropColumn('exchange_rate');
                }
                if (Schema::hasColumn('gigs', 'cache_value_brl')) {
                    $table->dropColumn('cache_value_brl');
                }
                // Remover também 'converted_total_value' se ela chegou a ser criada em algum momento
                if (Schema::hasColumn('gigs', 'converted_total_value')) {
                    $table->dropColumn('converted_total_value');
                }

                // Renomear 'total_value' para 'cache_value' (apenas se 'total_value' existir e 'cache_value' não)
                if (Schema::hasColumn('gigs', 'total_value') && ! Schema::hasColumn('gigs', 'cache_value')) {
                    $table->renameColumn('total_value', 'cache_value');
                } elseif (! Schema::hasColumn('gigs', 'cache_value') && Schema::hasColumn('gigs', 'cache_value_original')) {
                    // Fallback se você usou outro nome antes
                    $table->renameColumn('cache_value_original', 'cache_value');
                }

            });
        }
    }

    /**
     * Reverse the migrations.
     * Adiciona as colunas de volta e renomeia cache_value para total_value.
     */
    public function down(): void
    {
        if (Schema::hasTable('gigs')) {
            Schema::table('gigs', function (Blueprint $table) {

                // Renomear 'cache_value' de volta para 'total_value' (apenas se 'cache_value' existir e 'total_value' não)
                if (Schema::hasColumn('gigs', 'cache_value') && ! Schema::hasColumn('gigs', 'total_value')) {
                    $table->renameColumn('cache_value', 'total_value');
                }

                // Adicionar as colunas de volta (verificar se já não existem)
                if (! Schema::hasColumn('gigs', 'exchange_rate')) {
                    $table->decimal('exchange_rate', 10, 6)->nullable()->after('currency');
                }
                if (! Schema::hasColumn('gigs', 'cache_value_brl')) {
                    $table->decimal('cache_value_brl', 12, 2)->nullable()->after('exchange_rate'); // Adiciona após exchange_rate
                }
                if (! Schema::hasColumn('gigs', 'converted_total_value')) {
                    $table->decimal('converted_total_value', 12, 2)->nullable()->after('cache_value_brl');
                }

            });
        }
    }
};
