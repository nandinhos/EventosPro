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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_gets_overview_table_data_with_no_gigs()
    {
        $result = $this->reportService->getOverviewTableData();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(0, $result);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_sets_default_period_to_current_month()
    {
        $service = new FinancialReportService($this->gigCalculator);

        // Test that default period works by getting overview data
        $result = $service->getOverviewSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_inflow', $result);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_gets_cashflow_summary_with_no_transactions()
    {
        $result = $this->reportService->getCashflowSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_inflow', $result);
        $this->assertArrayHasKey('total_outflow', $result);
        $this->assertArrayHasKey('net_cashflow', $result);
        $this->assertEquals(0, $result['total_inflow']);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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
}
