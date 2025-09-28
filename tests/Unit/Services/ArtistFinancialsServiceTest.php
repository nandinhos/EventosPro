<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Gig;
use App\Services\ArtistFinancialsService;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistFinancialsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ArtistFinancialsService $artistFinancialsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artistFinancialsService = app(ArtistFinancialsService::class);
    }

    /** @test */
    public function it_calculates_financial_metrics_for_artist_with_no_gigs()
    {
        $artist = Artist::factory()->create();

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        $this->assertEquals(0, $result['total_gigs']);
        $this->assertEquals(0, $result['cache_received_brl']);
        $this->assertEquals(0, $result['cache_pending_brl']);
    }

    /** @test */
    public function it_calculates_financial_metrics_for_artist_with_paid_gigs()
    {
        $artist = Artist::factory()->create();

        // Criar gigs com status 'pago'
        Gig::factory()->count(2)->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pago',
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        $this->assertEquals(2, $result['total_gigs']);
        $this->assertGreaterThan(0, $result['cache_received_brl']);
        $this->assertEquals(0, $result['cache_pending_brl']);
    }

    /** @test */
    public function it_calculates_financial_metrics_for_artist_with_pending_gigs()
    {
        $artist = Artist::factory()->create();

        // Criar gigs com status 'pendente'
        Gig::factory()->count(3)->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pendente',
            'cache_value' => 1500.00,
            'currency' => 'BRL',
        ]);

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        $this->assertEquals(3, $result['total_gigs']);
        $this->assertEquals(0, $result['cache_received_brl']);
        $this->assertGreaterThan(0, $result['cache_pending_brl']);
    }

    /** @test */
    public function it_calculates_financial_metrics_for_artist_with_mixed_payment_status()
    {
        $artist = Artist::factory()->create();

        // Criar gigs com status 'pago'
        Gig::factory()->count(2)->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pago',
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        // Criar gigs com status 'pendente'
        Gig::factory()->count(3)->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pendente',
            'cache_value' => 1500.00,
            'currency' => 'BRL',
        ]);

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        $this->assertEquals(5, $result['total_gigs']);
        $this->assertGreaterThan(0, $result['cache_received_brl']);
        $this->assertGreaterThan(0, $result['cache_pending_brl']);
    }

    /** @test */
    public function it_calculates_financial_metrics_with_pre_filtered_gigs_collection()
    {
        $artist = Artist::factory()->create();

        // Criar mais gigs do que vamos passar para o método
        Gig::factory()->count(5)->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pago',
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        // Pegar apenas 2 gigs para testar a funcionalidade de coleção pré-filtrada
        $filteredGigs = $artist->gigs()->take(2)->get();

        $result = $this->artistFinancialsService->getFinancialMetrics($artist, $filteredGigs);

        // Deve considerar apenas as 2 gigs filtradas, não todas as 5
        $this->assertEquals(2, $result['total_gigs']);
        $this->assertGreaterThan(0, $result['cache_received_brl']);
        $this->assertEquals(0, $result['cache_pending_brl']);
    }

    /** @test */
    public function it_returns_correct_structure_for_financial_metrics()
    {
        $artist = Artist::factory()->create();

        Gig::factory()->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pago',
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        // Verificar estrutura do resultado
        $this->assertArrayHasKey('total_gigs', $result);
        $this->assertArrayHasKey('cache_received_brl', $result);
        $this->assertArrayHasKey('cache_pending_brl', $result);

        // Verificar tipos de dados
        $this->assertIsInt($result['total_gigs']);
        $this->assertIsNumeric($result['cache_received_brl']);
        $this->assertIsNumeric($result['cache_pending_brl']);
    }

    /** @test */
    public function it_handles_gigs_with_different_currencies()
    {
        $artist = Artist::factory()->create();

        // Criar gigs em moedas diferentes
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pago',
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        Gig::factory()->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pendente',
            'cache_value' => 800.00,
            'currency' => 'EUR',
        ]);

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        $this->assertEquals(2, $result['total_gigs']);
        // Os valores devem ser convertidos para BRL pelo GigFinancialCalculatorService
        $this->assertIsNumeric($result['cache_received_brl']);
        $this->assertIsNumeric($result['cache_pending_brl']);
    }

    /** @test */
    public function it_handles_gigs_with_null_or_unknown_payment_status()
    {
        $artist = Artist::factory()->create();

        // Criar gig com status pendente
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'pendente',
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        // Criar gig com status desconhecido (deve ser tratado como pendente)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'artist_payment_status' => 'unknown_status',
            'cache_value' => 1500.00,
            'currency' => 'BRL',
        ]);

        $result = $this->artistFinancialsService->getFinancialMetrics($artist);

        $this->assertEquals(2, $result['total_gigs']);
        $this->assertEquals(0, $result['cache_received_brl']);
        $this->assertGreaterThan(0, $result['cache_pending_brl']);
    }
}
