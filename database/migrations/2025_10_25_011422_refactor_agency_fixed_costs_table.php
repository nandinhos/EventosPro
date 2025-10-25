<?php

use App\Models\AgencyFixedCost;
use App\Models\CostCenter;
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
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->foreignId('cost_center_id')->nullable()->after('id')->constrained('cost_centers')->nullOnDelete();
        });

        // Data migration script
        $categoryMapping = [
            'administrative' => 'Administrativo',
            'operational' => 'Operacional',
            'marketing' => 'Marketing',
            'infrastructure' => 'Infraestrutura',
            'personnel' => 'Pessoal',
            'other' => 'Outros',
        ];

        foreach ($categoryMapping as $oldCategory => $newCostCenterName) {
            $costCenter = CostCenter::where('name', $newCostCenterName)->first();
            if ($costCenter) {
                AgencyFixedCost::where('category', $oldCategory)->update(['cost_center_id' => $costCenter->id]);
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

        // Reverse data migration
        $costCenters = CostCenter::all()->keyBy('id');
        $categoryMapping = [
            'Administrativo' => 'administrative',
            'Operacional' => 'operational',
            'Marketing' => 'marketing',
            'Infraestrutura' => 'infrastructure',
            'Pessoal' => 'personnel',
            'Outros' => 'other',
        ];

        $costs = AgencyFixedCost::whereNotNull('cost_center_id')->get();
        foreach ($costs as $cost) {
            $centerName = $costCenters->get($cost->cost_center_id)->name ?? 'Outros';
            $oldCategory = $categoryMapping[$centerName] ?? 'other';
            $cost->update(['category' => $oldCategory]);
        }

        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn('cost_center_id');
        });
    }
};
