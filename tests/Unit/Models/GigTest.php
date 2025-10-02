<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use App\Models\Settlement;
use App\Models\Tag;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GigTest extends TestCase
{
    use RefreshDatabase;

    private Artist $artist;
    private Booker $booker;
    private Gig $gig;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable GigObserver during tests to avoid logging issues
        Gig::unsetEventDispatcher();

        $this->artist = Artist::factory()->create();
        $this->booker = Booker::factory()->create();

        $this->gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);
    }

    #[Test]
    public function it_belongs_to_an_artist()
    {
        $this->assertInstanceOf(Artist::class, $this->gig->artist);
        $this->assertEquals($this->artist->id, $this->gig->artist->id);
    }

    #[Test]
    public function it_belongs_to_a_booker()
    {
        $this->assertInstanceOf(Booker::class, $this->gig->booker);
        $this->assertEquals($this->booker->id, $this->gig->booker->id);
    }

    #[Test]
    public function it_belongs_to_a_booker_with_default_when_null()
    {
        $gigWithoutBooker = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => null,
        ]);

        $this->assertInstanceOf(Booker::class, $gigWithoutBooker->booker);
        $this->assertNull($gigWithoutBooker->booker->id);
    }

    #[Test]
    public function it_has_many_payments()
    {
        $payment1 = Payment::factory()->create(['gig_id' => $this->gig->id]);
        $payment2 = Payment::factory()->create(['gig_id' => $this->gig->id]);

        $this->assertCount(2, $this->gig->payments);
        $this->assertTrue($this->gig->payments->contains($payment1));
        $this->assertTrue($this->gig->payments->contains($payment2));
    }

    #[Test]
    public function it_has_one_settlement()
    {
        $settlement = Settlement::factory()->create(['gig_id' => $this->gig->id]);

        $this->assertInstanceOf(Settlement::class, $this->gig->settlement);
        $this->assertEquals($settlement->id, $this->gig->settlement->id);
    }

    #[Test]
    public function it_has_many_costs()
    {
        $cost1 = GigCost::factory()->create(['gig_id' => $this->gig->id]);
        $cost2 = GigCost::factory()->create(['gig_id' => $this->gig->id]);

        $this->assertCount(2, $this->gig->costs);
        $this->assertTrue($this->gig->costs->contains($cost1));
        $this->assertTrue($this->gig->costs->contains($cost2));
    }

    #[Test]
    public function it_has_many_tags()
    {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $this->gig->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $this->gig->tags);
        $this->assertTrue($this->gig->tags->contains($tag1));
        $this->assertTrue($this->gig->tags->contains($tag2));
    }

    #[Test]
    public function it_casts_dates_correctly()
    {
        $contractDate = Carbon::now()->subDays(10);
        $gigDate = Carbon::now()->addDays(30);

        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'contract_date' => $contractDate,
            'gig_date' => $gigDate,
        ]);

        $this->assertInstanceOf(Carbon::class, $gig->contract_date);
        $this->assertInstanceOf(Carbon::class, $gig->gig_date);
        $this->assertEquals($contractDate->format('Y-m-d'), $gig->contract_date->format('Y-m-d'));
        $this->assertEquals($gigDate->format('Y-m-d'), $gig->gig_date->format('Y-m-d'));
    }

    #[Test]
    public function it_casts_decimal_values_correctly()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'cache_value' => 1500.75,
            'agency_commission_rate' => 20.5,
            'agency_commission_value' => 300.25,
            'booker_commission_rate' => 10.0,
            'booker_commission_value' => 150.0,
            'liquid_commission_value' => 150.25,
        ]);

        $this->assertEquals('1500.75', $gig->cache_value);
        $this->assertEquals('20.50', $gig->agency_commission_rate);
        $this->assertEquals('300.25', $gig->agency_commission_value);
        $this->assertEquals('10.00', $gig->booker_commission_rate);
        $this->assertEquals('150.00', $gig->booker_commission_value);
        $this->assertEquals('150.25', $gig->liquid_commission_value);
    }

    #[Test]
    public function it_gets_exchange_rate_for_brl_currency()
    {
        $rate = $this->gig->getExchangeRateForCurrency('BRL', Carbon::now());

        $this->assertEquals(1.0, $rate);
    }

    #[Test]
    public function it_gets_exchange_rate_from_confirmed_payment()
    {
        Log::shouldReceive('info')->once();

        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'currency' => 'USD',
            'exchange_rate' => 5.25,
            'confirmed_at' => Carbon::now(),
        ]);

        $rate = $this->gig->getExchangeRateForCurrency('USD', Carbon::now());

        $this->assertEquals(5.25, $rate);
    }

    #[Test]
    public function it_gets_exchange_rate_from_service_when_no_confirmed_payment()
    {
        Log::shouldReceive('info')->once();

        // Mock do ExchangeRateService
        $exchangeRateService = $this->createMock(\App\Services\ExchangeRateService::class);
        $exchangeRateService->method('getExchangeRate')->willReturn(5.0);
        App::instance(\App\Services\ExchangeRateService::class, $exchangeRateService);

        $rate = $this->gig->getExchangeRateForCurrency('USD', Carbon::now());

        $this->assertEquals(5.0, $rate);
    }

    #[Test]
    public function it_returns_null_when_exchange_rate_not_found()
    {
        Log::shouldReceive('warning')->once();

        // Mock do ExchangeRateService retornando null
        $exchangeRateService = $this->createMock(\App\Services\ExchangeRateService::class);
        $exchangeRateService->method('getExchangeRate')->willReturn(null);
        App::instance(\App\Services\ExchangeRateService::class, $exchangeRateService);

        $rate = $this->gig->getExchangeRateForCurrency('EUR', Carbon::now());

        $this->assertNull($rate);
    }





    #[Test]
    public function it_calculates_gross_cash_brl_via_accessor()
    {
        // Mock do GigFinancialCalculatorService
        $calculator = $this->createMock(GigFinancialCalculatorService::class);
        $calculator->method('calculateGrossCashBrl')->willReturn(1500.0);
        App::instance(GigFinancialCalculatorService::class, $calculator);

        $this->assertEquals(1500.0, $this->gig->gross_cash_brl);
    }

    #[Test]
    public function it_calculates_total_confirmed_expenses_brl_via_accessor()
    {
        // Mock do GigFinancialCalculatorService
        $calculator = $this->createMock(GigFinancialCalculatorService::class);
        $calculator->method('calculateTotalConfirmedExpensesBrl')->willReturn(200.0);
        App::instance(GigFinancialCalculatorService::class, $calculator);

        $this->assertEquals(200.0, $this->gig->total_confirmed_expenses_brl);
    }

    #[Test]
    public function it_calculates_total_reimbursable_expenses_brl_via_accessor()
    {
        // Mock do GigFinancialCalculatorService
        $calculator = $this->createMock(GigFinancialCalculatorService::class);
        $calculator->method('calculateTotalReimbursableExpensesBrl')->willReturn(150.0);
        App::instance(GigFinancialCalculatorService::class, $calculator);

        $this->assertEquals(150.0, $this->gig->total_reimbursable_expenses_brl);
    }

    #[Test]
    public function it_gets_exchange_rate_details_for_brl()
    {
        $details = $this->gig->getExchangeRateDetails();

        $this->assertEquals(['rate' => 1.0, 'type' => 'confirmed'], $details);
    }

    #[Test]
    public function it_gets_exchange_rate_details_from_confirmed_payment()
    {
        $gigUsd = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'currency' => 'USD',
        ]);

        Payment::factory()->create([
            'gig_id' => $gigUsd->id,
            'currency' => 'USD',
            'exchange_rate' => 5.5,
            'confirmed_at' => Carbon::now(),
        ]);

        $details = $gigUsd->getExchangeRateDetails();

        $this->assertEquals(5.5, $details['rate']);
        $this->assertEquals('confirmed', $details['type']);
    }

    #[Test]
    public function it_gets_exchange_rate_details_from_config_when_no_confirmed_payment()
    {
        Config::set('exchange_rates.default_rates.USD', 5.0);

        $gigUsd = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'currency' => 'USD',
        ]);

        $details = $gigUsd->getExchangeRateDetails();

        $this->assertEquals(5.0, $details['rate']);
        $this->assertEquals('projected', $details['type']);
    }

    #[Test]
    public function it_calculates_total_received_brl_from_confirmed_payments()
    {
        Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'currency' => 'BRL',
            'received_value_actual' => 500.0,
            'confirmed_at' => Carbon::now(),
        ]);

        Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'currency' => 'USD',
            'received_value_actual' => 100.0,
            'exchange_rate' => 5.0,
            'confirmed_at' => Carbon::now(),
        ]);

        // Pagamento não confirmado - não deve ser incluído
        Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'currency' => 'BRL',
            'received_value_actual' => 200.0,
            'confirmed_at' => null,
        ]);

        $this->assertEquals(1000.0, $this->gig->total_received_brl); // 500 + (100 * 5)
    }

    #[Test]
    public function it_gets_cache_value_brl_for_brl_currency()
    {
        $this->assertEquals(1000.0, $this->gig->cache_value_brl);
    }

    #[Test]
    public function it_gets_cache_value_brl_details_for_brl_currency()
    {
        $details = $this->gig->cacheValueBrlDetails;

        $this->assertEquals(1000.0, $details['value']);
        $this->assertEquals('confirmed', $details['type']);
        $this->assertEquals(1.0, $details['rate_used']);
    }

    #[Test]
    public function it_gets_cache_value_brl_details_for_fully_paid_foreign_currency()
    {
        Log::shouldReceive('debug')->once();

        $gigUsd = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'cache_value' => 200,
            'currency' => 'USD',
            'payment_status' => 'pago',
        ]);

        Payment::factory()->create([
            'gig_id' => $gigUsd->id,
            'currency' => 'USD',
            'received_value_actual' => 200.0,
            'exchange_rate' => 5.0,
            'confirmed_at' => Carbon::now(),
        ]);

        $details = $gigUsd->cacheValueBrlDetails;

        $this->assertEquals(1000.0, $details['value']); // 200 * 5.0
        $this->assertEquals('confirmed', $details['type']);
        $this->assertEquals(5.0, $details['rate_used']);
    }

    #[Test]
    public function it_gets_cache_value_brl_details_for_pending_foreign_currency()
    {
        Log::shouldReceive('debug')->once();
        Config::set('exchange_rates.default_rates.USD', 5.2);

        $gigUsd = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'cache_value' => 200,
            'currency' => 'USD',
            'payment_status' => 'a_vencer',
        ]);

        $details = $gigUsd->cacheValueBrlDetails;

        $this->assertEquals(1040.0, $details['value']); // 200 * 5.2
        $this->assertEquals('projected', $details['type']);
        $this->assertEquals(5.2, $details['rate_used']);
    }

    #[Test]
    public function it_returns_unavailable_when_no_exchange_rate_found()
    {
        Log::shouldReceive('warning')->once();

        $gigJpy = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'cache_value' => 200,
            'currency' => 'JPY', // Usando JPY que não tem taxa configurada
            'payment_status' => 'a_vencer',
        ]);

        $details = $gigJpy->cacheValueBrlDetails;

        $this->assertNull($details['value']);
        $this->assertEquals('unavailable', $details['type']);
        $this->assertNull($details['rate_used']);
    }

    #[Test]
    public function it_checks_if_all_costs_are_confirmed_when_no_costs()
    {
        $this->assertTrue($this->gig->are_all_costs_confirmed);
    }

    #[Test]
    public function it_checks_if_all_costs_are_confirmed_when_all_confirmed()
    {
        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'is_confirmed' => true,
        ]);

        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'is_confirmed' => true,
        ]);

        $this->assertTrue($this->gig->are_all_costs_confirmed);
    }

    #[Test]
    public function it_checks_if_all_costs_are_confirmed_when_some_unconfirmed()
    {
        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'is_confirmed' => true,
        ]);

        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'is_confirmed' => false,
        ]);

        $this->assertFalse($this->gig->are_all_costs_confirmed);
    }

    #[Test]
    public function it_has_correct_fillable_attributes()
    {
        $expectedFillable = [
            'artist_id',
            'booker_id',
            'contract_number',
            'contract_date',
            'gig_date',
            'location_event_details',
            'cache_value',
            'currency',
            'agency_commission_type',
            'agency_commission_rate',
            'agency_commission_value',
            'booker_commission_type',
            'booker_commission_rate',
            'booker_commission_value',
            'liquid_commission_value',
            'payment_status',
            'artist_payment_status',
            'booker_payment_status',
            'contract_status',
            'notes',
        ];

        $this->assertEquals($expectedFillable, $this->gig->getFillable());
    }

    #[Test]
    public function it_can_be_created_with_factory()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'contract_number' => 'TEST-001',
            'cache_value' => 2500,
            'currency' => 'USD',
            'notes' => 'Test gig notes',
        ]);

        $this->assertDatabaseHas('gigs', [
            'id' => $gig->id,
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'contract_number' => 'TEST-001',
            'cache_value' => 2500,
            'currency' => 'USD',
            'notes' => 'Test gig notes',
        ]);
    }
}