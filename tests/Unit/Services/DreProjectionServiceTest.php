<?php

namespace Tests\Unit\Services;

use App\Models\AgencyFixedCost;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Services\DreProjectionService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DreProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DreProjectionService $service;

    protected GigFinancialCalculatorService $calculator;

    protected Artist $artist;

    protected Booker $booker;

    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(GigFinancialCalculatorService::class);
        $this->service = new DreProjectionService($this->calculator);
        $this->artist = Artist::factory()->create();
        $this->booker = Booker::factory()->create();
        $this->costCenter = CostCenter::factory()->create();
    }

    #[Test]
    public function it_sets_period_correctly()
    {
        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        $result = $this->service->setPeriod($startDate, $endDate);

        $this->assertInstanceOf(DreProjectionService::class, $result);

        // Verifica que o período foi setado verificando no cálculo total
        $totalDre = $this->service->calculateTotalDre();
        $this->assertEquals('2025-01-01', $totalDre['period']['start']);
        $this->assertEquals('2025-01-31', $totalDre['period']['end']);
        $this->assertTrue($totalDre['period']['days'] >= 30 && $totalDre['period']['days'] <= 32); // Tolerância para precisão
    }

    #[Test]
    public function it_calculates_receita_liquida_real_agencia()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // R$ 200
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 50.00, // R$ 50
        ]);

        $result = $this->service->calculateReceitaLiquidaRealAgencia($gig);

        // RLRA = Comissão Agência - Comissão Booker = 200 - 50 = 150
        $this->assertEquals(150.00, $result);
    }

    #[Test]
    public function it_returns_event_metrics_correctly()
    {
        $gigDate = Carbon::parse('2025-01-15');

        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => $gigDate,
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // R$ 1000
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 300.00, // R$ 300
        ]);

        $metrics = $this->service->getEventMetrics($gig);

        $this->assertIsArray($metrics);
        $this->assertEquals($gig->id, $metrics['gig_id']);
        $this->assertEquals('2025-01-15', $metrics['gig_date']);
        $this->assertEquals($this->artist->name, $metrics['artist_name']);
        $this->assertEquals($this->booker->name, $metrics['booker_name']);
        $this->assertEquals(5000.00, $metrics['contract_value_brl']);
        $this->assertEquals(1000.00, $metrics['receita_bruta_agencia']);
        $this->assertEquals(300.00, $metrics['custo_booker']);
        $this->assertEquals(700.00, $metrics['receita_liquida_real_agencia']); // 1000 - 300
        $this->assertArrayHasKey('margin_percentage', $metrics);
    }

    #[Test]
    public function it_groups_events_by_month()
    {
        // Criar gigs em diferentes meses
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-10'),
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-20'),
            'cache_value' => 2000.00,
            'currency' => 'BRL',
        ]);

        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-02-15'),
            'cache_value' => 3000.00,
            'currency' => 'BRL',
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-02-28')
        );

        $grouped = $this->service->getEventsGroupedByMonth();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $grouped);
        $this->assertCount(2, $grouped); // Janeiro e Fevereiro
        $this->assertTrue($grouped->has('2025-01'));
        $this->assertTrue($grouped->has('2025-02'));
        $this->assertCount(2, $grouped->get('2025-01')); // 2 gigs em janeiro
        $this->assertCount(1, $grouped->get('2025-02')); // 1 gig em fevereiro
    }

    #[Test]
    public function it_calculates_monthly_dre_without_fixed_costs()
    {
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 10000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // R$ 2000
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 500.00, // R$ 500
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $monthlyDre = $this->service->calculateMonthlyDre();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $monthlyDre);
        $this->assertCount(1, $monthlyDre);

        $janDre = $monthlyDre->get('2025-01');
        $this->assertEquals('2025-01', $janDre['year_month']);
        $this->assertEquals(1, $janDre['event_count']);
        $this->assertEquals(10000.00, $janDre['total_cachee_liquido']);
        $this->assertEquals(2000.00, $janDre['total_receita_bruta_agencia']);
        $this->assertEquals(500.00, $janDre['total_custo_booker']);
        $this->assertEquals(1500.00, $janDre['total_receita_liquida_real_agencia']); // 2000 - 500
        $this->assertEquals(0.00, $janDre['custo_fixo_medio']); // Sem custos fixos
        $this->assertEquals(1500.00, $janDre['resultado_operacional']); // RLRA - CFM
    }

    #[Test]
    public function it_calculates_monthly_dre_with_fixed_costs()
    {
        // Criar gig
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 10000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // R$ 2000
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 500.00, // R$ 500
        ]);

        // Criar custos fixos operacionais
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 2000.00,
            'cost_type' => 'operacional',
            'is_active' => true,
        ]);

        // Criar custos fixos administrativos
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-15',
            'monthly_value' => 3000.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $monthlyDre = $this->service->calculateMonthlyDre();

        $janDre = $monthlyDre->get('2025-01');
        $this->assertEquals(2000.00, $janDre['custo_operacional']);
        $this->assertEquals(3000.00, $janDre['custo_administrativo']);
        $this->assertEquals(5000.00, $janDre['custo_fixo_medio']); // 2000 + 3000
        $this->assertEquals(-3500.00, $janDre['resultado_operacional']); // 1500 (RLRA) - 5000 (CFM)
    }

    #[Test]
    public function it_calculates_total_dre_for_period()
    {
        // Janeiro
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0,
        ]);

        // Fevereiro
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-02-10'),
            'cache_value' => 8000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0,
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-02-28')
        );

        $totalDre = $this->service->calculateTotalDre();

        $this->assertIsArray($totalDre);
        $this->assertArrayHasKey('period', $totalDre);
        $this->assertArrayHasKey('totals', $totalDre);
        $this->assertArrayHasKey('monthly_breakdown', $totalDre);

        $this->assertEquals('2025-01-01', $totalDre['period']['start']);
        $this->assertEquals('2025-02-28', $totalDre['period']['end']);
        $this->assertEquals(2, $totalDre['totals']['event_count']);
        $this->assertEquals(13000.00, $totalDre['totals']['total_cachee_liquido']); // 5000 + 8000
    }

    #[Test]
    public function it_calculates_ticket_medio()
    {
        // Criar 3 gigs com valores diferentes
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-10'),
            'cache_value' => 2000.00,
            'currency' => 'BRL',
        ]);

        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 4000.00,
            'currency' => 'BRL',
        ]);

        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-20'),
            'cache_value' => 6000.00,
            'currency' => 'BRL',
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $ticketMedio = $this->service->calculateTicketMedio();

        // Ticket Médio = (2000 + 4000 + 6000) / 3 = 4000
        $this->assertEquals(4000.00, $ticketMedio);
    }

    #[Test]
    public function it_returns_zero_ticket_medio_with_no_gigs()
    {
        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $ticketMedio = $this->service->calculateTicketMedio();

        $this->assertEquals(0.00, $ticketMedio);
    }

    #[Test]
    public function it_calculates_break_even_point()
    {
        // Criar gig
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 10000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // R$ 2000
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 500.00, // R$ 500
        ]);

        // Criar custos fixos totais
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 6000.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $breakEven = $this->service->calculateBreakEvenPoint();

        // Break Even = CFM médio mensal = 6000
        $this->assertEquals(6000.00, $breakEven);
    }

    #[Test]
    public function it_returns_zero_break_even_without_gigs()
    {
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 5000.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $breakEven = $this->service->calculateBreakEvenPoint();

        // Sem gigs no período, DRE vazio, break even = 0
        $this->assertEquals(0.00, $breakEven);
    }

    #[Test]
    public function it_returns_executive_summary()
    {
        // Criar gig
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 10000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0,
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 500.00,
        ]);

        // Criar custos fixos
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 3000.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $summary = $this->service->getExecutiveSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('periodo', $summary);
        $this->assertArrayHasKey('kpis', $summary);
        $this->assertArrayHasKey('dre_mensal', $summary);

        // Verificar KPIs
        $kpis = $summary['kpis'];
        $this->assertEquals(1, $kpis['total_eventos']);
        $this->assertEquals(10000.00, $kpis['ticket_medio']);
        $this->assertEquals(3000.00, $kpis['ponto_equilibrio_mensal']);
        $this->assertEquals(1500.00, $kpis['margem_contribuicao_total']); // 2000 - 500
        $this->assertEquals(-1500.00, $kpis['resultado_operacional']); // 1500 - 3000
        $this->assertEquals('deficitario', $kpis['status_financeiro']);
    }

    #[Test]
    public function it_excludes_inactive_fixed_costs()
    {
        // Criar custo ativo
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 2000.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        // Criar custo inativo (deve ser ignorado)
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-15',
            'monthly_value' => 5000.00,
            'cost_type' => 'administrativo',
            'is_active' => false,
        ]);

        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        $this->service->setPeriod(
            Carbon::parse('2025-01-01'),
            Carbon::parse('2025-01-31')
        );

        $monthlyDre = $this->service->calculateMonthlyDre();
        $janDre = $monthlyDre->get('2025-01');

        // Apenas o custo ativo deve ser contabilizado
        $this->assertEquals(2000.00, $janDre['custo_fixo_medio']);
    }
}
