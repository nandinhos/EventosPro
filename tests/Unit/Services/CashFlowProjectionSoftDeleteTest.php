<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use App\Services\CashFlowProjectionService;
use App\Services\DreProjectionService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teste para garantir que CashFlowProjectionService respeita SoftDeletes.
 *
 * CRÍTICO: O sistema usa SoftDeletes e os cálculos NÃO PODEM incluir
 * registros deletados, pois isso causaria erros nos valores financeiros.
 */
class CashFlowProjectionSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private CashFlowProjectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $dreService = app(DreProjectionService::class);
        $gigCalculatorService = $this->createMock(GigFinancialCalculatorService::class);

        // Configura o mock para retornar valores esperados
        $gigCalculatorService->method('calculateArtistNetPayoutBrl')->willReturn(160.0);
        $gigCalculatorService->method('calculateBookerCommissionBrl')->willReturn(50.0);
        $gigCalculatorService->method('calculateGrossCashBrl')->willReturn(800.0); // Valor base para o cachê líquido

        $this->service = new CashFlowProjectionService($dreService, $gigCalculatorService);
    }

    /**
     * Testa que Payments de Gigs deletados NÃO são incluídos em calculateMonthlyInflows.
     */
    public function test_monthly_inflows_excludes_soft_deleted_gigs(): void
    {
        // Cria gig ativo com payment recebido
        $activeGig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 1000,
            'gig_date' => Carbon::today()->addDays(10),
        ]);

        $activePayment = Payment::factory()->create([
            'gig_id' => $activeGig->id,
            'due_value' => 500,
            'currency' => 'BRL',
            'received_date_actual' => Carbon::today(),
            'received_value_actual' => 500,
        ]);

        // Cria gig deletado com payment recebido
        $deletedGig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 2000,
            'gig_date' => Carbon::today()->addDays(15),
        ]);

        $deletedPayment = Payment::factory()->create([
            'gig_id' => $deletedGig->id,
            'due_value' => 1000,
            'currency' => 'BRL',
            'received_date_actual' => Carbon::today(),
            'received_value_actual' => 1000,
        ]);

        // Soft delete do gig
        $deletedGig->delete();

        // Configura período que inclui ambos os payments
        $this->service->setPeriod(Carbon::today()->subDay(), Carbon::today()->addDay());

        // Calcula inflows
        $inflows = $this->service->calculateMonthlyInflows();

        // Obtém total de todos os meses
        $totalInflow = $inflows->sum('total_inflow');

        // DEVE incluir apenas o payment do gig ativo (500), NÃO o do deletado (1000)
        $this->assertEquals(500.0, $totalInflow, 'Inflows NÃO devem incluir payments de gigs soft-deleted');

        // Verifica que apenas 1 payment foi contado
        $totalPaymentCount = $inflows->sum('payment_count');
        $this->assertEquals(1, $totalPaymentCount, 'Apenas 1 payment deve ser contado (do gig ativo)');
    }

    /**
     * Testa que Payments de Gigs deletados NÃO são incluídos em calculateAccountsReceivable.
     */
    public function test_accounts_receivable_excludes_soft_deleted_gigs(): void
    {
        // Cria gig ativo com payment pendente
        $activeGig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 1000,
            'gig_date' => Carbon::today()->addDays(10),
        ]);

        $activePayment = Payment::factory()->create([
            'gig_id' => $activeGig->id,
            'due_value' => 500,
            'currency' => 'BRL',
            'due_date' => Carbon::today()->addDays(5),
            'confirmed_at' => null, // Pendente
        ]);

        // Cria gig deletado com payment pendente
        $deletedGig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 2000,
            'gig_date' => Carbon::today()->addDays(15),
        ]);

        $deletedPayment = Payment::factory()->create([
            'gig_id' => $deletedGig->id,
            'due_value' => 1000,
            'currency' => 'BRL',
            'due_date' => Carbon::today()->addDays(7),
            'confirmed_at' => null, // Pendente
        ]);

        // Soft delete do gig
        $deletedGig->delete();

        // Configura período que inclui ambos os payments
        $this->service->setPeriod(Carbon::today(), Carbon::today()->addDays(10));

        // Calcula accounts receivable
        $accountsReceivable = $this->service->calculateAccountsReceivable();

        // DEVE incluir apenas o payment do gig ativo (500), NÃO o do deletado (1000)
        $this->assertEquals(500.0, $accountsReceivable['total_receivable'], 'Accounts receivable NÃO devem incluir payments de gigs soft-deleted');

        // Verifica que apenas 1 payment foi contado
        $this->assertEquals(1, $accountsReceivable['payment_count'], 'Apenas 1 payment deve ser contado (do gig ativo)');
    }

    /**
     * Testa que Gigs deletados NÃO são incluídos em calculateMonthlyOutflows.
     */
    public function test_monthly_outflows_excludes_soft_deleted_gigs(): void
    {
        // Cria gig ativo
        $activeGig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 1000,
            'currency' => 'BRL',
            'gig_date' => Carbon::today()->addDays(5),
            'booker_commission_rate' => 5.0,
        ]);

        // Cria gig deletado
        $deletedGig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 2000,
            'currency' => 'BRL',
            'gig_date' => Carbon::today()->addDays(7),
            'booker_commission_rate' => 5.0,
        ]);

        // Soft delete do segundo gig
        $deletedGig->delete();

        // Configura período que incluiria ambos os gigs
        $this->service->setPeriod(Carbon::today(), Carbon::today()->addDays(10));

        // Calcula outflows
        $outflows = $this->service->calculateMonthlyOutflows();

        // Verifica que apenas 1 evento foi contado
        $totalEventCount = $outflows->sum('event_count');
        $this->assertEquals(1, $totalEventCount, 'Apenas 1 gig deve ser contado (o ativo)');

        // Obtém total de outflows
        $totalOutflow = $outflows->sum('total_outflow');

        // O total deve ser baseado APENAS no gig ativo (1000 BRL)
        // Não deve incluir o gig deletado (2000 BRL)
        // Com cachê de 1000 BRL, esperamos ~160 BRL de outflow (80% de 20% do cachê + comissão booker)
        $this->assertGreaterThan(0, $totalOutflow, 'Deve haver outflows do gig ativo');
        $this->assertLessThan(1000, $totalOutflow, 'Total de outflows deve ser baseado apenas no gig ativo (1000 BRL de cachê)');

        // Verifica que não incluiu o gig deletado (se incluísse, seria > 1600)
        $this->assertLessThan(1600, $totalOutflow, 'NÃO deve incluir o gig deletado');
    }

    /**
     * Testa que ao restaurar um Gig soft-deleted, ele volta a ser incluído nos cálculos.
     */
    public function test_restored_gig_is_included_in_calculations(): void
    {
        // Cria gig
        $gig = Gig::factory()->create([
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'cache_value' => 1000,
            'currency' => 'BRL',
            'gig_date' => Carbon::today()->addDays(5),
        ]);

        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 500,
            'currency' => 'BRL',
            'due_date' => Carbon::today()->addDays(3),
            'confirmed_at' => null,
        ]);

        // Soft delete
        $gig->delete();

        // Configura período
        $this->service->setPeriod(Carbon::today(), Carbon::today()->addDays(10));

        // Verifica que está excluído
        $accountsReceivable = $this->service->calculateAccountsReceivable();
        $this->assertEquals(0, $accountsReceivable['payment_count'], 'Payment deve estar excluído após soft delete');

        // Restaura o gig
        $gig->restore();

        // Recalcula
        $accountsReceivable = $this->service->calculateAccountsReceivable();
        $this->assertEquals(1, $accountsReceivable['payment_count'], 'Payment deve ser incluído após restore');
        $this->assertEquals(500.0, $accountsReceivable['total_receivable'], 'Valor deve aparecer após restore');
    }
}
