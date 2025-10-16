<?php

namespace Tests\Unit\Events;

use App\Events\PaymentSaved;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentSavedTest extends TestCase
{
    use RefreshDatabase;

    private Artist $artist;

    private Booker $booker;

    private Gig $gig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = Artist::factory()->create();
        $this->booker = Booker::factory()->create();
        $this->gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
        ]);
    }

    #[Test]
    public function it_can_be_instantiated_with_payment()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
        ]);

        $event = new PaymentSaved($payment);

        $this->assertInstanceOf(PaymentSaved::class, $event);
        $this->assertInstanceOf(Gig::class, $event->gig);
        $this->assertEquals($this->gig->id, $event->gig->id);
    }

    #[Test]
    public function it_loads_gig_from_payment()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
        ]);

        $event = new PaymentSaved($payment);

        $this->assertEquals($this->gig->id, $event->gig->id);
        $this->assertEquals($this->gig->artist_id, $event->gig->artist_id);
        $this->assertEquals($this->gig->booker_id, $event->gig->booker_id);
    }

    #[Test]
    public function it_returns_empty_broadcast_channels()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
        ]);

        $event = new PaymentSaved($payment);

        $this->assertEquals([], $event->broadcastOn());
    }

    #[Test]
    public function it_uses_dispatchable_trait()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
        ]);

        $event = new PaymentSaved($payment);

        $this->assertTrue(method_exists($event, 'dispatch'));
        $this->assertTrue(method_exists($event, 'dispatchIf'));
        $this->assertTrue(method_exists($event, 'dispatchUnless'));
    }

    #[Test]
    public function it_uses_serializes_models_trait()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
        ]);

        $event = new PaymentSaved($payment);

        // Verifica se o evento pode ser serializado (importante para queues)
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(PaymentSaved::class, $unserialized);
        $this->assertEquals($event->gig->id, $unserialized->gig->id);
    }

    #[Test]
    public function it_handles_payment_with_preloaded_gig()
    {
        $payment = Payment::factory()->create([
            'gig_id' => $this->gig->id,
        ]);

        // Pré-carrega a gig
        $payment->load('gig');

        $event = new PaymentSaved($payment);

        $this->assertEquals($this->gig->id, $event->gig->id);
    }
}
