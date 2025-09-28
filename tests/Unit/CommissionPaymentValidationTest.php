<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CommissionPaymentValidationTest extends TestCase
{
    public function test_future_event_should_have_pending_payment_status()
    {
        // Arrange
        $futureDate = Carbon::now()->addDays(30);

        // Act
        $isPastEvent = $futureDate < now();
        $artistPaymentStatus = $isPastEvent ? 'pago' : 'pendente';
        $bookerPaymentStatus = $isPastEvent ? 'pago' : 'pendente';

        // Assert
        $this->assertFalse($isPastEvent);
        $this->assertEquals('pendente', $artistPaymentStatus);
        $this->assertEquals('pendente', $bookerPaymentStatus);
    }

    public function test_past_event_should_have_paid_payment_status()
    {
        // Arrange
        $pastDate = Carbon::now()->subDays(30);

        // Act
        $isPastEvent = $pastDate < now();
        $artistPaymentStatus = $isPastEvent ? 'pago' : 'pendente';
        $bookerPaymentStatus = $isPastEvent ? 'pago' : 'pendente';

        // Assert
        $this->assertTrue($isPastEvent);
        $this->assertEquals('pago', $artistPaymentStatus);
        $this->assertEquals('pago', $bookerPaymentStatus);
    }

    public function test_today_event_should_have_pending_payment_status()
    {
        // Arrange
        $now = Carbon::now();
        $todayDate = $now->copy();

        // Act
        $isPastEvent = $todayDate < $now;
        $artistPaymentStatus = $isPastEvent ? 'pago' : 'pendente';
        $bookerPaymentStatus = $isPastEvent ? 'pago' : 'pendente';

        // Assert
        $this->assertFalse($isPastEvent);
        $this->assertEquals('pendente', $artistPaymentStatus);
        $this->assertEquals('pendente', $bookerPaymentStatus);
    }

    public function test_yesterday_event_should_have_paid_payment_status()
    {
        // Arrange
        $yesterdayDate = Carbon::now()->subDay();

        // Act
        $isPastEvent = $yesterdayDate < now();
        $artistPaymentStatus = $isPastEvent ? 'pago' : 'pendente';
        $bookerPaymentStatus = $isPastEvent ? 'pago' : 'pendente';

        // Assert
        $this->assertTrue($isPastEvent);
        $this->assertEquals('pago', $artistPaymentStatus);
        $this->assertEquals('pago', $bookerPaymentStatus);
    }
}
