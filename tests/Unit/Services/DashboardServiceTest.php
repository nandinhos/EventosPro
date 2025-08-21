<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Services\DashboardService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;
    protected GigFinancialCalculatorService $gigCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gigCalculator = $this->app->make(GigFinancialCalculatorService::class);
        $this->dashboardService = new DashboardService($this->gigCalculator);
    }

    /** @test */
    public function it_sets_filters_correctly()
    {
        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ];

        $result = $this->dashboardService->setFilters($filters);

        $this->assertInstanceOf(DashboardService::class, $result);
    }

    /** @test */
    public function it_gets_first_and_last_month_with_no_gigs()
    {
        $result = $this->dashboardService->getFirstAndLastMonth();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('first_month', $result);
        $this->assertArrayHasKey('last_month', $result);
        
        // Should return current month when no gigs exist
        $now = Carbon::now();
        $this->assertEquals($now->copy()->startOfMonth()->format('Y-m-d'), $result['first_month']);
        $this->assertEquals($now->copy()->endOfMonth()->format('Y-m-d'), $result['last_month']);
    }

    /** @test */
    public function it_gets_first_and_last_month_with_gigs()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        // Create gigs with different dates
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::parse('2024-01-15'),
            'contract_date' => Carbon::parse('2024-01-10'),
        ]);

        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::parse('2024-06-20'),
            'contract_date' => Carbon::parse('2024-06-15'),
        ]);

        $result = $this->dashboardService->getFirstAndLastMonth();

        $this->assertEquals('2024-01-01', $result['first_month']);
        $this->assertEquals('2024-06-30', $result['last_month']);
    }

    /** @test */
    public function it_gets_dashboard_data()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        // Create some test gigs
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(5),
            'contract_date' => Carbon::now(),
            'payment_status' => 'confirmado',
            'artist_payment_status' => 'pendente',
            'booker_payment_status' => 'pendente',
        ]);

        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDays(5),
            'contract_date' => Carbon::now()->subDays(10),
            'payment_status' => 'vencido',
            'artist_payment_status' => 'pago',
            'booker_payment_status' => 'pago',
        ]);

        $result = $this->dashboardService->getDashboardData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalGigsCount', $result);
        $this->assertArrayHasKey('activeFutureGigsCount', $result);
        $this->assertArrayHasKey('overdueClientPaymentsCount', $result);
        $this->assertArrayHasKey('pendingArtistPaymentsCount', $result);
        $this->assertArrayHasKey('pendingBookerPaymentsCount', $result);
        $this->assertArrayHasKey('gigsThisMonthCount', $result);
        
        $this->assertEquals(2, $result['totalGigsCount']);
        $this->assertEquals(1, $result['activeFutureGigsCount']);
        $this->assertEquals(1, $result['overdueClientPaymentsCount']);
        $this->assertEquals(1, $result['pendingArtistPaymentsCount']);
        $this->assertEquals(1, $result['pendingBookerPaymentsCount']);
    }

    /** @test */
    public function it_sets_default_period_correctly()
    {
        $service = new DashboardService($this->gigCalculator);
        
        // Test that default period is set to current month
        $result = $service->getDashboardData();
        
        $this->assertArrayHasKey('startOfMonth', $result);
        $this->assertArrayHasKey('endOfMonth', $result);
        
        $expectedStart = Carbon::now()->startOfMonth();
        $expectedEnd = Carbon::now()->endOfMonth();
        
        $this->assertEquals($expectedStart->format('Y-m-d'), $result['startOfMonth']->format('Y-m-d'));
        $this->assertEquals($expectedEnd->format('Y-m-d'), $result['endOfMonth']->format('Y-m-d'));
    }
}