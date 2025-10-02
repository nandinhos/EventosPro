<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private Gig $gig;
    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable GigObserver during tests to avoid logging issues
        Gig::unsetEventDispatcher();

        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $this->gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        $this->payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'due_value' => 500,
            'currency' => 'BRL',
        ]);
    }

    #[Test]
    public function it_belongs_to_a_gig()
    {
        $this->assertInstanceOf(Gig::class, $this->payment->gig);
        $this->assertEquals($this->gig->id, $this->payment->gig->id);
    }

    #[Test]
    public function it_casts_due_value_to_decimal()
    {
        $this->assertIsString($this->payment->due_value);
        $this->assertEquals('500.00', $this->payment->due_value);
    }

    #[Test]
    public function it_casts_received_value_actual_to_decimal()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'received_value_actual' => 450.75,
        ]);

        $this->assertIsString($payment->received_value_actual);
        $this->assertEquals('450.75', $payment->received_value_actual);
    }

    #[Test]
    public function it_casts_exchange_rate_to_decimal()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'exchange_rate' => 5.25,
        ]);

        $this->assertIsString($payment->exchange_rate);
        $this->assertEquals('5.250000', $payment->exchange_rate);
    }

    #[Test]
    public function it_casts_confirmed_at_to_datetime()
    {
        $confirmedAt = Carbon::now();
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'confirmed_at' => $confirmedAt,
        ]);

        $this->assertInstanceOf(Carbon::class, $payment->confirmed_at);
        $this->assertEquals($confirmedAt->format('Y-m-d H:i:s'), $payment->confirmed_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_calculates_due_value_brl_for_brl_currency()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'due_value' => 1000,
            'currency' => 'BRL',
        ]);

        $this->assertEquals(1000.0, $payment->due_value_brl);
    }

    #[Test]
    public function it_calculates_due_value_brl_for_foreign_currency_with_exchange_rate()
    {
        // Garantir que a configuração padrão esteja definida
        Config::set('exchange_rates.default_rates', [
            'USD' => 5.30,
            'EUR' => 5.70,
            'GBP' => 6.50,
        ]);

        // Criar gig em moeda estrangeira
        $gigWithExchangeRate = Gig::factory()->create([
            'artist_id' => $this->gig->artist_id,
            'booker_id' => $this->gig->booker_id,
            'cache_value' => 1000,
            'currency' => 'USD',
        ]);

        $payment = Payment::factory()->create([
            'gig_id' => $gigWithExchangeRate->id,
            'due_value' => 200, // 200 USD
            'currency' => 'USD',
        ]);

        $this->assertEquals(1060.0, $payment->due_value_brl); // 200 * 5.30
    }

    #[Test]
    public function it_returns_due_value_when_exchange_rate_not_found()
    {
        // Salvar configuração original
        $originalConfig = config('exchange_rates.default_rates');
        
        Log::shouldReceive('warning')->twice(); // Uma vez no Gig e uma vez no Payment

        // Remover configuração padrão para simular ausência de taxa
        Config::set('exchange_rates.default_rates', []);
        
        // Mock do ExchangeRateService retornando null ANTES de criar qualquer modelo
        $exchangeRateService = $this->createMock(\App\Services\ExchangeRateService::class);
        $exchangeRateService->method('getExchangeRate')->willReturn(null);
        $this->app->instance(\App\Services\ExchangeRateService::class, $exchangeRateService);

        try {
            // Criar gig sem taxa de câmbio
            $gigWithoutExchangeRate = Gig::factory()->create([
                'artist_id' => $this->gig->artist_id,
                'booker_id' => $this->gig->booker_id,
                'cache_value' => 1000,
                'currency' => 'USD',
            ]);

            $payment = Payment::factory()->create([
                'gig_id' => $gigWithoutExchangeRate->id,
                'due_value' => 200,
                'currency' => 'USD',
                'exchange_rate' => null, // Garantir que não há taxa no payment
            ]);

            $this->assertEquals(200.0, $payment->due_value_brl);
        } finally {
            // Restaurar configuração original
            Config::set('exchange_rates.default_rates', $originalConfig);
            
            // Limpar o mock do container
            $this->app->forgetInstance(\App\Services\ExchangeRateService::class);
        }
    }

    #[Test]
    public function it_checks_if_payment_is_pending()
    {
        $pendingPayment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'confirmed_at' => null,
        ]);

        $confirmedPayment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'confirmed_at' => Carbon::now(),
        ]);

        $this->assertFalse($pendingPayment->is_paid);
        $this->assertTrue($confirmedPayment->is_paid);
    }

    #[Test]
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'gig_id',
            'description',
            'due_value',
            'due_date',
            'currency',
            'exchange_rate',
            'received_value_actual',
            'received_date_actual',
            'confirmed_at',
            'confirmed_by',
            'notes',
        ];

        $this->assertEquals($fillable, $this->payment->getFillable());
    }

    #[Test]
    public function it_has_correct_casts()
    {
        $expectedCasts = [
            'id' => 'int',
            'due_value' => 'decimal:2',
            'due_date' => 'date',
            'received_value_actual' => 'decimal:2',
            'received_date_actual' => 'date',
            'confirmed_at' => 'datetime',
            'exchange_rate' => 'decimal:6',
            'deleted_at' => 'datetime',
        ];

        $this->assertEquals($expectedCasts, $this->payment->getCasts());
    }

    #[Test]
    public function it_can_be_created_with_factory()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'due_value' => 750,
            'currency' => 'EUR',
            'notes' => 'Test payment notes',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gig_id' => $this->gig->id,
            'due_value' => 750,
            'currency' => 'EUR',
            'notes' => 'Test payment notes',
        ]);
    }

    #[Test]
    public function it_can_be_confirmed()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
            'confirmed_at' => null,
        ]);

        $this->assertFalse($payment->is_paid);

        $payment->update(['confirmed_at' => Carbon::now()]);

        $this->assertTrue($payment->fresh()->is_paid);
    }
}