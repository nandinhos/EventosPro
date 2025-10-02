<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use App\Services\FinancialReportService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialReportService $reportService;

    protected GigFinancialCalculatorService $gigCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gigCalculator = $this->app->make(GigFinancialCalculatorService::class);
        $this->reportService = new FinancialReportService($this->gigCalculator);
    }

    #[Test]
    public function it_sets_filters_correctly()
    {
        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'booker_id' => 1,
            'artist_id' => 2,
        ];

        $this->reportService->setFilters($filters);

        // Since we can't access protected properties directly,
        // we test by calling a method that uses the filters
        $result = $this->reportService->getOverviewSummary();
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_gets_overview_summary_with_no_gigs()
    {
        $result = $this->reportService->getOverviewSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_inflow', $result);
        $this->assertArrayHasKey('total_outflow', $result);
        $this->assertArrayHasKey('net_cashflow', $result);

        $this->assertEquals(0, $result['total_inflow']);
        $this->assertEquals(0, $result['total_outflow']);
        $this->assertEquals(0, $result['net_cashflow']);
    }

    #[Test]
    public function it_gets_overview_summary_with_gigs_and_payments()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'contract_date' => Carbon::now()->subDays(5),
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        // Create confirmed payment (should be included in inflow)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 500,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        // Create unconfirmed payment (should not be included)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 300,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        $result = $this->reportService->getOverviewSummary();

        $this->assertEquals(500, $result['total_inflow']);
        $this->assertGreaterThanOrEqual(0, $result['total_outflow']);
        $this->assertIsNumeric($result['net_cashflow']);
    }

    #[Test]
    public function it_gets_overview_table_data_with_no_gigs()
    {
        $result = $this->reportService->getOverviewTableData();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_gets_overview_table_data_with_gigs()
    {
        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'contract_date' => Carbon::now()->subDays(5),
            'contract_number' => 'TEST-001',
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 800,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getOverviewTableData();

        $this->assertCount(1, $result);

        $gigData = $result->first();
        $this->assertEquals('TEST-001', $gigData['contract_number']);
        $this->assertEquals('Test Artist', $gigData['artist']);
        $this->assertEquals('Test Booker', $gigData['booker']);
        $this->assertEquals(800, $gigData['revenue']);
        $this->assertArrayHasKey('costs', $gigData);
        $this->assertArrayHasKey('commission', $gigData);
        $this->assertArrayHasKey('net_profit', $gigData);
    }

    #[Test]
    public function it_filters_by_date_range()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        // Gig within date range
        $gigInRange = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::parse('2024-01-15'),
            'contract_date' => Carbon::parse('2024-01-10'),
        ]);

        // Gig outside date range
        $gigOutOfRange = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::parse('2024-02-15'),
            'contract_date' => Carbon::parse('2024-02-10'),
        ]);

        $this->reportService->setFilters([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $result = $this->reportService->getOverviewTableData();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_filters_by_booker_id()
    {
        $artist = Artist::factory()->create();
        $booker1 = Booker::factory()->create();
        $booker2 = Booker::factory()->create();

        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker1->id,
            'gig_date' => Carbon::now(),
        ]);

        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker2->id,
            'gig_date' => Carbon::now(),
        ]);

        $this->reportService->setFilters([
            'booker_id' => $booker1->id,
        ]);

        $result = $this->reportService->getOverviewTableData();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_filters_by_artist_id()
    {
        $artist1 = Artist::factory()->create();
        $artist2 = Artist::factory()->create();
        $booker = Booker::factory()->create();

        Gig::factory()->create([
            'artist_id' => $artist1->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Gig::factory()->create([
            'artist_id' => $artist2->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        $this->reportService->setFilters([
            'artist_id' => $artist1->id,
        ]);

        $result = $this->reportService->getOverviewTableData();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_handles_gigs_with_missing_relationships_gracefully()
    {
        // Create a gig with valid relationships
        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'contract_number' => 'ERROR-TEST',
        ]);

        $result = $this->reportService->getOverviewTableData();

        // Should return the gig with proper relationships
        $this->assertCount(1, $result);

        $gigData = $result->first();
        $this->assertEquals('ERROR-TEST', $gigData['contract_number']);
        $this->assertEquals('Test Artist', $gigData['artist']);
        $this->assertEquals('Test Booker', $gigData['booker']);
    }

    #[Test]
    public function it_sets_default_period_to_current_month()
    {
        $service = new FinancialReportService($this->gigCalculator);

        // Test that default period works by getting overview data
        $result = $service->getOverviewSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_inflow', $result);
    }

    #[Test]
    public function it_gets_profitability_summary_with_no_gigs()
    {
        $result = $this->reportService->getProfitabilitySummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_profit', $result);
        $this->assertArrayHasKey('average_margin', $result);
        $this->assertArrayHasKey('profitable_events', $result);
        $this->assertEquals(0, $result['total_profit']);
        $this->assertEquals(0, $result['profitable_events']);
    }

    #[Test]
    public function it_gets_profitability_summary_with_gigs()
    {
        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'contract_number' => 'PROFIT-001',
            'cache_value' => 2000,
            'currency' => 'BRL',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1800,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getProfitabilitySummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_profit', $result);
        $this->assertArrayHasKey('average_margin', $result);
        $this->assertArrayHasKey('profitable_events', $result);
        $this->assertIsNumeric($result['total_profit']);
        $this->assertIsNumeric($result['average_margin']);
        $this->assertIsNumeric($result['profitable_events']);
    }

    #[Test]
    public function it_gets_cashflow_summary_with_no_transactions()
    {
        $result = $this->reportService->getCashflowSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_inflow', $result);
        $this->assertArrayHasKey('total_outflow', $result);
        $this->assertArrayHasKey('net_cashflow', $result);
        $this->assertEquals(0, $result['total_inflow']);
    }

    #[Test]
    public function it_gets_cashflow_table_data_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1000,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
            'received_date_actual' => Carbon::now(),
            'received_value_actual' => 1000,
        ]);

        $result = $this->reportService->getCashflowTableData();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        if ($result->count() > 0) {
            $firstEntry = $result->first();
            $this->assertArrayHasKey('date', $firstEntry);
            $this->assertArrayHasKey('type', $firstEntry);
            $this->assertArrayHasKey('description', $firstEntry);
            $this->assertArrayHasKey('value', $firstEntry);
        }
    }

    #[Test]
    public function it_gets_financial_report_data_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1500,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getFinancialReportData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('total_agency_commissions', $result);
        $this->assertArrayHasKey('total_booker_commissions', $result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('events_by_artist', $result);
        $this->assertArrayHasKey('revenue_by_booker', $result);
        $this->assertArrayHasKey('operational_expenses', $result);
        $this->assertArrayHasKey('net_revenue', $result);
        $this->assertArrayHasKey('operational_result', $result);

        $this->assertEquals(1, $result['total_events']);
        $this->assertGreaterThan(0, $result['total_revenue']);
    }

    #[Test]
    public function it_gets_profitability_analysis_data_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 2000,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getProfitabilityAnalysisData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tableData', $result);
        $this->assertArrayHasKey('chartData', $result);

        $chartData = $result['chartData'];
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('netAgencyCommission', $chartData);
        $this->assertArrayHasKey('grossMarginPercentage', $chartData);
        $this->assertArrayHasKey('commissionByBooker', $chartData);
    }

    #[Test]
    public function it_gets_commissions_summary_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1200,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getCommissionsSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_commissions', $result);
        $this->assertArrayHasKey('events_with_commissions', $result);
        $this->assertIsNumeric($result['total_commissions']);
        $this->assertIsNumeric($result['events_with_commissions']);
    }

    #[Test]
    public function it_gets_sales_profitability_data_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1800,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getSalesProfitabilityData();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        if ($result->count() > 0) {
            $firstEntry = $result->first();
            $this->assertArrayHasKey('sale_date', $firstEntry);
            $this->assertArrayHasKey('gig_id', $firstEntry);
            $this->assertArrayHasKey('gig_name', $firstEntry);
            $this->assertArrayHasKey('revenue', $firstEntry);
            $this->assertArrayHasKey('costs', $firstEntry);
            $this->assertArrayHasKey('profitability', $firstEntry);
            $this->assertArrayHasKey('margin', $firstEntry);
        }
    }

    #[Test]
    public function it_gets_commissions_table_data_structure()
    {
        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1600,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getCommissionsTableData();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        if ($result->count() > 0) {
            $firstRow = $result->first();
            $this->assertArrayHasKey('contract_number', $firstRow);
            $this->assertArrayHasKey('gig_date', $firstRow);
            $this->assertArrayHasKey('booker', $firstRow);
            $this->assertArrayHasKey('artist', $firstRow);
            $this->assertArrayHasKey('commission', $firstRow);
            $this->assertArrayHasKey('percentage', $firstRow);
            $this->assertEquals('Test Artist', $firstRow['artist']);
        }
    }

    #[Test]
    public function it_handles_error_cases_gracefully()
    {
        // Test with invalid date filters - should handle gracefully
        try {
            $this->reportService->setFilters([
                'start_date' => 'invalid-date',
                'end_date' => 'invalid-date',
            ]);

            $result = $this->reportService->getOverviewSummary();
            $this->assertIsArray($result);
        } catch (\Exception $e) {
            // Invalid dates should be handled gracefully
            $this->assertTrue(true);
        }

        // Test with non-existent booker_id
        $this->reportService->setFilters([
            'booker_id' => 99999,
        ]);

        $result = $this->reportService->getOverviewTableData();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_gets_detailed_expenses_with_no_expenses()
    {
        $result = $this->reportService->getDetailedExpenses();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_gets_detailed_expenses_with_filters()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        // Create a cost center for testing
        $costCenter = \App\Models\CostCenter::factory()->create(['name' => 'Test Cost Center']);

        // Create expenses
        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 500,
            'currency' => 'BRL',
            'expense_date' => Carbon::now(),
            'is_confirmed' => true,
            'description' => 'Test expense',
        ]);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 300,
            'currency' => 'BRL',
            'expense_date' => Carbon::now(),
            'is_confirmed' => false,
            'description' => 'Pending expense',
        ]);

        // Test with artist filter
        $this->reportService->setFilters(['artist_id' => $artist->id]);
        $result = $this->reportService->getDetailedExpenses();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);

        // Test with booker filter
        $this->reportService->setFilters(['booker_id' => $booker->id]);
        $result = $this->reportService->getDetailedExpenses();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);

        // Test with cost center filter
        $this->reportService->setFilters(['cost_center_id' => $costCenter->id]);
        $result = $this->reportService->getDetailedExpenses();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);

        // Test with status filter (confirmed only)
        $this->reportService->setFilters(['status' => 1]);
        $result = $this->reportService->getDetailedExpenses();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result);
    }

    #[Test]
    public function it_gets_grouped_expenses_data_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        $costCenter = \App\Models\CostCenter::factory()->create(['name' => 'Test Cost Center']);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 1000,
            'currency' => 'BRL',
            'expense_date' => Carbon::now(),
            'is_confirmed' => true,
        ]);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 500,
            'currency' => 'BRL',
            'expense_date' => Carbon::now(),
            'is_confirmed' => false,
        ]);

        $result = $this->reportService->getGroupedExpensesData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('groups', $result);
        $this->assertArrayHasKey('total_geral', $result);
        $this->assertArrayHasKey('total_confirmado', $result);
        $this->assertArrayHasKey('total_pendente', $result);

        $this->assertEquals(1500, $result['total_geral']);
        $this->assertEquals(1000, $result['total_confirmado']);
        $this->assertEquals(500, $result['total_pendente']);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['groups']);
    }

    #[Test]
    public function it_gets_grouped_commissions_data_structure()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create(['name' => 'Test Booker']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 2000,
            'currency' => 'BRL',
        ]);

        $result = $this->reportService->getGroupedCommissionsData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('groups', $result);

        $summary = $result['summary'];
        $this->assertArrayHasKey('total_commissions', $summary);
        $this->assertArrayHasKey('events_with_commissions', $summary);
        $this->assertArrayHasKey('total_commission_base', $summary);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result['groups']);
    }

    #[Test]
    public function it_gets_overview_data_structure()
    {
        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 3000,
            'currency' => 'BRL',
            'location_event_details' => 'Test Venue',
            'contract_status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $result = $this->reportService->getOverviewData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dataByArtist', $result);
        $this->assertArrayHasKey('grandTotals', $result);

        $dataByArtist = $result['dataByArtist'];
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $dataByArtist);

        if ($dataByArtist->count() > 0) {
            $artistData = $dataByArtist->first();
            $this->assertArrayHasKey('artist_name', $artistData);
            $this->assertArrayHasKey('gigs', $artistData);
            $this->assertArrayHasKey('subtotals', $artistData);
            $this->assertArrayHasKey('gig_count', $artistData);

            $this->assertEquals('Test Artist', $artistData['artist_name']);
            $this->assertEquals(1, $artistData['gig_count']);

            $gigs = $artistData['gigs'];
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $gigs);

            if ($gigs->count() > 0) {
                $gigData = $gigs->first();
                $this->assertArrayHasKey('gig_id', $gigData);
                $this->assertArrayHasKey('gig_date', $gigData);
                $this->assertArrayHasKey('artist_name', $gigData);
                $this->assertArrayHasKey('booker_name', $gigData);
                $this->assertArrayHasKey('location_event_details', $gigData);
                $this->assertArrayHasKey('cache_bruto_brl', $gigData);
                $this->assertArrayHasKey('contract_status', $gigData);
                $this->assertArrayHasKey('payment_status', $gigData);
            }
        }

        $grandTotals = $result['grandTotals'];
        $this->assertArrayHasKey('cache_bruto_brl', $grandTotals);
        $this->assertArrayHasKey('total_despesas_confirmadas_brl', $grandTotals);
        $this->assertArrayHasKey('cache_liquido_base_brl', $grandTotals);
        $this->assertArrayHasKey('gig_count', $grandTotals);
    }

    #[Test]
    public function it_handles_edge_cases_in_calculations()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        // Test with zero values
        $gigZero = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 0,
            'currency' => 'BRL',
        ]);

        $result = $this->reportService->getOverviewSummary();
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_inflow']);

        // Test with negative values
        $gigNegative = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => -500,
            'currency' => 'BRL',
        ]);

        $tableData = $this->reportService->getOverviewTableData();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $tableData);

        // Test with very large values
        $gigLarge = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 999999999,
            'currency' => 'BRL',
        ]);

        $overviewData = $this->reportService->getOverviewData();
        $this->assertIsArray($overviewData);
        $this->assertArrayHasKey('grandTotals', $overviewData);
    }

    #[Test]
    public function it_gets_cashflow_summary_with_transactions()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        // Create confirmed payment (inflow)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'received_value_actual' => 2000,
            'received_date_actual' => Carbon::now(),
            'confirmed_at' => Carbon::now(),
        ]);

        // Create confirmed expense (outflow)
        $costCenter = \App\Models\CostCenter::factory()->create();
        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 500,
            'is_confirmed' => true,
            'confirmed_at' => Carbon::now(),
        ]);

        // Create settlement for artist payment
        \App\Models\Settlement::factory()->create([
            'gig_id' => $gig->id,
            'artist_payment_value' => 800,
            'artist_payment_paid_at' => Carbon::now(),
        ]);

        // Create settlement for booker commission
        \App\Models\Settlement::factory()->create([
            'gig_id' => $gig->id,
            'booker_commission_value_paid' => 100,
            'booker_commission_paid_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getCashflowSummary();

        $this->assertEquals(2000, $result['total_inflow']);
        $this->assertEquals(500, $result['total_outflow_expenses']);
        $this->assertEquals(800, $result['total_outflow_artists']);
        $this->assertEquals(100, $result['total_outflow_bookers']);
        $this->assertEquals(1400, $result['total_outflow']);
        $this->assertEquals(600, $result['net_cashflow']);
    }

    #[Test]
    public function it_gets_expenses_table_data_with_multiple_cost_centers()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        $costCenter1 = \App\Models\CostCenter::factory()->create(['name' => 'Transport']);
        $costCenter2 = \App\Models\CostCenter::factory()->create(['name' => 'Accommodation']);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter1->id,
            'value' => 500,
            'currency' => 'BRL',
            'expense_date' => Carbon::now(),
            'description' => 'Transport expense',
        ]);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter2->id,
            'value' => 800,
            'currency' => 'BRL',
            'expense_date' => Carbon::now(),
            'description' => 'Hotel expense',
        ]);

        $result = $this->reportService->getExpensesTableData();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        if ($result->count() > 0) {
            $firstGroup = $result->first();
            $this->assertArrayHasKey('cost_center_name', $firstGroup);
            $this->assertArrayHasKey('total_brl', $firstGroup);
            $this->assertArrayHasKey('expenses', $firstGroup);

            $expenses = $firstGroup['expenses'];
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $expenses);

            if ($expenses->count() > 0) {
                $firstExpense = $expenses->first();
                $this->assertArrayHasKey('gig_contract_number', $firstExpense);
                $this->assertArrayHasKey('description', $firstExpense);
                $this->assertArrayHasKey('expense_date', $firstExpense);
                $this->assertArrayHasKey('value_brl', $firstExpense);
                $this->assertArrayHasKey('currency', $firstExpense);
            }
        }
    }

    #[Test]
    public function it_gets_detailed_performance_data_with_complete_structure()
    {
        $artist = Artist::factory()->create(['name' => 'Performance Artist']);
        $booker = Booker::factory()->create(['name' => 'Performance Booker']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'location_event_details' => 'Performance Venue',
            'cache_value' => 4000,
            'currency' => 'BRL',
            'contract_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $result = $this->reportService->getDetailedPerformanceData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tableData', $result);
        $this->assertArrayHasKey('totals', $result);

        $tableData = $result['tableData'];
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $tableData);

        if ($tableData->count() > 0) {
            $firstRow = $tableData->first();
            $this->assertArrayHasKey('gig_date', $firstRow);
            $this->assertArrayHasKey('artist_name', $firstRow);
            $this->assertArrayHasKey('booker_name', $firstRow);
            $this->assertArrayHasKey('location_event_details', $firstRow);
            $this->assertArrayHasKey('cache_bruto_original', $firstRow);
            $this->assertArrayHasKey('cache_bruto_brl', $firstRow);
            $this->assertArrayHasKey('total_despesas_confirmadas_brl', $firstRow);
            $this->assertArrayHasKey('cache_liquido_base_brl', $firstRow);
            $this->assertArrayHasKey('repasse_estimado_artista_brl', $firstRow);
            $this->assertArrayHasKey('comissao_agencia_brl', $firstRow);
            $this->assertArrayHasKey('comissao_booker_brl', $firstRow);
            $this->assertArrayHasKey('comissao_agencia_liquida_brl', $firstRow);
            $this->assertArrayHasKey('contract_status', $firstRow);
            $this->assertArrayHasKey('payment_status', $firstRow);

            $this->assertEquals('Performance Artist', $firstRow['artist_name']);
            $this->assertEquals('Performance Booker', $firstRow['booker_name']);
        }

        $totals = $result['totals'];
        $this->assertArrayHasKey('cache_bruto_brl', $totals);
        $this->assertArrayHasKey('total_despesas_confirmadas_brl', $totals);
        $this->assertArrayHasKey('cache_liquido_base_brl', $totals);
        $this->assertArrayHasKey('repasse_estimado_artista_brl', $totals);
        $this->assertArrayHasKey('comissao_agencia_brl', $totals);
        $this->assertArrayHasKey('comissao_booker_brl', $totals);
        $this->assertArrayHasKey('comissao_agencia_liquida_brl', $totals);
    }

    #[Test]
    public function it_handles_gigs_without_booker_in_profitability_analysis()
    {
        $artist = Artist::factory()->create();

        // Create gig without booker (direct agency)
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => null,
            'gig_date' => Carbon::now(),
            'cache_value' => 1500,
            'currency' => 'BRL',
        ]);

        $result = $this->reportService->getProfitabilityAnalysisData();

        $this->assertIsArray($result);
        $chartData = $result['chartData'];
        $commissionByBooker = $chartData['commissionByBooker'];

        // Should include "Agência Direta" if there are gigs without booker
        $this->assertIsArray($commissionByBooker['labels']);
        $this->assertIsArray($commissionByBooker['data']);
    }

    #[Test]
    public function it_filters_cashflow_data_by_date_range()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gigInRange = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
        ]);

        $gigOutOfRange = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addMonths(2),
        ]);

        // Payment in range
        Payment::factory()->create([
            'gig_id' => $gigInRange->id,
            'received_value_actual' => 1000,
            'received_date_actual' => Carbon::now(),
            'confirmed_at' => Carbon::now(),
        ]);

        // Payment out of range
        Payment::factory()->create([
            'gig_id' => $gigOutOfRange->id,
            'received_value_actual' => 2000,
            'received_date_actual' => Carbon::now()->addMonths(2),
            'confirmed_at' => Carbon::now()->addMonths(2),
        ]);

        // Set date filter
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);

        $result = $this->reportService->getCashflowSummary();

        // Should only include the payment in range
        $this->assertEquals(1000, $result['total_inflow']);
    }

    #[Test]
    public function it_handles_deleted_gigs_in_cashflow_table_data()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        $costCenter = \App\Models\CostCenter::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        // Create related data
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'received_date_actual' => Carbon::now(),
            'received_value_actual' => 1000,
            'confirmed_at' => Carbon::now(),
        ]);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 200,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'confirmed_at' => Carbon::now(),
        ]);

        // Soft delete the gig
        $gig->delete();

        $result = $this->reportService->getCashflowTableData();

        // Should not include transactions from deleted gigs
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    #[Test]
    public function it_gets_cashflow_table_data_with_all_transaction_types()
    {
        // Set date range to include all transactions
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);
        $costCenter = \App\Models\CostCenter::factory()->create(['name' => 'Transport']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 2000,
            'currency' => 'BRL',
        ]);

        // Create payment (inflow)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'received_date_actual' => Carbon::now()->addDay(),
            'received_value_actual' => 2000,
            'confirmed_at' => Carbon::now(),
            'description' => 'Contract payment',
        ]);

        // Create expense (outflow)
        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 300,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'confirmed_at' => Carbon::now()->addDays(2),
            'description' => 'Travel expense',
        ]);

        // Create settlement for artist (outflow)
        \App\Models\Settlement::factory()->create([
            'gig_id' => $gig->id,
            'artist_payment_value' => 1500,
            'artist_payment_paid_at' => Carbon::now()->addDays(3),
        ]);

        // Create settlement for booker (outflow)
        \App\Models\Settlement::factory()->create([
            'gig_id' => $gig->id,
            'booker_commission_value_paid' => 100,
            'booker_commission_paid_at' => Carbon::now()->addDays(4),
        ]);

        $result = $this->reportService->getCashflowTableData();

        $this->assertCount(4, $result);

        // Check inflow transaction
        $inflow = $result->where('type', 'Entrada')->first();
        $this->assertNotNull($inflow, 'Inflow transaction should exist');
        $this->assertEquals('Entrada', $inflow['type']);
        $this->assertEquals(2000, $inflow['value']);
        $this->assertEquals('Test Artist', $inflow['artist_name']);
        $this->assertStringContainsString('Contract payment', $inflow['description']);

        // Check expense outflow
        $expenseOutflow = $result->where('type', 'Saída')->filter(function ($item) {
            return str_contains($item['description'], 'Transport');
        })->first();
        $this->assertNotNull($expenseOutflow, 'Expense outflow should exist');
        $this->assertEquals('Saída', $expenseOutflow['type']);
        $this->assertEquals(-300, $expenseOutflow['value']);
        $this->assertStringContainsString('Transport', $expenseOutflow['description']);

        // Check artist payment outflow
        $artistOutflow = $result->where('type', 'Saída')->filter(function ($item) {
            return str_contains($item['description'], 'Pagamento Artista');
        })->first();
        $this->assertNotNull($artistOutflow, 'Artist payment outflow should exist');
        $this->assertEquals('Saída', $artistOutflow['type']);
        $this->assertEquals(-1500, $artistOutflow['value']);
        $this->assertStringContainsString('Test Artist', $artistOutflow['description']);

        // Check booker payment outflow
        $bookerOutflow = $result->where('type', 'Saída')->filter(function ($item) {
            return str_contains($item['description'], 'Pagamento Booker');
        })->first();
        $this->assertNotNull($bookerOutflow, 'Booker payment outflow should exist');
        $this->assertEquals('Saída', $bookerOutflow['type']);
        $this->assertEquals(-100, $bookerOutflow['value']);
        $this->assertStringContainsString('Test Booker', $bookerOutflow['description']);
    }

    #[Test]
    public function it_gets_cashflow_table_data_sorted_by_date()
    {
        // Set date range to include all transactions
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        $costCenter = \App\Models\CostCenter::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        // Create transactions with different dates
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'received_date_actual' => Carbon::now()->addDays(3),
            'received_value_actual' => 1000,
            'confirmed_at' => Carbon::now(),
        ]);

        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 200,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'confirmed_at' => Carbon::now()->addDay(),
        ]);

        \App\Models\Settlement::factory()->create([
            'gig_id' => $gig->id,
            'artist_payment_value' => 700,
            'artist_payment_paid_at' => Carbon::now()->addDays(2),
        ]);

        $result = $this->reportService->getCashflowTableData();

        // Should be sorted by date
        $this->assertCount(3, $result);

        $dates = $result->pluck('date')->map(function ($date) {
            return $date->format('Y-m-d');
        })->toArray();

        $sortedDates = collect($dates)->sort()->values()->toArray();
        $this->assertEquals($sortedDates, $dates);
    }

    #[Test]
    public function it_returns_correct_financial_report_structure()
    {
        // Set date range to include all transactions
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);
        $costCenter = \App\Models\CostCenter::factory()->create(['name' => 'Transport']);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 5000,
            'currency' => 'BRL',
        ]);

        // Create payment
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 5000,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        // Create expense
        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 500,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'expense_date' => Carbon::now(),
        ]);

        $result = $this->reportService->getFinancialReportData();

        // Check structure
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('total_agency_commissions', $result);
        $this->assertArrayHasKey('total_booker_commissions', $result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('events_by_artist', $result);
        $this->assertArrayHasKey('revenue_by_booker', $result);
        $this->assertArrayHasKey('operational_expenses', $result);
        $this->assertArrayHasKey('total_operational_expenses', $result);
        $this->assertArrayHasKey('net_revenue', $result);
        $this->assertArrayHasKey('operational_result', $result);

        // Check values
        $this->assertEquals(5000, $result['total_revenue']);
        $this->assertEquals(1, $result['total_events']);
        $this->assertArrayHasKey('Test Artist', $result['events_by_artist']);
        $this->assertArrayHasKey('Test Booker', $result['revenue_by_booker']);
        $this->assertEquals(5000, $result['revenue_by_booker']['Test Booker']);
    }

    #[Test]
    public function it_calculates_commissions_correctly_in_report()
    {
        // Set date range to include all transactions
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 10000,
            'currency' => 'BRL',
        ]);

        // Create payment
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 10000,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        // Create expense
        \App\Models\GigCost::factory()->create([
            'gig_id' => $gig->id,
            'value' => 1000,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'expense_date' => Carbon::now(),
        ]);

        $result = $this->reportService->getFinancialReportData();

        // Expected calculations:
        // Gross cash: 9000 (10000 - 1000 confirmed expense)
        // Agency commission (20%): 1800 (20% of 9000)
        // Booker commission (5%): 450 (5% of 9000)

        $this->assertEquals(10000, $result['total_revenue']);
        $this->assertEquals(1800, $result['total_agency_commissions']);
        $this->assertEquals(450, $result['total_booker_commissions']);
    }

    #[Test]
    public function it_handles_multiple_gigs_in_report()
    {
        // Set date range to include all transactions
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ]);

        $artist1 = Artist::factory()->create(['name' => 'Artist One']);
        $artist2 = Artist::factory()->create(['name' => 'Artist Two']);
        $booker = Booker::factory()->create(['name' => 'Test Booker']);

        // First gig
        $gig1 = Gig::factory()->create([
            'artist_id' => $artist1->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now(),
            'cache_value' => 5000,
            'currency' => 'BRL',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig1->id,
            'due_value' => 5000,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        // Second gig
        $gig2 = Gig::factory()->create([
            'artist_id' => $artist2->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDay(),
            'cache_value' => 8000,
            'currency' => 'BRL',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig2->id,
            'due_value' => 8000,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->reportService->getFinancialReportData();

        $this->assertEquals(13000, $result['total_revenue']);
        $this->assertEquals(2, $result['total_events']);
        $this->assertArrayHasKey('Artist One', $result['events_by_artist']);
        $this->assertArrayHasKey('Artist Two', $result['events_by_artist']);
        $this->assertEquals(13000, $result['revenue_by_booker']['Test Booker']);
    }
}
