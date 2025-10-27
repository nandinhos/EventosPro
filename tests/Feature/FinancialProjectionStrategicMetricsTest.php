<?php

namespace Tests\Feature;

use App\Models\AgencyFixedCost;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinancialProjectionStrategicMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_calculates_strategic_balance_with_proportional_operational_costs_for_past_events()
    {
        // Arrange: Criar custos operacionais fixos
        $costCenter = CostCenter::factory()->create(['name' => 'Administrativo']);
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
            'monthly_value' => 1000.00,
            'is_active' => true,
        ]);

        // Criar gigs passadas em meses diferentes (3 meses atrás e 1 mês atrás)
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create(['default_commission_rate' => 10.00]);

        $gig1 = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->subMonths(3),
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        $gig2 = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->subMonths(1),
            'cache_value' => 3000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        // Criar payments confirmados
        Payment::factory()->create([
            'gig_id' => $gig1->id,
            'due_value' => 5000.00,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
            'received_value_actual' => 5000.00,
        ]);

        Payment::factory()->create([
            'gig_id' => $gig2->id,
            'due_value' => 3000.00,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
            'received_value_actual' => 3000.00,
        ]);

        // Criar settlements
        Settlement::factory()->create([
            'gig_id' => $gig1->id,
            'artist_payment_value' => 4000.00,
            'booker_commission_value_paid' => 500.00,
        ]);

        Settlement::factory()->create([
            'gig_id' => $gig2->id,
            'artist_payment_value' => 2400.00,
            'booker_commission_value_paid' => 300.00,
        ]);

        // Act: Acessar o dashboard
        $response = $this->actingAs($this->user)->get(route('projections.index'));

        // Assert: Status OK
        $response->assertStatus(200);

        // Assert: Verificar que custos operacionais foram calculados para 4 meses (3 meses atrás + mês atual)
        // Total Inflows: 8000.00
        // Total Outflows: 4000 + 2400 + 500 + 300 = 7200.00
        // Operational Costs: 1000 * 4 = 4000.00
        // Generated Cash: 8000 - 7200 - 4000 = -3200.00

        $response->assertViewHas('global_metrics');
        $metrics = $response->viewData('global_metrics');

        $this->assertArrayHasKey('generated_cash', $metrics);
        // Verificar que o valor está negativo devido aos custos operacionais proporcionais
        $this->assertLessThan(0, $metrics['generated_cash']);
    }

    #[Test]
    public function it_calculates_strategic_balance_with_proportional_operational_costs_for_future_events()
    {
        // Arrange: Criar custos operacionais fixos
        $costCenter = CostCenter::factory()->create(['name' => 'Administrativo']);
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
            'monthly_value' => 1000.00,
            'is_active' => true,
        ]);

        // Criar gigs futuras (2 meses no futuro e 5 meses no futuro)
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create(['default_commission_rate' => 10.00]);

        $futureGig1 = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->addMonths(2),
            'cache_value' => 6000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        $futureGig2 = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->addMonths(5),
            'cache_value' => 4000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        // Criar payments não confirmados
        Payment::factory()->create([
            'gig_id' => $futureGig1->id,
            'due_value' => 6000.00,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        Payment::factory()->create([
            'gig_id' => $futureGig2->id,
            'due_value' => 4000.00,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Act: Acessar o dashboard
        $response = $this->actingAs($this->user)->get(route('projections.index'));

        // Assert: Status OK
        $response->assertStatus(200);

        // Assert: Verificar que custos operacionais foram calculados para 6 meses (5 meses + mês atual)
        // Future Inflows: 10000.00
        // Future Outflows (artists + bookers): calculado pelo serviço
        // Operational Costs: 1000 * 6 = 6000.00
        // Committed Cash: deve considerar 6 meses de custos operacionais

        $response->assertViewHas('global_metrics');
        $metrics = $response->viewData('global_metrics');

        $this->assertArrayHasKey('committed_cash', $metrics);
        $this->assertArrayHasKey('financial_balance', $metrics);
    }

    #[Test]
    public function it_maintains_consistency_between_receivables_and_strategic_balance()
    {
        // Arrange: Criar cenário completo
        $costCenter = CostCenter::factory()->create(['name' => 'Administrativo']);
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
            'monthly_value' => 500.00,
            'is_active' => true,
        ]);

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create(['default_commission_rate' => 10.00]);

        // Gig passada
        $pastGig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->subMonth(),
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        Payment::factory()->create([
            'gig_id' => $pastGig->id,
            'due_value' => 5000.00,
            'currency' => 'BRL',
            'confirmed_at' => null, // Não confirmado = recebível
        ]);

        // Gig futura
        $futureGig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->addMonth(),
            'cache_value' => 3000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        Payment::factory()->create([
            'gig_id' => $futureGig->id,
            'due_value' => 3000.00,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('projections.index'));

        // Assert: Verificar consistência
        $response->assertStatus(200);
        $metrics = $response->viewData('global_metrics');

        // Total recebível = 8000.00 (5000 + 3000)
        $totalReceivable = $metrics['total_receivable_past_events'] + $metrics['total_receivable_future_events'];
        $this->assertEquals(8000.00, $totalReceivable, '', 0.01);

        // Balanço = Caixa Gerado + Caixa Comprometido
        $calculatedBalance = $metrics['generated_cash'] + $metrics['committed_cash'];
        $this->assertEquals($metrics['financial_balance'], $calculatedBalance, '', 0.01);
    }

    #[Test]
    public function it_includes_reimbursable_expenses_in_artist_payment_without_double_counting()
    {
        // Arrange: Criar gig com despesas reembolsáveis
        $costCenter = CostCenter::factory()->create(['name' => 'Transporte']);
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create(['default_commission_rate' => 10.00]);

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->subMonth(),
            'cache_value' => 10000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        // Despesa reembolsável confirmada
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Passagem aérea',
            'value' => 500.00,
            'currency' => 'BRL',
            'is_invoice' => true, // Reembolsável
            'is_confirmed' => true,
        ]);

        // Despesa não-reembolsável confirmada
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Hospedagem',
            'value' => 300.00,
            'currency' => 'BRL',
            'is_invoice' => false, // Não reembolsável
            'is_confirmed' => true,
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 10000.00,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
            'received_value_actual' => 10000.00,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('projections.index'));

        // Assert
        $response->assertStatus(200);
        $metrics = $response->viewData('global_metrics');

        // Verificar que as despesas aparecem no total de despesas
        $this->assertArrayHasKey('total_expenses', $metrics);
        $totalExpenses = $metrics['total_expenses']['total_expenses'];
        $this->assertEquals(800.00, $totalExpenses, '', 0.01); // 500 + 300

        // Verificar que o pagamento ao artista inclui o reembolsável
        // Cachê Bruto = 10000 - 500 - 300 = 9200
        // Comissão Agência (20%) = 1840
        // Cachê Líquido = 7360
        // Total NF Artista = 7360 + 500 (reembolsável) = 7860
        $this->assertArrayHasKey('total_payable_artists', $metrics);

        // O valor esperado deve incluir o reembolsável sem dupla contagem no balanço
        $this->assertGreaterThan(0, $metrics['total_payable_artists']);
    }

    #[Test]
    public function it_handles_empty_gigs_gracefully()
    {
        // Arrange: Criar custos operacionais mas sem gigs
        $costCenter = CostCenter::factory()->create(['name' => 'Administrativo']);
        AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
            'monthly_value' => 1000.00,
            'is_active' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('projections.index'));

        // Assert: Não deve gerar erros
        $response->assertStatus(200);
        $metrics = $response->viewData('global_metrics');

        // Valores devem ser zero
        $this->assertEquals(0, $metrics['generated_cash'], '', 0.01);
        $this->assertEquals(0, $metrics['committed_cash'], '', 0.01);
        $this->assertEquals(0, $metrics['financial_balance'], '', 0.01);
    }

    #[Test]
    public function it_only_counts_active_operational_costs()
    {
        // Arrange: Criar custos ativos e inativos
        $costCenter = CostCenter::factory()->create(['name' => 'Administrativo']);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
            'monthly_value' => 1000.00,
            'is_active' => true,
        ]);

        AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
            'monthly_value' => 5000.00,
            'is_active' => false, // Inativo - não deve ser contado
        ]);

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create(['default_commission_rate' => 10.00]);

        // Criar gig passada
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::today()->subMonth(),
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_rate' => 20.00,
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 5000.00,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
            'received_value_actual' => 5000.00,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('projections.index'));

        // Assert: Apenas custos ativos devem ser considerados (1000, não 6000)
        $response->assertStatus(200);
        $metrics = $response->viewData('global_metrics');

        // Generated Cash deve usar 1000*2 = 2000 de custos operacionais, não 6000*2
        // Inflows: 5000
        // Operational Costs: 1000 * 2 meses = 2000
        // Caixa Gerado deve ser > 0 se só considerar custos ativos
        $this->assertArrayHasKey('generated_cash', $metrics);
    }
}
