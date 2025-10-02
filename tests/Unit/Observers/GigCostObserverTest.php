<?php

namespace Tests\Unit\Observers;

use App\Models\Gig;
use App\Models\GigCost;
use App\Observers\GigCostObserver;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GigCostObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_saved_method()
    {
        $observer = app(GigCostObserver::class);
        $this->assertTrue(method_exists($observer, 'saved'));
    }

    #[Test]
    public function it_has_deleted_method()
    {
        $observer = app(GigCostObserver::class);
        $this->assertTrue(method_exists($observer, 'deleted'));
    }

    #[Test]
    public function it_calls_saved_method_without_errors()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $gig = Gig::factory()->create();
        $gigCost = GigCost::factory()->create(['gig_id' => $gig->id]);

        $observer = app(GigCostObserver::class);
        
        // Should not throw any exceptions
        $observer->saved($gigCost);
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_calls_deleted_method_without_errors()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $gig = Gig::factory()->create();
        $gigCost = GigCost::factory()->create(['gig_id' => $gig->id]);

        $observer = app(GigCostObserver::class);
        
        // Should not throw any exceptions
        $observer->deleted($gigCost);
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_missing_gig_gracefully_on_save()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        // Create a gig cost without a valid gig
        $gigCost = new GigCost();
        $gigCost->id = 1;
        $gigCost->gig_id = 999; // Non-existent gig

        $observer = app(GigCostObserver::class);

        // Should not throw any exceptions
        $observer->saved($gigCost);
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_missing_gig_gracefully_on_delete()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        // Create a gig cost without a valid gig
        $gigCost = new GigCost();
        $gigCost->id = 1;
        $gigCost->gig_id = 999; // Non-existent gig

        $observer = app(GigCostObserver::class);

        // Should not throw any exceptions
        $observer->deleted($gigCost);
        
        $this->assertTrue(true);
    }
}