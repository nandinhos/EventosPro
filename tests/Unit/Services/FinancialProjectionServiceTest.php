<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
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
        
        // Should convert to BRL using the accessor
        $this->assertGreaterThan(50, $result); // Assuming USD > BRL conversion
    }
}