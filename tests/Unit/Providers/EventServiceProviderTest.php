<?php

namespace Tests\Unit\Providers;

use App\Events\PaymentSaved;
use App\Listeners\UpdateGigPaymentStatus;
use App\Providers\EventServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    private EventServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new EventServiceProvider($this->app);
    }

    #[Test]
    public function it_has_correct_event_listener_mappings()
    {
        $expectedMappings = [
            Registered::class => [
                SendEmailVerificationNotification::class,
            ],
            PaymentSaved::class => [
                UpdateGigPaymentStatus::class,
            ],
        ];

        $reflection = new \ReflectionClass($this->provider);
        $listenProperty = $reflection->getProperty('listen');
        $listenProperty->setAccessible(true);
        $actualMappings = $listenProperty->getValue($this->provider);

        $this->assertEquals($expectedMappings, $actualMappings);
    }

    #[Test]
    public function it_does_not_discover_events_automatically()
    {
        $this->assertFalse($this->provider->shouldDiscoverEvents());
    }

    #[Test]
    public function it_can_boot_without_errors()
    {
        // Test that the boot method can be called without throwing exceptions
        $this->provider->boot();

        // If we reach this point, boot() executed successfully
        $this->assertTrue(true);
    }

    #[Test]
    public function it_has_payment_saved_event_in_listen_array()
    {
        $reflection = new \ReflectionClass($this->provider);
        $listenProperty = $reflection->getProperty('listen');
        $listenProperty->setAccessible(true);
        $listenMappings = $listenProperty->getValue($this->provider);

        $this->assertArrayHasKey(PaymentSaved::class, $listenMappings);
        $this->assertContains(UpdateGigPaymentStatus::class, $listenMappings[PaymentSaved::class]);
    }

    #[Test]
    public function it_has_registered_event_in_listen_array()
    {
        $reflection = new \ReflectionClass($this->provider);
        $listenProperty = $reflection->getProperty('listen');
        $listenProperty->setAccessible(true);
        $listenMappings = $listenProperty->getValue($this->provider);

        $this->assertArrayHasKey(Registered::class, $listenMappings);
        $this->assertContains(SendEmailVerificationNotification::class, $listenMappings[Registered::class]);
    }
}
