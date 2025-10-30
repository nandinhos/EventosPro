<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('id')->constrained('cost_centers')->nullOnDelete();
        });

        // Data migration script using DB facade to avoid SoftDeletes issues
        $categoryMapping = [
            'administrative' => 'Administrativo',
            'operational' => 'Operacional',
            'marketing' => 'Marketing',
            'infrastructure' => 'Infraestrutura',
            'personnel' => 'Pessoal',
            'other' => 'Outros',
        ];

        foreach ($categoryMapping as $oldCategory => $newCostCenterName) {
            $costCenter = DB::table('cost_centers')->where('name', $newCostCenterName)->first();
            if ($costCenter) {
                DB::table('agency_fixed_costs')
                    ->where('category', $oldCategory)
                    ->update(['cost_center_id' => $costCenter->id]);
            }
        }

        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->enum('category', ['administrative', 'operational', 'marketing', 'infrastructure', 'personnel', 'other'])
                ->default('other')
                ->after('monthly_value');
        });

        // Reverse data migration using DB facade
        $costCenters = DB::table('cost_centers')->get()->keyBy('id');
        $categoryMapping = [
            'Administrativo' => 'administrative',
            'Operacional' => 'operational',
            'Marketing' => 'marketing',
            'Infraestrutura' => 'infrastructure',
            'Pessoal' => 'personnel',
            'Outros' => 'other',
        ];

        $costs = DB::table('agency_fixed_costs')->whereNotNull('cost_center_id')->get();
        foreach ($costs as $cost) {
            $centerName = $costCenters->get($cost->cost_center_id)->name ?? 'Outros';
            $oldCategory = $categoryMapping[$centerName] ?? 'other';
            DB::table('agency_fixed_costs')
                ->where('id', $cost->id)
                ->update(['category' => $oldCategory]);
        }

        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn('cost_center_id');
        });
    }
};
