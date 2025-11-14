<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Use ENUM with MODIFY COLUMN
            // 1. Temporarily expand the ENUM to include both old and new values
            DB::statement("ALTER TABLE agency_fixed_costs MODIFY COLUMN cost_type ENUM('GIG', 'AGENCY', 'operacional', 'administrativo')");

            // 2. Update the data from old values to new values
            DB::table('agency_fixed_costs')->where('cost_type', 'GIG')->update(['cost_type' => 'operacional']);
            DB::table('agency_fixed_costs')->where('cost_type', 'AGENCY')->update(['cost_type' => 'administrativo']);

            // 3. Change the ENUM to only include the new values and set the new default
            DB::statement("ALTER TABLE agency_fixed_costs MODIFY COLUMN cost_type ENUM('operacional', 'administrativo') NOT NULL DEFAULT 'administrativo'");
        } else {
            // SQLite and others: ENUM is stored as string, just update data
            // No schema change needed for SQLite (already string column)
            DB::table('agency_fixed_costs')->where('cost_type', 'GIG')->update(['cost_type' => 'operacional']);
            DB::table('agency_fixed_costs')->where('cost_type', 'AGENCY')->update(['cost_type' => 'administrativo']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Use ENUM with MODIFY COLUMN
            // 1. Temporarily expand the ENUM to include both old and new values
            DB::statement("ALTER TABLE agency_fixed_costs MODIFY COLUMN cost_type ENUM('GIG', 'AGENCY', 'operacional', 'administrativo')");

            // 2. Update the data from new values back to old values
            DB::table('agency_fixed_costs')->where('cost_type', 'operacional')->update(['cost_type' => 'GIG']);
            DB::table('agency_fixed_costs')->where('cost_type', 'administrativo')->update(['cost_type' => 'AGENCY']);

            // 3. Change the ENUM to only include the old values and set the old default
            DB::statement("ALTER TABLE agency_fixed_costs MODIFY COLUMN cost_type ENUM('GIG', 'AGENCY') NOT NULL DEFAULT 'AGENCY'");
        } else {
            // SQLite and others: Just revert data
            DB::table('agency_fixed_costs')->where('cost_type', 'operacional')->update(['cost_type' => 'GIG']);
            DB::table('agency_fixed_costs')->where('cost_type', 'administrativo')->update(['cost_type' => 'AGENCY']);
        }
    }
};
