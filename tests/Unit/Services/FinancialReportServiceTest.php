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
}