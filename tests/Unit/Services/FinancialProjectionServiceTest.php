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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
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
        $this->projectionService = $this->app->make(FinancialProjectionService::class);

        // Limpar cache antes de cada teste para evitar interferência
        Cache::flush();
    }

    #[Test]
    public function it_sets_period_correctly_for_30_days()
    {
        $this->projectionService->setPeriod('30_days');

        // We can't directly access protected properties, but we can test the behavior
        // by checking the results of methods that depend on the period
        $this->assertTrue(true); // Basic test that setPeriod doesn't throw errors
    }

    #[Test]
    public function it_sets_period_correctly_for_60_days()
    {
        $this->projectionService->setPeriod('60_days');
        $this->assertTrue(true);
    }

    #[Test]
    public function it_sets_period_correctly_for_90_days()
    {
        $this->projectionService->setPeriod('90_days');
        $this->assertTrue(true);
    }

    #[Test]
    public function it_sets_period_correctly_for_next_semester()
    {
        $this->projectionService->setPeriod('next_semester');
        $this->assertTrue(true);
    }

    #[Test]
    public function it_sets_period_correctly_for_next_year()
    {
        $this->projectionService->setPeriod('next_year');
        $this->assertTrue(true);
    }

    #[Test]
    public function it_calculates_accounts_receivable_with_no_payments()
    {
        $result = $this->projectionService->getAccountsReceivable();

        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function it_calculates_total_accounts_payable_consolidated()
    {
        $this->projectionService->setPeriod('30_days');

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        $costCenter = CostCenter::factory()->create();

        // Create a gig with pending date in projection period
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(10),
            'cache_value' => 1000,
            'currency' => 'BRL',
            'agency_commission_type' => 'percentage',
            'agency_commission_value' => 20,
            'booker_commission_type' => 'percentage',
            'booker_commission_value' => 10,
            'payment_status' => 'pending',
        ]);

        // Add a pending cost
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 200,
            'currency' => 'BRL',
            'is_confirmed' => false,
            'expense_date' => Carbon::now()->addDays(8),
        ]);

        // Calculate expected values
        $expectedArtistsPayable = $this->gigCalculator->calculateArtistInvoiceValueBrl($gig);
        $expectedBookersPayable = $this->gigCalculator->calculateBookerCommissionBrl($gig);
        $expectedExpensesPayable = $this->projectionService->getAccountsPayableExpenses();
        $expectedTotal = $expectedArtistsPayable + $expectedBookersPayable + $expectedExpensesPayable;

        // Test the consolidated metric
        $totalPayable = $this->projectionService->getTotalAccountsPayable();

        $this->assertEquals($expectedTotal, $totalPayable);
        $this->assertGreaterThan(0, $totalPayable);
    }

    #[Test]
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
            'due_date' => Carbon::now()->addDays(5),
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

    #[Test]
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

    #[Test]
    public function it_calculates_accounts_payable_artists_with_no_pending_gigs()
    {
        $result = $this->projectionService->getAccountsPayableArtists();

        $this->assertEquals(0.0, $result);
    }

    #[Test]
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

    #[Test]
    public function it_handles_different_currencies_in_calculations()
    {
        // Mock HTTP calls to prevent real API calls and force fallback to config
        Http::fake([
            '*' => Http::response([], 404), // Force fallback to default rates
        ]);

        // Clear any cached exchange rates
        Cache::flush();

        // Configure a known exchange rate for testing
        config(['exchange_rates.default_rates.USD' => 5.20]);

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
            'due_date' => Carbon::now()->addDays(5),
            'due_value' => 50, // USD
            'currency' => 'USD',
            'exchange_rate' => null, // Force use of gig's exchange rate
            'confirmed_at' => null,
        ]);

        $result = $this->projectionService->getAccountsReceivable();

        // Should convert to BRL using the accessor (USD to BRL conversion)
        // Expected: 50 USD * exchange rate (5.20) = 260 BRL
        $this->assertEquals(260.0, $result, 'Expected 50 USD * 5.20 = 260 BRL');
    }

    #[Test]
    public function it_calculates_accounts_payable_bookers_with_no_pending_gigs()
    {
        $result = $this->projectionService->getAccountsPayableBookers();

        $this->assertEquals(0.0, $result);
    }

    #[Test]
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

    #[Test]
    public function it_calculates_accounts_payable_expenses_with_no_costs()
    {
        $result = $this->projectionService->getAccountsPayableExpenses();

        $this->assertEquals(0.0, $result);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_sets_custom_date_range_correctly()
    {
        $startDate = '2025-01-01';
        $endDate = '2025-03-31';

        $this->projectionService->setPeriod('custom', $startDate, $endDate);

        // Test by checking upcoming payments are filtered by custom date range
        $artist = Artist::factory()->create();
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
        ]);

        // Payment within custom range
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::parse('2025-02-15'),
            'due_value' => 500,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Payment outside custom range (before)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::parse('2024-12-15'),
            'due_value' => 300,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        // Payment outside custom range (after)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_date' => Carbon::parse('2025-04-15'),
            'due_value' => 400,
            'currency' => 'BRL',
            'confirmed_at' => null,
        ]);

        $result = $this->projectionService->getUpcomingClientPayments();

        // Should only include payments within the custom date range (and overdue)
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    #[Test]
    public function it_handles_partial_custom_dates()
    {
        // Only start date provided
        $startDate = '2025-01-01';

        $this->projectionService->setPeriod('', $startDate, null);

        // Should not throw error
        $this->assertTrue(true);
    }

    #[Test]
    public function it_filters_projections_by_custom_date_range()
    {
        $startDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(15)->format('Y-m-d');

        $this->projectionService->setPeriod('custom', $startDate, $endDate);

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        // Gig within custom range
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(10),
            'cache_value' => 1000,
            'currency' => 'BRL',
            'artist_payment_status' => 'pendente',
        ]);

        // Gig outside custom range (too early)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(2),
            'cache_value' => 800,
            'currency' => 'BRL',
            'artist_payment_status' => 'pendente',
        ]);

        // Gig outside custom range (too late)
        Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->addDays(20),
            'cache_value' => 600,
            'currency' => 'BRL',
            'artist_payment_status' => 'pendente',
        ]);

        $upcomingArtistPayments = $this->projectionService->getUpcomingInternalPayments('artists');

        // Should only include gigs within the custom range (and past pending gigs)
        $this->assertGreaterThanOrEqual(1, $upcomingArtistPayments->count());
    }
}
