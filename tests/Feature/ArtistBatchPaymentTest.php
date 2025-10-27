<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Settlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistBatchPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Artist $artist;

    protected Booker $booker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->artist = Artist::factory()->create();
        $this->booker = Booker::factory()->create();
    }

    #[Test]
    public function it_can_settle_batch_artist_payments_for_realized_gigs_with_confirmed_costs()
    {
        // Create 3 realized gigs with all costs confirmed
        $gigs = Gig::factory()
            ->count(3)
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pendente',
            ]);

        // Add confirmed costs to all gigs
        $costCenter = CostCenter::factory()->create();
        foreach ($gigs as $gig) {
            GigCost::factory()->create([
                'gig_id' => $gig->id,
                'cost_center_id' => $costCenter->id,
                'value' => 1000,
                'currency' => 'BRL',
                'is_confirmed' => true,
            ]);
        }

        $paymentDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => $gigs->pluck('id')->toArray(),
            'payment_date' => $paymentDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify all gigs are marked as paid
        foreach ($gigs as $gig) {
            $gig->refresh();
            $this->assertEquals('pago', $gig->artist_payment_status);

            // Verify settlement record was created
            $settlement = Settlement::where('gig_id', $gig->id)->first();
            $this->assertNotNull($settlement);
            $this->assertNotNull($settlement->artist_payment_value);
            $this->assertNotNull($settlement->artist_payment_paid_at);
            $this->assertEquals($paymentDate, $settlement->artist_payment_paid_at->format('Y-m-d'));
            $this->assertEquals($paymentDate, $settlement->settlement_date->format('Y-m-d'));
        }
    }

    #[Test]
    public function it_cannot_settle_batch_payments_for_future_gigs()
    {
        // Create future gig
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->addDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pendente',
            ]);

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify gig is still pending
        $gig->refresh();
        $this->assertEquals('pendente', $gig->artist_payment_status);
    }

    #[Test]
    public function it_cannot_settle_batch_payments_with_unconfirmed_costs()
    {
        // Create realized gig with unconfirmed cost
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pendente',
            ]);

        $costCenter = CostCenter::factory()->create();
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 1000,
            'currency' => 'BRL',
            'is_confirmed' => false, // Not confirmed
        ]);

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify gig is still pending
        $gig->refresh();
        $this->assertEquals('pendente', $gig->artist_payment_status);
    }

    #[Test]
    public function it_cannot_settle_already_paid_gigs()
    {
        // Create already paid gig
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pago',
            ]);

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_can_unsettle_batch_artist_payments()
    {
        // Create paid gigs
        $gigs = Gig::factory()
            ->count(2)
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pago',
            ]);

        // Create settlement records
        foreach ($gigs as $gig) {
            Settlement::create([
                'gig_id' => $gig->id,
                'settlement_date' => Carbon::now(),
                'artist_payment_value' => 8000,
                'artist_payment_paid_at' => Carbon::now(),
            ]);
        }

        $response = $this->actingAs($this->user)->patch(route('artists.payments.unsettleBatch'), [
            'gig_ids' => $gigs->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify all gigs are marked as pending
        foreach ($gigs as $gig) {
            $gig->refresh();
            $this->assertEquals('pendente', $gig->artist_payment_status);

            // Verify settlement record was nullified
            $settlement = Settlement::where('gig_id', $gig->id)->first();
            $this->assertNotNull($settlement);
            $this->assertNull($settlement->artist_payment_value);
            $this->assertNull($settlement->artist_payment_paid_at);
        }
    }

    #[Test]
    public function it_cannot_unsettle_gigs_that_are_not_paid()
    {
        // Create pending gig
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pendente',
            ]);

        $response = $this->actingAs($this->user)->patch(route('artists.payments.unsettleBatch'), [
            'gig_ids' => [$gig->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_validates_payment_date_is_required()
    {
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pendente',
            ]);

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            // Missing payment_date
        ]);

        $response->assertSessionHasErrors('payment_date');
    }

    #[Test]
    public function it_validates_payment_date_cannot_be_future()
    {
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'artist_payment_status' => 'pendente',
            ]);

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => Carbon::now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('payment_date');
    }

    #[Test]
    public function it_validates_gig_ids_are_required()
    {
        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            // Missing gig_ids
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('gig_ids');
    }

    #[Test]
    public function it_calculates_artist_net_payout_correctly_for_batch_payment()
    {
        // Create realized gig with specific values
        $gig = Gig::factory()
            ->for($this->artist)
            ->for($this->booker)
            ->create([
                'gig_date' => Carbon::now()->subDays(10),
                'cache_value' => 10000,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20, // 20% commission
                'artist_payment_status' => 'pendente',
            ]);

        // Add confirmed cost
        $costCenter = CostCenter::factory()->create();
        GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $costCenter->id,
            'value' => 1000,
            'currency' => 'BRL',
            'is_confirmed' => true,
        ]);

        $paymentDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('artists.payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => $paymentDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Expected calculation:
        // Gross Cash = Contract Value (10000) - Costs (1000) = 9000
        // Agency Commission = 20% of 9000 = 1800
        // Artist Net Payout = 9000 - 1800 = 7200
        $settlement = Settlement::where('gig_id', $gig->id)->first();
        $this->assertNotNull($settlement);
        $this->assertEquals(7200.00, (float) $settlement->artist_payment_value);
    }
}
