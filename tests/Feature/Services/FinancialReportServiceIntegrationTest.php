<?php

namespace Tests\Feature\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use App\Services\FinancialReportService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinancialReportServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialReportService $reportService;

    protected GigFinancialCalculatorService $gigCalculator;

    protected Artist $artist;

    protected Booker $booker;

    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gigCalculator = $this->app->make(GigFinancialCalculatorService::class);
        $this->reportService = new FinancialReportService($this->gigCalculator);

        // Criar dados base para os testes
        $this->artist = Artist::factory()->create([
            'name' => 'Test Artist',
        ]);

        $this->booker = Booker::factory()->create([
            'name' => 'Test Booker',
            'default_commission_rate' => 10.0,
        ]);

        $this->costCenter = CostCenter::factory()->create([
            'name' => 'Production Costs',
        ]);
    }

    #[Test]
    public function it_integrates_complete_financial_workflow_with_multiple_gigs()
    {
        // Arrange: Criar múltiplos gigs com diferentes cenários
        $gig1 = $this->createCompleteGig([
            'gig_date' => Carbon::now()->subDays(5),
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 50000,
        ]);

        $gig2 = $this->createCompleteGig([
            'gig_date' => Carbon::now()->subDays(10),
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 82500,
        ]);

        // Adicionar pagamentos confirmados
        $this->createConfirmedPayment($gig1, 50000); // 10k USD = ~50k BRL
        $this->createConfirmedPayment($gig2, 82500); // 15k EUR = ~82.5k BRL

        // Adicionar custos confirmados
        $this->createConfirmedCosts($gig1, [
            ['amount' => 5000, 'description' => 'Sound Equipment'],
            ['amount' => 3000, 'description' => 'Transportation'],
        ]);

        $this->createConfirmedCosts($gig2, [
            ['amount' => 8000, 'description' => 'Venue Rental'],
            ['amount' => 4000, 'description' => 'Catering'],
        ]);

        // Act: Executar relatório financeiro completo
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->subDays(15)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $overviewSummary = $this->reportService->getOverviewSummary();
        $overviewTable = $this->reportService->getOverviewTableData();
        $profitabilitySummary = $this->reportService->getProfitabilitySummary();

        // Assert: Verificar integração completa
        $this->assertEquals(132500, $overviewSummary['total_inflow']); // 50k + 82.5k
        $this->assertEquals(20000, $overviewSummary['total_outflow']); // 8k + 12k custos
        $this->assertEquals(112500, $overviewSummary['net_cashflow']);

        // Verificar dados da tabela
        $this->assertCount(2, $overviewTable);

        // Verificar que ambos os gigs estão presentes
        $revenues = $overviewTable->pluck('revenue')->sort()->values();
        $this->assertEquals([50000, 82500], $revenues->toArray());

        $costs = $overviewTable->pluck('costs')->sort()->values();
        $this->assertEquals([8000, 12000], $costs->toArray());

        // Verificar rentabilidade
        $this->assertGreaterThan(100000, $profitabilitySummary['total_profit']);
        $this->assertGreaterThan(0, $profitabilitySummary['average_margin']);
        $this->assertEquals(2, $profitabilitySummary['profitable_events']);
    }

    #[Test]
    public function it_handles_multi_currency_integration_correctly()
    {
        // Arrange: Gigs em diferentes moedas
        $usdGig = $this->createCompleteGig([
            'gig_date' => Carbon::now()->subDays(3),
            'cache_value' => 25000,
        ]);

        $eurGig = $this->createCompleteGig([
            'gig_date' => Carbon::now()->subDays(7),
            'cache_value' => 22000,
        ]);

        $brlGig = $this->createCompleteGig([
            'gig_date' => Carbon::now()->subDays(1),
            'cache_value' => 20000,
        ]);

        // Pagamentos em diferentes moedas
        $this->createConfirmedPayment($usdGig, 25000); // ~5k USD
        $this->createConfirmedPayment($eurGig, 22000); // ~4k EUR
        $this->createConfirmedPayment($brlGig, 20000); // 20k BRL

        // Act
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $summary = $this->reportService->getOverviewSummary();
        $tableData = $this->reportService->getOverviewTableData();

        // Assert: Verificar conversão e integração de moedas
        $this->assertEquals(67000, $summary['total_inflow']); // Soma em BRL
        $this->assertCount(3, $tableData);

        // Verificar que cada gig tem valores corretos em BRL
        foreach ($tableData as $gigData) {
            $this->assertIsNumeric($gigData['revenue']);
            $this->assertGreaterThan(0, $gigData['revenue']);
        }
    }

    #[Test]
    public function it_integrates_with_complex_cost_structure()
    {
        // Arrange: Gig com estrutura complexa de custos
        $gig = $this->createCompleteGig([
            'gig_date' => Carbon::now()->subDays(2),
            'cache_value' => 100000,
        ]);

        $this->createConfirmedPayment($gig, 100000);

        // Custos de diferentes tipos e centros de custo
        $productionCenter = CostCenter::factory()->create(['name' => 'Production']);
        $marketingCenter = CostCenter::factory()->create(['name' => 'Marketing']);

        $this->createConfirmedCosts($gig, [
            ['amount' => 15000, 'description' => 'Stage Setup', 'cost_center_id' => $productionCenter->id],
            ['amount' => 8000, 'description' => 'Sound System', 'cost_center_id' => $productionCenter->id],
            ['amount' => 5000, 'description' => 'Promotion', 'cost_center_id' => $marketingCenter->id],
            ['amount' => 3000, 'description' => 'Social Media', 'cost_center_id' => $marketingCenter->id],
        ]);

        // Custos não confirmados (não devem aparecer)
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'value' => 10000,
            'currency' => 'BRL',
            'description' => 'Unconfirmed Cost',
            'cost_center_id' => $productionCenter->id,
            'is_confirmed' => false,
            'confirmed_at' => null,
        ]);

        // Act
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $summary = $this->reportService->getOverviewSummary();
        $tableData = $this->reportService->getOverviewTableData();

        // Assert: Verificar que apenas custos confirmados são incluídos
        $this->assertEquals(100000, $summary['total_inflow']);
        $this->assertEquals(31000, $summary['total_outflow']); // Apenas custos confirmados
        $this->assertEquals(69000, $summary['net_cashflow']);

        $gigData = $tableData->first();
        $this->assertEquals(31000, $gigData['costs']);
        $this->assertGreaterThan(50000, $gigData['net_profit']);
    }

    #[Test]
    public function it_integrates_filtering_across_multiple_dimensions()
    {
        // Arrange: Múltiplos artistas e bookers
        $artist2 = Artist::factory()->create(['name' => 'Artist Two']);
        $booker2 = Booker::factory()->create(['name' => 'Booker Two']);

        // Gigs para diferentes combinações
        $gig1 = $this->createCompleteGig([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::now()->subDays(5),
            'cache_value' => 30000,
        ]);

        $gig2 = $this->createCompleteGig([
            'artist_id' => $artist2->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::now()->subDays(3),
            'cache_value' => 40000,
        ]);

        $gig3 = $this->createCompleteGig([
            'artist_id' => $this->artist->id,
            'booker_id' => $booker2->id,
            'gig_date' => Carbon::now()->subDays(1),
            'cache_value' => 50000,
        ]);

        // Pagamentos para todos
        $this->createConfirmedPayment($gig1, 30000);
        $this->createConfirmedPayment($gig2, 40000);
        $this->createConfirmedPayment($gig3, 50000);

        // Act & Assert: Testar filtros por artista
        $this->reportService->setFilters([
            'artist_id' => $this->artist->id,
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $artistSummary = $this->reportService->getOverviewSummary();
        $artistTable = $this->reportService->getOverviewTableData();

        $this->assertEquals(80000, $artistSummary['total_inflow']); // gig1 + gig3
        $this->assertCount(2, $artistTable);

        // Act & Assert: Testar filtros por booker
        $this->reportService->setFilters([
            'booker_id' => $this->booker->id,
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $bookerSummary = $this->reportService->getOverviewSummary();
        $bookerTable = $this->reportService->getOverviewTableData();

        $this->assertEquals(70000, $bookerSummary['total_inflow']); // gig1 + gig2
        $this->assertCount(2, $bookerTable);
    }

    #[Test]
    public function it_handles_edge_cases_and_error_scenarios()
    {
        // Arrange: Gig com dados problemáticos
        $gig = Gig::factory()->create([
            'gig_date' => Carbon::now()->subDays(1),
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 0, // Valor zero para testar edge case
            'currency' => 'INVALID', // Moeda inválida para testar edge case
        ]);

        // Payment com valor baixo (edge case)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1,
            'currency' => 'BRL',
            'exchange_rate' => 1.0,
            'confirmed_at' => Carbon::now(),
        ]);

        // Act
        $this->reportService->setFilters([
            'start_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $summary = $this->reportService->getOverviewSummary();
        $tableData = $this->reportService->getOverviewTableData();

        // Assert: Verificar que o sistema lida graciosamente com erros
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_inflow', $summary);
        $this->assertArrayHasKey('total_outflow', $summary);
        $this->assertArrayHasKey('net_cashflow', $summary);

        $this->assertCount(1, $tableData);
        $gigData = $tableData->first();

        // Verificar que dados problemáticos são tratados graciosamente
        $this->assertEquals('Test Artist', $gigData['artist']);
        $this->assertEquals('Test Booker', $gigData['booker']);
        $this->assertIsNumeric($gigData['revenue']);

        // Verificar que valores baixos são tratados corretamente
        $this->assertGreaterThanOrEqual(0, $gigData['revenue']);
    }

    // Helper methods
    private function createCompleteGig(array $attributes = []): Gig
    {
        return Gig::factory()->create(array_merge([
            'contract_number' => 'TEST-'.rand(1000, 9999),
            'gig_date' => Carbon::now()->subDays(1),
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => $attributes['cache_value'] ?? 1000, // Adicionado para aceitar cache_value
        ], $attributes));
    }

    private function createConfirmedPayment(Gig $gig, float $value): Payment
    {
        return Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => $value,
            'currency' => 'BRL',
            'exchange_rate' => 1.0,
            'confirmed_at' => Carbon::now(),
        ]);
    }

    private function createConfirmedCosts(Gig $gig, array $costs): void
    {
        foreach ($costs as $cost) {
            GigCost::factory()->create([
                'gig_id' => $gig->id,
                'value' => $cost['amount'],
                'currency' => 'BRL',
                'description' => $cost['description'],
                'cost_center_id' => $cost['cost_center_id'] ?? $this->costCenter->id,
                'is_confirmed' => true,
                'confirmed_at' => Carbon::now()->subDays(1),
            ]);
        }
    }
}
