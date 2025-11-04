<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 2 Performance Optimization - Database Indexes
     *
     * Adds composite indexes to improve query performance for:
     * - Financial projections (date + status filtering)
     * - Revenue reports (date + artist/booker filtering)
     * - Expense reconciliation (date + confirmation status)
     * - Cash flow calculations (payment and settlement dates)
     *
     * Expected Impact: 5-50x performance improvement on critical queries
     */
    public function up(): void
    {
        // PRIORITY 1 - CRITICAL: Gigs table indexes
        Schema::table('gigs', function (Blueprint $table) {
            // Index 1: Date + Booker Payment Status (8+ queries/day)
            // Used in: ProjectionQueryBuilder, DashboardService
            $table->index(['gig_date', 'booker_payment_status'], 'idx_gigs_date_booker_payment');

            // Index 2: Date + Artist ID (12+ queries/day)
            // Used in: FinancialReportService, BookerFinancialsService, ArtistController
            $table->index(['gig_date', 'artist_id'], 'idx_gigs_date_artist_id');

            // Index 3: Date + Booker ID (12+ queries/day)
            // Used in: FinancialReportService, BookerFinancialsService, DashboardService
            $table->index(['gig_date', 'booker_id'], 'idx_gigs_date_booker_id');

            // Index 4: Contract Date (4+ queries/day)
            // Used in: DashboardService (monthly revenue chart), DRE calculations
            $table->index(['contract_date'], 'idx_gigs_contract_date');

            // Index 5: Soft Deletes + Date (improves all gig queries)
            // Used in: All Gig queries when soft-deleted records exist
            $table->index(['deleted_at', 'gig_date'], 'idx_gigs_deleted_gig_date');
        });

        // PRIORITY 1 - CRITICAL: GigCosts table indexes
        Schema::table('gig_costs', function (Blueprint $table) {
            $table->index(['expense_date', 'is_confirmed'], 'idx_gig_costs_expense_confirmed');
            $table->index(['cost_center_id', 'expense_date'], 'idx_gig_costs_center_date');
        });

        // PRIORITY 2 - HIGH: Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['received_date_actual', 'confirmed_at', 'gig_id'], 'idx_payments_received_date_confirmed');
        });

        // PRIORITY 2 - HIGH: Settlements table indexes
        Schema::table('settlements', function (Blueprint $table) {
            $table->index(['settlement_date', 'gig_id'], 'idx_settlements_date_gig');
        });

        // PRIORITY 3 - MEDIUM: AgencyFixedCosts table indexes
        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->index(['is_active', 'cost_center_id'], 'idx_agency_fixed_costs_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gigs', function (Blueprint $table) {
            $table->dropIndex('idx_gigs_date_booker_payment');
            $table->dropIndex('idx_gigs_date_artist_id');
            $table->dropIndex('idx_gigs_date_booker_id');
            $table->dropIndex('idx_gigs_contract_date');
            $table->dropIndex('idx_gigs_deleted_gig_date');
        });

        Schema::table('gig_costs', function (Blueprint $table) {
            $table->dropIndex('idx_gig_costs_expense_confirmed');
            $table->dropIndex('idx_gig_costs_center_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_received_date_confirmed');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropIndex('idx_settlements_date_gig');
        });

        Schema::table('agency_fixed_costs', function (Blueprint $table) {
            $table->dropIndex('idx_agency_fixed_costs_active');
        });
    }
};
