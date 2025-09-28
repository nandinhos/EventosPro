<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Settlement;
use App\Services\BookerFinancialsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BookerFinancialsServiceTest extends TestCase
{
    use RefreshDatabase;    private BookerFinancialsService $service;    private Booker $booker;

    private Artist $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->booker = Booker::factory()->create();
        $this->artist = Artist::factory()->create();

        $this->service = app(BookerFinancialsService::class);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_gets_realized_events_without_date_filter()
    {
        // Arrange
        $pastGig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(5),
            'payment_status' => 'pago',
            'artist_payment_status' => 'pago',
            'booker_payment_status' => 'pendente',
            'contract_status' => 'assinado',
            'location_event_details' => 'São Paulo',
            'contract_number' => 'CTR001',
            'notes' => 'Test notes',
        ]);

        $futureGig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(5),
        ]);

        // Act
        $result = $this->service->getRealizedEvents($this->booker);

        // Assert
        $this->assertCount(1, $result);
        $event = $result->first();
        $this->assertEquals($pastGig->id, $event['id']);
        $this->assertEquals('CTR001', $event['contract_number']);
        $this->assertEquals($pastGig->gig_date->format('d/m/Y'), $event['gig_date']);
        $this->assertEquals($this->artist->name, $event['artist_name']);
        $this->assertEquals('São Paulo', $event['location']);
        $this->assertEquals(1000.00, $event['cache_value_brl']);
        $this->assertEquals('pago', $event['payment_status']);
        $this->assertEquals('pago', $event['artist_payment_status']);
        $this->assertEquals('pendente', $event['booker_payment_status']);
        $this->assertEquals('assinado', $event['contract_status']);
        $this->assertEquals('Test notes', $event['notes']);
        $this->assertTrue($event['can_pay_commission']);
        $this->assertFalse($event['is_exception']);
    }

    #[Test]
    public function it_gets_realized_events_with_date_filter()
    {
        // Arrange
        $startDate = now()->subDays(10);
        $endDate = now()->subDays(3);

        $gigInPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(5),
        ]);

        $gigOutOfPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(15),
        ]);

        // Act
        $result = $this->service->getRealizedEvents($this->booker, $startDate, $endDate);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($gigInPeriod->id, $result->first()['id']);
    }

    #[Test]
    public function it_gets_future_events_without_date_filter()
    {
        // Arrange
        $futureGig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 2000.00,
            'currency' => 'BRL',
            'gig_date' => now()->addDays(10),
            'payment_status' => 'pendente',
            'artist_payment_status' => 'pendente',
            'booker_payment_status' => 'pendente',
            'contract_status' => 'assinado',
            'location_event_details' => 'Rio de Janeiro',
            'contract_number' => 'CTR002',
            'notes' => 'exceção autorizada para pagamento antecipado',
        ]);

        $pastGig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(5),
        ]);

        // Act
        $result = $this->service->getFutureEvents($this->booker);

        // Assert
        $this->assertCount(1, $result);
        $event = $result->first();
        $this->assertEquals($futureGig->id, $event['id']);
        $this->assertEquals('CTR002', $event['contract_number']);
        $this->assertEquals($futureGig->gig_date->format('d/m/Y'), $event['gig_date']);
        $this->assertEquals($this->artist->name, $event['artist_name']);
        $this->assertEquals('Rio de Janeiro', $event['location']);
        $this->assertEquals(2000.00, $event['cache_value_brl']);
        $this->assertEquals('pendente', $event['payment_status']);
        $this->assertEquals('pendente', $event['artist_payment_status']);
        $this->assertEquals('pendente', $event['booker_payment_status']);
        $this->assertEquals('assinado', $event['contract_status']);
        $this->assertEquals('exceção autorizada para pagamento antecipado', $event['notes']);
        $this->assertTrue($event['can_pay_commission']);
        $this->assertTrue($event['is_exception']);
    }

    #[Test]
    public function it_gets_future_events_with_date_filter()
    {
        // Arrange
        $startDate = now()->addDays(5);
        $endDate = now()->addDays(15);

        $gigInPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(10),
        ]);

        $gigOutOfPeriod = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(20),
        ]);

        // Act
        $result = $this->service->getFutureEvents($this->booker, $startDate, $endDate);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($gigInPeriod->id, $result->first()['id']);
    }

    #[Test]
    public function it_detects_payment_exceptions_with_various_keywords()
    {
        // Arrange - Test different exception keywords
        $exceptionKeywords = [
            'exceção autorizada',
            'excecao justificada',
            'pagamento antecipado',
            'justificado pela diretoria',
            'autorizado pelo gestor',
            'aprovado excepcionalmente',
        ];

        foreach ($exceptionKeywords as $keyword) {
            $gig = Gig::factory()->create([
                'booker_id' => $this->booker->id,
                'artist_id' => $this->artist->id,
                'gig_date' => now()->addDays(10),
                'notes' => "Evento especial com {$keyword} devido às circunstâncias.",
            ]);

            // Act
            $result = $this->service->getFutureEvents($this->booker);
            $event = $result->where('id', $gig->id)->first();

            // Assert
            $this->assertTrue($event['is_exception'], "Failed to detect exception with keyword: {$keyword}");
            $this->assertTrue($event['can_pay_commission'], "Should allow commission payment with keyword: {$keyword}");
        }
    }

    #[Test]
    public function it_does_not_detect_payment_exception_without_keywords()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(10),
            'notes' => 'Evento normal sem palavras especiais.',
        ]);

        // Act
        $result = $this->service->getFutureEvents($this->booker);
        $event = $result->first();

        // Assert
        $this->assertFalse($event['is_exception']);
        $this->assertFalse($event['can_pay_commission']);
    }

    #[Test]
    public function it_does_not_detect_payment_exception_with_empty_notes()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(10),
            'notes' => null,
        ]);

        // Act
        $result = $this->service->getFutureEvents($this->booker);
        $event = $result->first();

        // Assert
        $this->assertFalse($event['is_exception']);
        $this->assertFalse($event['can_pay_commission']);
    }

    #[Test]
    public function it_allows_commission_payment_for_past_events_regardless_of_notes()
    {
        // Arrange
        $pastGig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(5),
            'notes' => 'Evento normal realizado conforme planejado',
        ]);

        // Act
        $result = $this->service->getRealizedEvents($this->booker);
        $event = $result->first();

        // Assert
        $this->assertTrue($event['can_pay_commission']);
        $this->assertFalse($event['is_exception']);
    }

    #[Test]
    public function it_calculates_financial_values_correctly_in_realized_events()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'gig_date' => now()->subDays(5),
            'booker_commission_type' => 'percentage',
            'booker_commission_rate' => 10.0,
            'agency_commission_type' => 'percentage',
            'agency_commission_rate' => 5.0,
        ]);

        // Act
        $result = $this->service->getRealizedEvents($this->booker);
        $event = $result->first();

        // Assert
        $this->assertArrayHasKey('booker_commission_brl', $event);
        $this->assertArrayHasKey('agency_commission_brl', $event);
        $this->assertArrayHasKey('total_costs_brl', $event);
        $this->assertIsNumeric($event['booker_commission_brl']);
        $this->assertIsNumeric($event['agency_commission_brl']);
        $this->assertIsNumeric($event['total_costs_brl']);
    }

    #[Test]
    public function it_handles_artist_name_gracefully_when_artist_is_deleted()
    {
        // Arrange
        $tempArtist = Artist::factory()->create();
        $gig = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $tempArtist->id,
            'gig_date' => now()->subDays(5),
        ]);

        // Delete the artist to simulate a missing relationship
        $tempArtist->delete();

        // Act
        $result = $this->service->getRealizedEvents($this->booker);
        $event = $result->first();

        // Assert
        $this->assertEquals('N/A', $event['artist_name']);
    }

    #[Test]
    public function it_orders_realized_events_by_gig_date_desc()
    {
        // Arrange
        $gig1 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(10),
        ]);

        $gig2 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(5),
        ]);

        $gig3 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->subDays(15),
        ]);

        // Act
        $result = $this->service->getRealizedEvents($this->booker);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals($gig2->id, $result->first()['id']); // Most recent first
        $this->assertEquals($gig3->id, $result->last()['id']); // Oldest last
    }

    #[Test]
    public function it_orders_future_events_by_gig_date_asc()
    {
        // Arrange
        $gig1 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(10),
        ]);

        $gig2 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(5),
        ]);

        $gig3 = Gig::factory()->create([
            'booker_id' => $this->booker->id,
            'artist_id' => $this->artist->id,
            'gig_date' => now()->addDays(15),
        ]);

        // Act
        $result = $this->service->getFutureEvents($this->booker);

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals($gig2->id, $result->first()['id']); // Nearest future first
        $this->assertEquals($gig3->id, $result->last()['id']); // Furthest future last
    }
}
