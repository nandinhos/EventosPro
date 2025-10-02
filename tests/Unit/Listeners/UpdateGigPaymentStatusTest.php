<?php

namespace Tests\Unit\Listeners;

use App\Events\PaymentSaved;
use App\Listeners\UpdateGigPaymentStatus;
use App\Models\Gig;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class UpdateGigPaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private UpdateGigPaymentStatus $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new UpdateGigPaymentStatus();
    }

    public function test_it_can_be_instantiated()
    {
        $this->assertInstanceOf(UpdateGigPaymentStatus::class, $this->listener);
    }

    public function test_updates_gig_status_to_pago_when_fully_paid()
    {
        // Mock logs that might be called by observers
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create a confirmed payment that covers the full amount
        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1000.00,
            'received_value_actual' => 1000.00,
            'currency' => 'USD',
            'confirmed_at' => now()
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('pago', $gig->payment_status);
    }

    public function test_updates_gig_status_to_vencido_when_overdue()
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create an unconfirmed payment that is overdue
        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1000.00,
            'currency' => 'USD',
            'due_date' => now()->subDays(5), // Overdue
            'confirmed_at' => null
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('vencido', $gig->payment_status);
    }

    public function test_keeps_gig_status_as_a_vencer_when_not_overdue()
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create an unconfirmed payment that is not overdue
        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 500.00,
            'currency' => 'USD',
            'due_date' => now()->addDays(5), // Future date
            'confirmed_at' => null
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('a_vencer', $gig->payment_status);
    }

    public function test_can_be_instantiated()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create partial confirmed payment
        $payment1 = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 500.00,
            'received_value_actual' => 500.00,
            'currency' => 'USD',
            'confirmed_at' => now()
        ]);

        // Create remaining unconfirmed payment (not overdue)
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 500.00,
            'currency' => 'USD',
            'due_date' => now()->addDays(10),
            'confirmed_at' => null
        ]);

        $event = new PaymentSaved($payment1);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('a_vencer', $gig->payment_status);
    }

    public function test_handles_tolerance_for_payment_completion()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create payment that is slightly less than total due (within tolerance)
        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 999.99,
            'received_value_actual' => 999.99,
            'currency' => 'USD',
            'confirmed_at' => now()
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('pago', $gig->payment_status);
    }

    public function test_only_considers_confirmed_payments()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create unconfirmed payment that would cover full amount
        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1000.00,
            'received_value_actual' => 1000.00,
            'currency' => 'USD',
            'confirmed_at' => null,
            'due_date' => now()->addDays(10)
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('a_vencer', $gig->payment_status);
    }

    public function test_only_considers_payments_in_same_currency()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        // Create confirmed payment in different currency
        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 5000.00,
            'received_value_actual' => 5000.00,
            'currency' => 'BRL', // Different currency
            'confirmed_at' => now()
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        $gig->refresh();
        $this->assertEquals('a_vencer', $gig->payment_status);
    }



    public function test_uses_database_transaction()
    {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        $payment = Payment::factory()->create([
            'gig_id' => $gig->id,
            'due_value' => 1000.00,
            'received_value_actual' => 1000.00,
            'currency' => 'USD',
            'confirmed_at' => now()
        ]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);
    }

    public function test_handles_zero_cache_value()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 0,
            'currency' => 'USD',
            'payment_status' => 'a_vencer'
        ]);

        $payment = Payment::factory()->create(['gig_id' => $gig->id]);

        $event = new PaymentSaved($payment);

        $this->listener->handle($event);

        // Should return early without processing
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
}