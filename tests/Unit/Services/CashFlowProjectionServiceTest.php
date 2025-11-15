<?php

namespace Tests\Unit\Services;

use App\Enums\AgencyCostType;
use App\Models\AgencyFixedCost;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Services\CashFlowProjectionService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashFlowProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CashFlowProjectionService $service;

    protected GigFinancialCalculatorService $calculator;

    protected Artist $artist;

    protected Booker $booker;

    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(GigFinancialCalculatorService::class);
        $this->artist = Artist::factory()->create();
        $this->booker = Booker::factory()->create();
        $this->costCenter = CostCenter::factory()->create();

        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-03-31');

        $dreService = new \App\Services\DreProjectionService($this->calculator);
        $this->service = new CashFlowProjectionService($dreService, $this->calculator);
        $this->service->setPeriod($startDate, $endDate);
    }

    #[Test]
    public function it_calculates_monthly_agency_costs_using_due_date()
    {
        // Criar custos com due_date em janeiro
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-02-01', // Competência em fevereiro
            'due_date' => '2025-01-10', // Mas vence em janeiro (CAIXA)
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-02-01', // Competência em fevereiro
            'due_date' => '2025-01-15', // Mas vence em janeiro (CAIXA)
            'monthly_value' => 500.00,
            'cost_type' => AgencyCostType::ADMINISTRATIVO,
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        // Deve aparecer em janeiro (due_date), não em fevereiro (reference_month)
        $this->assertTrue($result->has('2025-01'));
        $this->assertFalse($result->has('2025-02'));

        $janCosts = $result->get('2025-01');
        $this->assertEquals(1500.00, $janCosts['total_agency_costs']); // 1000 + 500
        $this->assertEquals(1000.00, $janCosts['total_operational_costs']);
        $this->assertEquals(500.00, $janCosts['total_administrative_costs']);
    }

    #[Test]
    public function it_segregates_costs_by_type_correctly()
    {
        // Criar 3 custos operacionais
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 1000.00,
            'cost_type' => 'operacional',
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-15',
            'monthly_value' => 2000.00,
            'cost_type' => 'operacional',
            'is_active' => true,
        ]);

        // Criar 2 custos administrativos
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-20',
            'monthly_value' => 1500.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-25',
            'monthly_value' => 500.00,
            'cost_type' => 'administrativo',
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        $janCosts = $result->get('2025-01');
        $this->assertEquals(3000.00, $janCosts['total_operational_costs']); // 1000 + 2000
        $this->assertEquals(2000.00, $janCosts['total_administrative_costs']); // 1500 + 500
        $this->assertEquals(5000.00, $janCosts['total_agency_costs']); // 3000 + 2000
        $this->assertEquals(4, $janCosts['cost_count']);
    }

    #[Test]
    public function it_excludes_inactive_costs()
    {
        // Custo ativo
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        // Custo inativo (não deve aparecer)
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-15',
            'monthly_value' => 5000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => false,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        $janCosts = $result->get('2025-01');
        $this->assertEquals(1000.00, $janCosts['total_agency_costs']); // Apenas o ativo
        $this->assertEquals(1, $janCosts['cost_count']);
    }

    #[Test]
    public function it_filters_costs_by_period()
    {
        // Custo dentro do período (jan-mar)
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-02-01',
            'due_date' => '2025-02-10',
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        // Custo ANTES do período (não deve aparecer)
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2024-12-01',
            'due_date' => '2024-12-10',
            'monthly_value' => 2000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        // Custo DEPOIS do período (não deve aparecer)
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-04-01',
            'due_date' => '2025-04-10',
            'monthly_value' => 3000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        // Apenas fevereiro deve aparecer
        $this->assertCount(1, $result);
        $this->assertTrue($result->has('2025-02'));
        $this->assertFalse($result->has('2024-12'));
        $this->assertFalse($result->has('2025-04'));

        $febCosts = $result->get('2025-02');
        $this->assertEquals(1000.00, $febCosts['total_agency_costs']);
    }

    #[Test]
    public function it_groups_costs_by_month_correctly()
    {
        // 2 custos em janeiro
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-20',
            'monthly_value' => 500.00,
            'cost_type' => AgencyCostType::ADMINISTRATIVO,
            'is_active' => true,
        ]);

        // 1 custo em fevereiro
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-02-01',
            'due_date' => '2025-02-15',
            'monthly_value' => 2000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        $this->assertCount(2, $result);

        $janCosts = $result->get('2025-01');
        $this->assertEquals(1500.00, $janCosts['total_agency_costs']);
        $this->assertEquals(2, $janCosts['cost_count']);

        $febCosts = $result->get('2025-02');
        $this->assertEquals(2000.00, $febCosts['total_agency_costs']);
        $this->assertEquals(1, $febCosts['cost_count']);
    }

    #[Test]
    public function it_includes_cost_details_in_result()
    {
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'description' => 'Aluguel do escritório',
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        $janCosts = $result->get('2025-01');
        $this->assertArrayHasKey('costs', $janCosts);
        $this->assertCount(1, $janCosts['costs']);

        $costDetail = $janCosts['costs'][0];
        $this->assertArrayHasKey('cost_id', $costDetail);
        $this->assertArrayHasKey('description', $costDetail);
        $this->assertArrayHasKey('cost_center', $costDetail);
        $this->assertArrayHasKey('due_date', $costDetail);
        $this->assertArrayHasKey('monthly_value', $costDetail);
        $this->assertArrayHasKey('cost_type', $costDetail);

        $this->assertEquals('Aluguel do escritório', $costDetail['description']);
        $this->assertEquals(1000.00, $costDetail['monthly_value']);
    }

    #[Test]
    public function it_integrates_agency_costs_into_monthly_cash_flow()
    {
        // Criar um gig
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 10000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // R$ 2000
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 500.00,
        ]);

        // Criar custos operacionais
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-20',
            'monthly_value' => 500.00,
            'cost_type' => AgencyCostType::ADMINISTRATIVO,
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyCashFlow();

        $janCashFlow = $result->firstWhere('year_month', '2025-01');

        // Verificar que custos operacionais estão incluídos
        $this->assertArrayHasKey('total_agency_costs', $janCashFlow);
        $this->assertEquals(1500.00, $janCashFlow['total_agency_costs']); // 1000 + 500

        // Verificar que total_outflow inclui agency costs
        $this->assertArrayHasKey('total_outflow', $janCashFlow);
        $expectedTotalOutflow = $janCashFlow['total_gig_outflow'] + 1500.00;
        $this->assertEquals($expectedTotalOutflow, $janCashFlow['total_outflow']);

        // Verificar que net_cash_flow está correto
        $expectedNetCashFlow = $janCashFlow['total_inflow'] - $janCashFlow['total_outflow'];
        $this->assertEquals($expectedNetCashFlow, $janCashFlow['net_cash_flow']);

        // Verificar detalhes dos custos operacionais
        $this->assertArrayHasKey('agency_cost_details', $janCashFlow);
        $agencyCostDetails = $janCashFlow['agency_cost_details'];
        $this->assertEquals(1000.00, $agencyCostDetails['total_operational_costs']);
        $this->assertEquals(500.00, $agencyCostDetails['total_administrative_costs']);
    }

    #[Test]
    public function it_handles_months_without_agency_costs()
    {
        // Criar apenas um gig em janeiro, sem custos operacionais
        Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => Carbon::parse('2025-01-15'),
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0,
        ]);

        $result = $this->service->calculateMonthlyCashFlow();

        $janCashFlow = $result->firstWhere('year_month', '2025-01');

        // Deve ter valores zerados para agency costs
        $this->assertEquals(0.00, $janCashFlow['total_agency_costs']);
        $this->assertEquals(0.00, $janCashFlow['agency_cost_details']['total_operational_costs']);
        $this->assertEquals(0.00, $janCashFlow['agency_cost_details']['total_administrative_costs']);
    }

    #[Test]
    public function it_returns_sorted_months()
    {
        // Criar custos em ordem não sequencial
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-03-01',
            'due_date' => '2025-03-10',
            'monthly_value' => 1000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-01-01',
            'due_date' => '2025-01-10',
            'monthly_value' => 2000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $this->costCenter->id,
            'reference_month' => '2025-02-01',
            'due_date' => '2025-02-10',
            'monthly_value' => 3000.00,
            'cost_type' => AgencyCostType::OPERACIONAL,
            'is_active' => true,
        ]);

        $result = $this->service->calculateMonthlyAgencyCosts();

        // Verificar que os meses estão ordenados
        $months = $result->keys()->toArray();
        $this->assertEquals(['2025-01', '2025-02', '2025-03'], $months);
    }
}
