<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Settlement;
use App\Services\BookerFinancialsService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookerFinancialsServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookerFinancialsService $service;
    private Booker $booker;
    private Artist $artist;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->booker = Booker::factory()->create();
        $this->artist = Artist::factory()->create();
        
        $this->service = app(BookerFinancialsService::class);
    }

    /** @test */
    public function it_calculates_sales_kpis_without_date_filter()
    {
        // Arrange
        $gig1 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(10),
            'payment_status' => 'pago', // Garantir que o valor BRL seja calculado
        ]);
        
        $gig2 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(5),
            'payment_status' => 'pago', // Garantir que o valor BRL seja calculado
        ]);

        // Act
        $result = $this->service->getSalesKpis($this->booker);

        // Assert
        $this->assertEquals(3000.00, $result['total_sold_value']);
        $this->assertEquals(2, $result['total_gigs_sold']);
    }

    /** @test */
    public function it_calculates_sales_kpis_with_date_filter()
    {
        // Arrange
        $startDate = now()->subDays(15);
        $endDate = now()->subDays(8);
        
        // Gig dentro do período
        $gigInPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1500.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(10),
            'gig_date' => now()->subDays(10),
            'payment_status' => 'pago',
        ]);
        
        // Gig fora do período
        $gigOutOfPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2500.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(20),
            'gig_date' => now()->subDays(20),
            'payment_status' => 'pago',
        ]);

        // Act
        $result = $this->service->getSalesKpis($this->booker, $startDate, $endDate);

        // Assert
        $this->assertEquals(1500.00, $result['total_sold_value']);
        $this->assertEquals(1, $result['total_gigs_sold']);
    }

    /** @test */
    public function it_calculates_commission_kpis()
    {
        // Arrange
        $gig1 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'booker_commission_type' => 'fixed',
            'booker_commission_value' => 100.00,
            'booker_payment_status' => 'pendente',
        ]);
        
        $gig2 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2000.00,
            'currency' => 'BRL',
            'booker_commission_type' => 'percentage',
            'booker_commission_rate' => 5.0,
            'booker_commission_value' => 100.00,
            'booker_payment_status' => 'pago',
        ]);

        // Create settlements
        Settlement::factory()->create([
            'gig_id' => $gig1->id,
            'booker_commission_value_paid' => 100.00,
            'booker_commission_paid_at' => null,
        ]);
        
        Settlement::factory()->create([
            'gig_id' => $gig2->id,
            'booker_commission_value_paid' => 100.00,
            'booker_commission_paid_at' => now(),
        ]);

        // Act
        $result = $this->service->getCommissionKpis($this->booker);

        // Assert
        $this->assertEquals(100.00, $result['commission_received']);
        $this->assertEquals(100.00, $result['commission_to_receive']);
    }

    /** @test */
    public function it_gets_commission_chart_data()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
        ]);
        
        Settlement::factory()->create([
            'gig_id' => $gig->id,
            'booker_commission_value_paid' => 150.00,
            'booker_commission_paid_at' => now()->subMonth(),
        ]);

        // Act
        $result = $this->service->getCommissionChartData($this->booker);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['data']);
    }

    /** @test */
    public function it_gets_top_artists_without_date_filter()
    {
        // Arrange
        $artist2 = Artist::factory()->create(['name' => 'Artist Two']);
        
        // Artist 1 - 2 gigs
        Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(10),
            'payment_status' => 'pago',
        ]);
        
        Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1500.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(5),
            'payment_status' => 'pago',
        ]);
        
        // Artist 2 - 1 gig
        Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $artist2->id,
            'cache_value' => 3000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(3),
            'payment_status' => 'pago',
        ]);

        // Act
        $result = $this->service->getTopArtists($this->booker);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('Artist Two', $result->first()->artist_name);
        $this->assertEquals(3000.00, $result->first()->total_value);
        $this->assertEquals(1, $result->first()->gigs_count);
    }

    /** @test */
    public function it_gets_top_artists_with_date_filter_and_limit()
    {
        // Arrange
        $startDate = now()->subDays(15);
        $endDate = now()->subDays(8);
        
        $artist2 = Artist::factory()->create();
        $artist3 = Artist::factory()->create();
        
        // Gigs within period
        Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(10),
            'gig_date' => now()->subDays(10),
            'payment_status' => 'pago',
        ]);
        
        Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $artist2->id,
            'cache_value' => 2000.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(12),
            'gig_date' => now()->subDays(12),
            'payment_status' => 'pago',
        ]);
        
        // Gig outside period
        Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $artist3->id,
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(20),
            'gig_date' => now()->subDays(20),
            'payment_status' => 'pago',
        ]);

        // Act
        $result = $this->service->getTopArtists($this->booker, $startDate, $endDate, 5);

        // Assert
        $this->assertCount(2, $result);
        // Should not include artist3 as it's outside the date range
        $this->assertFalse($result->contains('artist_name', $artist3->name));
    }

    /** @test */
    public function it_gets_recent_gigs()
    {
        // Arrange
        $gig1 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(1),
            'payment_status' => 'pago',
        ]);
        
        $gig2 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(5),
            'payment_status' => 'pago',
        ]);

        // Act
        $result = $this->service->getRecentGigs($this->booker);

        // Assert
        $this->assertCount(2, $result);
        // Verificar que o gig mais recente vem primeiro (ordenação por data)
        // O método getRecentGigs ordena por COALESCE(contract_date, gig_date) DESC
        // Então o primeiro deve ter data maior ou igual ao último
        $firstDate = $result->first()->contract_date ?? $result->first()->gig_date;
        $lastDate = $result->last()->contract_date ?? $result->last()->gig_date;
        $this->assertTrue($firstDate->gte($lastDate));
    }

    /** @test */
    public function it_gets_gigs_for_period()
    {
        // Arrange
        $startDate = now()->subDays(10);
        $endDate = now()->subDays(5);
        
        $gigInPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1500.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(7),
            'gig_date' => now()->subDays(7),
            'payment_status' => 'pago',
        ]);
        
        $gigOutOfPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2500.00,
            'currency' => 'BRL',
            'contract_date' => now()->subDays(15),
            'gig_date' => now()->subDays(15),
            'payment_status' => 'pago',
        ]);

        // Act
        $result = $this->service->getGigsForPeriod($this->booker, $startDate, $endDate);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($gigInPeriod->id, $result->first()->id);
    }

    /** @test */
    public function it_excludes_deleted_gigs_from_all_methods()
    {
        // Arrange
        $gig1 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(10),
            'payment_status' => 'pago',
        ]);
        
        $gig2 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(5),
            'payment_status' => 'pago',
        ]);
        
        // Soft delete one gig
        $gig2->delete();

        // Act & Assert
        $salesKpis = $this->service->getSalesKpis($this->booker);
        $this->assertEquals(1000.00, $salesKpis['total_sold_value']);
        $this->assertEquals(1, $salesKpis['total_gigs_sold']);
        
        $topArtists = $this->service->getTopArtists($this->booker);
        $this->assertCount(1, $topArtists);
        
        $recentGigs = $this->service->getRecentGigs($this->booker);
        $this->assertCount(1, $recentGigs);
    }

    /** @test */
    public function it_handles_empty_results_gracefully()
    {
        // Act
        $salesKpis = $this->service->getSalesKpis($this->booker);
        $commissionKpis = $this->service->getCommissionKpis($this->booker);
        $chartData = $this->service->getCommissionChartData($this->booker);
        $topArtists = $this->service->getTopArtists($this->booker);
        $recentGigs = $this->service->getRecentGigs($this->booker);

        // Assert
        $this->assertEquals(0, $salesKpis['total_sold_value']);
        $this->assertEquals(0, $salesKpis['total_gigs_sold']);
        
        $this->assertEquals(0, $commissionKpis['commission_received']);
        $this->assertEquals(0, $commissionKpis['commission_to_receive']);
        
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        
        $this->assertCount(0, $topArtists);
        $this->assertCount(0, $recentGigs);
    }
}