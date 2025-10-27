<?php

namespace Tests\Unit\Services;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Settlement;
use App\Services\CommissionPaymentValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommissionPaymentValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommissionPaymentValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CommissionPaymentValidationService;
    }

    #[Test]
    public function it_validates_booker_commission_payment_for_past_event()
    {
        $gig = $this->createGigWithDate(Carbon::yesterday());

        $result = $this->service->validateBookerCommissionPayment($gig);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Evento já realizado', $result['message']);
    }

    #[Test]
    public function it_rejects_booker_commission_payment_for_future_event_without_exceptions()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());

        $result = $this->service->validateBookerCommissionPayment($gig, false);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Não é possível pagar comissão para evento futuro', $result['message']);
        $this->assertStringContainsString(Carbon::tomorrow()->isoFormat('L'), $result['message']);
    }

    #[Test]
    public function it_allows_booker_commission_payment_for_future_event_with_exceptions_and_authorized_exception()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());
        $this->createSettlementWithException($gig, 'Pagamento exceção autorizado');

        $result = $this->service->validateBookerCommissionPayment($gig, true);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Evento futuro com exceção autorizada', $result['message']);
    }

    #[Test]
    public function it_rejects_booker_commission_payment_for_future_event_with_exceptions_but_no_authorization()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());

        $result = $this->service->validateBookerCommissionPayment($gig, true);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Evento futuro sem exceção autorizada', $result['message']);
        $this->assertStringContainsString(Carbon::tomorrow()->isoFormat('L'), $result['message']);
    }

    #[Test]
    public function it_validates_artist_payment_using_same_logic_as_booker()
    {
        $gig = $this->createGigWithDate(Carbon::yesterday());

        $result = $this->service->validateArtistPayment($gig);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Evento já realizado', $result['message']);
    }

    #[Test]
    public function it_validates_batch_payment_with_mixed_valid_and_invalid_gigs()
    {
        $validGig1 = $this->createGigWithDate(Carbon::yesterday());
        $validGig2 = $this->createGigWithDate(Carbon::today()->subDays(2));
        $invalidGig1 = $this->createGigWithDate(Carbon::tomorrow());
        $invalidGig2 = $this->createGigWithDate(Carbon::today()->addDays(2));

        $gigs = collect([$validGig1, $validGig2, $invalidGig1, $invalidGig2]);

        $result = $this->service->validateBatchPayment($gigs, false);

        $this->assertCount(2, $result['valid_gigs']);
        $this->assertCount(2, $result['invalid_gigs']);
        $this->assertCount(2, $result['errors']);

        $this->assertTrue($result['valid_gigs']->contains($validGig1));
        $this->assertTrue($result['valid_gigs']->contains($validGig2));
        $this->assertTrue($result['invalid_gigs']->contains($invalidGig1));
        $this->assertTrue($result['invalid_gigs']->contains($invalidGig2));

        $this->assertStringContainsString("Gig #{$invalidGig1->id}", $result['errors'][0]);
        $this->assertStringContainsString("Gig #{$invalidGig2->id}", $result['errors'][1]);
    }

    #[Test]
    public function it_validates_batch_payment_with_all_valid_gigs()
    {
        $gig1 = $this->createGigWithDate(Carbon::yesterday());
        $gig2 = $this->createGigWithDate(Carbon::today()->subDays(2));

        $gigs = collect([$gig1, $gig2]);

        $result = $this->service->validateBatchPayment($gigs, false);

        $this->assertCount(2, $result['valid_gigs']);
        $this->assertCount(0, $result['invalid_gigs']);
        $this->assertCount(0, $result['errors']);
    }

    #[Test]
    public function it_validates_batch_payment_with_exceptions_allowed()
    {
        $validGig = $this->createGigWithDate(Carbon::yesterday());
        $futureGigWithException = $this->createGigWithDate(Carbon::tomorrow());
        $this->createSettlementWithException($futureGigWithException, 'Pagamento antecipado autorizado');
        $futureGigWithoutException = $this->createGigWithDate(Carbon::today()->addDays(2));

        $gigs = collect([$validGig, $futureGigWithException, $futureGigWithoutException]);

        $result = $this->service->validateBatchPayment($gigs, true);

        $this->assertCount(2, $result['valid_gigs']);
        $this->assertCount(1, $result['invalid_gigs']);
        $this->assertCount(1, $result['errors']);

        $this->assertTrue($result['valid_gigs']->contains($validGig));
        $this->assertTrue($result['valid_gigs']->contains($futureGigWithException));
        $this->assertTrue($result['invalid_gigs']->contains($futureGigWithoutException));
    }

    #[Test]
    public function it_detects_payment_exception_with_various_keywords()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());

        // Test with "exceção"
        $this->createSettlementWithException($gig, 'Pagamento com exceção especial');
        $this->assertTrue($this->hasPaymentExceptionPublic($gig));

        // Test with "excecao" (without accent)
        $gig2 = $this->createGigWithDate(Carbon::tomorrow());
        $this->createSettlementWithException($gig2, 'Pagamento com excecao especial');
        $this->assertTrue($this->hasPaymentExceptionPublic($gig2));

        // Test with "antecipado"
        $gig3 = $this->createGigWithDate(Carbon::tomorrow());
        $this->createSettlementWithException($gig3, 'Pagamento antecipado');
        $this->assertTrue($this->hasPaymentExceptionPublic($gig3));

        // Test with "autorizado"
        $gig4 = $this->createGigWithDate(Carbon::tomorrow());
        $this->createSettlementWithException($gig4, 'Pagamento autorizado pela gerência');
        $this->assertTrue($this->hasPaymentExceptionPublic($gig4));
    }

    #[Test]
    public function it_returns_false_for_payment_exception_when_no_settlement_exists()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());

        $this->assertFalse($this->hasPaymentExceptionPublic($gig));
    }

    #[Test]
    public function it_returns_false_for_payment_exception_when_settlement_has_no_exception_keywords()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());
        $this->createSettlementWithException($gig, 'Pagamento normal sem palavras especiais');

        $this->assertFalse($this->hasPaymentExceptionPublic($gig));
    }

    #[Test]
    public function it_creates_payment_exception_successfully()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());
        $reason = 'Evento especial com aprovação da diretoria';
        $authorizedBy = 'João Silva';

        $result = $this->service->createPaymentException($gig, $reason, $authorizedBy);

        $this->assertTrue($result);

        $gig->refresh();
        $settlement = $gig->settlement;

        $this->assertNotNull($settlement);
        $this->assertStringContainsString('[EXCEÇÃO AUTORIZADA', $settlement->notes);
        $this->assertStringContainsString($reason, $settlement->notes);
        $this->assertStringContainsString($authorizedBy, $settlement->notes);
        $this->assertStringContainsString(now()->isoFormat('L'), $settlement->notes);
    }

    #[Test]
    public function it_creates_payment_exception_for_gig_with_existing_settlement()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());
        $existingNotes = 'Notas existentes do settlement';
        $this->createSettlementWithException($gig, $existingNotes);

        $reason = 'Nova exceção autorizada';
        $authorizedBy = 'Maria Santos';

        $result = $this->service->createPaymentException($gig, $reason, $authorizedBy);

        $this->assertTrue($result);

        $gig->refresh();
        $settlement = $gig->settlement;

        $this->assertStringContainsString($existingNotes, $settlement->notes);
        $this->assertStringContainsString('[EXCEÇÃO AUTORIZADA', $settlement->notes);
        $this->assertStringContainsString($reason, $settlement->notes);
        $this->assertStringContainsString($authorizedBy, $settlement->notes);
    }

    #[Test]
    public function it_appends_to_existing_settlement_notes()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());

        // Criar settlement com notas existentes
        Settlement::factory()->create([
            'gig_id' => $gig->id,
            'notes' => 'Notas existentes do settlement',
        ]);

        $result = $this->service->createPaymentException($gig, 'Nova exceção', 'João Silva');

        $this->assertTrue($result);

        $gig->refresh();
        $settlement = $gig->settlement;

        $this->assertStringContainsString('Notas existentes do settlement', $settlement->notes);
        $this->assertStringContainsString('[EXCEÇÃO AUTORIZADA', $settlement->notes);
        $this->assertStringContainsString('Nova exceção', $settlement->notes);
        $this->assertStringContainsString('João Silva', $settlement->notes);
    }

    #[Test]
    public function it_handles_exception_when_creating_payment_exception_fails()
    {
        $gig = $this->createGigWithDate(Carbon::tomorrow());

        // Mock do Log para capturar o erro
        Log::shouldReceive('error')
            ->with(Mockery::pattern('/Erro ao criar exceção de pagamento para Gig \d+:/'))
            ->once();

        // Simular falha ao salvar usando um mock do Settlement
        $mockGig = Mockery::mock(Gig::class);
        $mockGig->shouldReceive('getAttribute')->with('settlement')->andReturn(null);
        $mockGig->shouldReceive('getAttribute')->with('id')->andReturn(999);

        $mockSettlement = Mockery::mock(\App\Models\Settlement::class);
        $mockSettlement->shouldReceive('save')->andThrow(new \Exception('Database error'));

        // Mock do construtor Settlement
        $this->app->bind(\App\Models\Settlement::class, function () use ($mockSettlement) {
            return $mockSettlement;
        });

        $result = $this->service->createPaymentException($mockGig, 'Test reason', 'Test author');

        $this->assertFalse($result);
    }

    /**
     * Helper method to create a gig with specific date
     */
    private function createGigWithDate(Carbon $date): Gig
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        return Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => $date,
        ]);
    }

    /**
     * Helper method to create settlement with exception notes
     */
    private function createSettlementWithException(Gig $gig, string $notes): Settlement
    {
        return Settlement::factory()->create([
            'gig_id' => $gig->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Helper method to access private hasPaymentException method
     */
    private function hasPaymentExceptionPublic(Gig $gig): bool
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('hasPaymentException');
        $method->setAccessible(true);

        return $method->invoke($this->service, $gig);
    }
}
