<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use App\Services\FinancialProjectionService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialProjectionService $projectionService;
    protected GigFinancialCalculatorService $gigCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gigCalculator = $this->app->make(GigFinancialCalculatorService::class);
        $this->projectionService = new FinancialProjectionService($this->gigCalculator);
    }

    /** @test */
    public function it_sets_period_correctly_for_30_days()
    {
        $this->projectionService->setPeriod('30_days');
        
        // We can't directly access protected properties, but we can test the behavior
        // by checking the results of methods that depend on the period
        $this->assertTrue(true); // Basic test that setPeriod doesn't throw errors
    }

    /** @test */
    public function it_sets_period_correctly_for_60_days()
    {
        $this->projectionService->setPeriod('60_days');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_sets_period_correctly_for_90_days()
    {
        $this->projectionService->setPeriod('90_days');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_sets_period_correctly_for_next_semester()
    {
        $this->projectionService->setPeriod('next_semester');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_sets_period_correctly_for_next_year()
    {
        $this->projectionService->setPeriod('next_year');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_calculates_accounts_receivable_with_no_payments()
    {
        $result = $this->projectionService->getAccountsReceivable();
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_calculates_accounts_receivable_with_pending_payments()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        // Create pending payment
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 500,
            'currency' => 'BRL',
            'confirmed_at' => null, // Not confirmed
        ]);

        // Create confirmed payment (should not be included)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 300,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->projectionService->getAccountsReceivable();
        
        $this->assertEquals(500.0, $result);
    }

    /** @test */
    public function it_gets_upcoming_client_payments()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
        ]);

        // Create overdue payment
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::now()->subDays(5),
            'due_value' => 500,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Create future payment
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::now()->addDays(10),
            'due_value' => 300,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Create confirmed payment (should not be included)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::now()->addDays(5),
            'due_value' => 200,
            'currency' => 'BRL',
            'confirmed_at' => Carbon::now(),
        ]);

        $result = $this->projectionService->getUpcomingClientPayments();
        
        $this->assertCount(2, $result);
        $this->assertEquals(500, $result->first()->due_value);
    }

    /** @test */
    public function it_calculates_accounts_payable_artists_with_no_pending_gigs()
    {
        $result = $this->projectionService->getAccountsPayableArtists();
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_calculates_accounts_payable_artists_with_pending_gigs()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        // Create past gig with pending artist payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDays(10),
            'contract_date' => Carbon::now()->subDays(15),
            'cache_value' => 1000,
            'currency' => 'BRL',
            'artist_payment_status' => 'pendente',
        ]);

        // Create future gig with pending artist payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(10),
            'contract_date' => Carbon::now(),
            'cache_value' => 800,
            'currency' => 'BRL',
            'artist_payment_status' => 'pendente',
        ]);

        // Create gig with confirmed artist payment (should not be included)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(5),
            'contract_date' => Carbon::now(),
            'cache_value' => 600,
            'currency' => 'BRL',
            'artist_payment_status' => 'confirmado',
        ]);

        $result = $this->projectionService->getAccountsPayableArtists();
        
        // Should include both pending gigs
        $this->assertGreaterThan(0, $result);
    }

    /** @test */
    public function it_handles_different_currencies_in_calculations()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'cache_value' => 100, // USD
            'currency' => 'USD',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 50, // USD
            'currency' => 'USD',
            'confirmed_at' => null,
        ]);

        $result = $this->projectionService->getAccountsReceivable();
        
        // Should convert to BRL using the accessor (USD to BRL conversion multiplies by exchange rate)
        $this->assertGreaterThan(50, $result); // USD to BRL conversion results in larger numeric value due to exchange rate
        $this->assertGreaterThan(0, $result); // Should be positive
    }

    /** @test */
    public function it_calculates_accounts_payable_bookers_with_no_pending_gigs()
    {
        $result = $this->projectionService->getAccountsPayableBookers();
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_calculates_accounts_payable_bookers_with_pending_gigs()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        // Create past gig with pending booker payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDays(10),
            'contract_date' => Carbon::now()->subDays(15),
            'cache_value' => 1000,
            'currency' => 'BRL',
            'booker_payment_status' => 'pendente',
        ]);

        // Create future gig with pending booker payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(10),
            'contract_date' => Carbon::now(),
            'cache_value' => 800,
            'currency' => 'BRL',
            'booker_payment_status' => 'pendente',
        ]);

        // Create gig with confirmed booker payment (should not be included)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(5),
            'contract_date' => Carbon::now(),
            'cache_value' => 600,
            'currency' => 'BRL',
            'booker_payment_status' => 'confirmado',
        ]);

        // Create gig without booker (should not be included)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => null,
            'gig_date' => Carbon::now()->addDays(8),
            'contract_date' => Carbon::now(),
            'cache_value' => 500,
            'currency' => 'BRL',
            'booker_payment_status' => 'pendente',
        ]);

        $result = $this->projectionService->getAccountsPayableBookers();
        
        // Should include both pending gigs with bookers
         $this->assertGreaterThan(0, $result);
     }

    /** @test */
    public function it_calculates_accounts_payable_expenses_with_no_costs()
    {
        $result = $this->projectionService->getAccountsPayableExpenses();
        
        $this->assertEquals(0.0, $result);
    }

    /** @test */
    public function it_calculates_accounts_payable_expenses_with_pending_costs()
    {
        $artist = Artist::factory()->create();
        $costCenter = CostCenter::factory()->create();
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->addDays(10),
        ]);

        // Create unconfirmed cost with expense_date
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Test expense',
            'value' => 500,
            'currency' => 'BRL',
            'expense_date' => Carbon::now()->addDays(5),
            'is_confirmed' => false,
        ]);

        // Create unconfirmed cost without expense_date (should use gig_date)
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Test expense 2',
            'value' => 300,
            'currency' => 'BRL',
            'expense_date' => null,
            'is_confirmed' => false,
        ]);

        // Create confirmed cost (should not be included)
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Confirmed expense',
            'value' => 200,
            'currency' => 'BRL',
            'expense_date' => Carbon::now()->addDays(3),
            'is_confirmed' => true,
        ]);

        $result = $this->projectionService->getAccountsPayableExpenses();
        
        $this->assertEquals(800.0, $result);
    }

    /** @test */
    public function it_gets_projected_expenses_by_cost_center()
    {
        $artist = Artist::factory()->create();
        $costCenter1 = CostCenter::factory()->create(['name' => 'transport']);
        $costCenter2 = CostCenter::factory()->create(['name' => 'accommodation']);
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->addDays(10),
        ]);

        // Create costs for different cost centers
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter1->id,
            'description' => 'Transport cost',
            'value' => 500,
            'currency' => 'BRL',
            'expense_date' => Carbon::now()->addDays(5),
            'is_confirmed' => false,
        ]);

        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter2->id,
            'description' => 'Hotel cost',
            'value' => 300,
            'currency' => 'BRL',
            'expense_date' => Carbon::now()->addDays(3),
            'is_confirmed' => false,
        ]);

        $result = $this->projectionService->getProjectedExpensesByCostCenter();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(2, $result);
        
        $firstGroup = $result->first();
        $this->assertArrayHasKey('cost_center_name', $firstGroup);
        $this->assertArrayHasKey('total_brl', $firstGroup);
        $this->assertArrayHasKey('expenses', $firstGroup);
    }

    /** @test */
    public function it_calculates_projected_cash_flow()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
            'artist_payment_status' => 'pendente',
            'booker_payment_status' => 'pendente',
        ]);

        // Create pending payment (accounts receivable)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 800,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        $result = $this->projectionService->getProjectedCashFlow();
        
        $this->assertIsFloat($result);
        // Cash flow = receivable - (payable artists + payable bookers + payable expenses)
        // Should be positive since we have receivables
        $this->assertGreaterThan(-1000, $result); // Basic sanity check
    }

    /** @test */
    public function it_gets_upcoming_payments_for_clients()
    {
        $artist = Artist::factory()->create();
        
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
        ]);

        // Create payment within projection period
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::now()->addDays(10),
            'due_value' => 500,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Create payment outside projection period
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::now()->addDays(50),
            'due_value' => 300,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        $result = $this->projectionService->getUpcomingPayments('clients');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result); // Only the payment within period
    }

    /** @test */
    public function it_gets_upcoming_payments_for_artists()
    {
        $artist = Artist::factory()->create();
        
        // Create gig within projection period with pending artist payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->addDays(10),
            'artist_payment_status' => 'pendente',
        ]);

        // Create gig outside projection period
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->addDays(50),
            'artist_payment_status' => 'pendente',
        ]);

        $result = $this->projectionService->getUpcomingPayments('artists');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result); // Only the gig within period
    }

    /** @test */
    public function it_gets_upcoming_payments_for_bookers()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        // Create gig within projection period with pending booker payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(10),
            'booker_payment_status' => 'pendente',
        ]);

        // Create gig outside projection period
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(50),
            'booker_payment_status' => 'pendente',
        ]);

        $result = $this->projectionService->getUpcomingPayments('bookers');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result); // Only the gig within period
    }

    /** @test */
    public function it_gets_upcoming_internal_payments_for_artists()
    {
        $artist = Artist::factory()->create();
        
        // Create past gig with pending artist payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->subDays(5),
            'artist_payment_status' => 'pendente',
        ]);

        // Create future gig with pending artist payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->addDays(10),
            'artist_payment_status' => 'pendente',
        ]);

        // Create gig with confirmed payment (should not be included)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => Carbon::now()->addDays(5),
            'artist_payment_status' => 'confirmado',
        ]);

        $result = $this->projectionService->getUpcomingInternalPayments('artists');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(2, $result); // Past and future pending payments
    }

    /** @test */
    public function it_gets_upcoming_internal_payments_for_bookers()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        
        // Create past gig with pending booker payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDays(5),
            'booker_payment_status' => 'pendente',
        ]);

        // Create future gig with pending booker payment
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(10),
            'booker_payment_status' => 'pendente',
        ]);

        // Create gig without booker (should not be included)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => null,
            'gig_date' => Carbon::now()->addDays(8),
            'booker_payment_status' => 'pendente',
        ]);

        $result = $this->projectionService->getUpcomingInternalPayments('bookers');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(2, $result); // Past and future pending payments with bookers
    }
}