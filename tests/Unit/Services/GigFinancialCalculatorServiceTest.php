<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GigFinancialCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

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
    }

    #[Test]
    public function it_calculates_gross_cash_brl_without_expenses()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        $result = $this->calculator->calculateGrossCashBrl($gig);

        $this->assertEquals(1000.00, $result);
    }

    #[Test]
    public function it_calculates_gross_cash_brl_with_confirmed_expenses()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        // Criar despesas confirmadas
        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 200.00,
            'currency' => 'BRL',
        ]);

        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 100.00,
            'currency' => 'BRL',
        ]);

        $result = $this->calculator->calculateGrossCashBrl($gig);

        $this->assertEquals(700.00, $result); // 1000 - 200 - 100
    }

    #[Test]
    public function it_ignores_unconfirmed_expenses_in_gross_cash_calculation()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        // Criar despesa confirmada
        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 200.00,
            'currency' => 'BRL',
        ]);

        // Criar despesa não confirmada (deve ser ignorada)
        GigCost::factory()->unconfirmed()->create([
            'gig_id' => $gig->id,
            'value' => 500.00,
            'currency' => 'BRL',
        ]);

        $result = $this->calculator->calculateGrossCashBrl($gig);

        $this->assertEquals(800.00, $result); // 1000 - 200 (ignora os 500 não confirmados)
    }

    #[Test]
    public function it_calculates_agency_gross_commission_with_percentage()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0,
        ]);

        $result = $this->calculator->calculateAgencyGrossCommissionBrl($gig);

        $this->assertEquals(200.00, $result); // 20% de 1000
    }

    #[Test]
    public function it_calculates_agency_gross_commission_with_fixed_value()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'FIXED',
            'agency_commission_value' => 150.00,
        ]);

        $result = $this->calculator->calculateAgencyGrossCommissionBrl($gig);

        $this->assertEquals(150.00, $result);
    }

    #[Test]
    public function it_calculates_artist_net_payout_brl()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20.0,
        ]);

        $result = $this->calculator->calculateArtistNetPayoutBrl($gig);

        $this->assertEquals(800.00, $result); // 1000 - 200 (20% comissão)
    }

    #[Test]
    public function it_calculates_booker_commission_with_percentage()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'booker_commission_type' => 'PERCENT',
            'booker_commission_rate' => 5.0,
        ]);

        // Limpar todos os custos que possam ter sido criados automaticamente
        $gig->costs()->delete();

        $result = $this->calculator->calculateBookerCommissionBrl($gig);

        $this->assertEquals(50.00, $result); // 5% de 1000
    }

    #[Test]
    public function it_calculates_booker_commission_with_fixed_value()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 75.00,
        ]);

        $result = $this->calculator->calculateBookerCommissionBrl($gig);

        $this->assertEquals(75.00, $result);
    }

    #[Test]
    public function it_returns_zero_booker_commission_when_no_booker()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => null,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        $result = $this->calculator->calculateBookerCommissionBrl($gig);

        $this->assertEquals(0.00, $result);
    }

    #[Test]
    public function it_handles_gig_without_booker_for_commission_calculation()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => null, // Sem booker
            'cache_value' => 1000.00,
            'currency' => 'BRL',
        ]);

        $result = $this->calculator->calculateBookerCommissionBrl($gig);

        $this->assertEquals(0.00, $result);
    }

    #[Test]
    public function it_calculates_agency_net_commission_brl()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'FIXED',
            'agency_commission_value' => 200.00,
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 50.00,
        ]);

        $result = $this->calculator->calculateAgencyNetCommissionBrl($gig);

        $this->assertEquals(150.00, $result); // 200 - 50
    }

    #[Test]
    public function it_calculates_total_confirmed_expenses_brl()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
        ]);

        // Criar despesas confirmadas
        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 300.00,
            'currency' => 'BRL',
        ]);

        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 150.00,
            'currency' => 'BRL',
        ]);

        // Criar despesa não confirmada (deve ser ignorada)
        GigCost::factory()->unconfirmed()->create([
            'gig_id' => $gig->id,
            'value' => 100.00,
            'currency' => 'BRL',
        ]);

        $result = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);

        $this->assertEquals(450.00, $result); // 300 + 150 (ignora os 100 não confirmados)
    }

    #[Test]
    public function it_handles_complex_financial_calculation_scenario()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 5000.00,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20.0, // 20%
            'booker_commission_type' => 'FIXED',
            'booker_commission_value' => 300.00,
        ]);

        // Limpar todos os custos que possam ter sido criados automaticamente
        $gig->costs()->delete();

        // Adicionar despesas confirmadas
        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 800.00,
            'currency' => 'BRL',
        ]);

        GigCost::factory()->confirmed()->create([
            'gig_id' => $gig->id,
            'value' => 200.00,
            'currency' => 'BRL',
        ]);

        // Adicionar despesa não confirmada (deve ser ignorada)
        GigCost::factory()->unconfirmed()->create([
            'gig_id' => $gig->id,
            'value' => 500.00,
            'currency' => 'BRL',
        ]);

        // Calcular valores
        $grossCash = $this->calculator->calculateGrossCashBrl($gig);
        $agencyCommission = $this->calculator->calculateAgencyGrossCommissionBrl($gig);
        $bookerCommission = $this->calculator->calculateBookerCommissionBrl($gig);
        $agencyNetCommission = $this->calculator->calculateAgencyNetCommissionBrl($gig);
        $artistPayout = $this->calculator->calculateArtistNetPayoutBrl($gig);
        $totalExpenses = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);

        // Verificar cálculos
        $this->assertEquals(4000.00, $grossCash); // 5000 - 800 - 200
        $this->assertEquals(800.00, $agencyCommission); // 20% de 4000 (gross cash)
        $this->assertEquals(300.00, $bookerCommission); // Valor fixo
        $this->assertEquals(500.00, $agencyNetCommission); // 800 - 300
        $this->assertEquals(3200.00, $artistPayout); // 4000 - 800
        $this->assertEquals(1000.00, $totalExpenses); // 800 + 200
    }
}
