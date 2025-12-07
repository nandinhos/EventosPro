<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Gig;
use App\Models\Settlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistSettlementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Artist $artist;
    private Gig $gig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->artist = Artist::factory()->create();
        $this->gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'gig_date' => Carbon::today()->subDays(7),
            'artist_payment_status' => 'pendente',
        ]);
    }

    #[Test]
    public function it_displays_index_page_with_stage_metrics(): void
    {
        $response = $this->actingAs($this->user)->get(route('artists.settlements.index'));

        $response->assertStatus(200);
        $response->assertViewHas('stageMetrics');
        $response->assertViewHas('gigs');
        $response->assertViewHas('pendingTotal');
    }

    #[Test]
    public function it_can_filter_by_settlement_stage(): void
    {
        // Create gig with specific stage
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
            'settlement_sent_at' => now(),
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('artists.settlements.index', ['stage' => 'fechamento_enviado']));

        $response->assertStatus(200);
        $response->assertSee($this->artist->name);
    }

    #[Test]
    public function it_can_send_settlement_and_update_stage(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.send', $this->gig), [
                'communication_notes' => 'Enviado por email',
            ]);

        $response->assertRedirect(route('artists.settlements.index'));
        $response->assertSessionHas('success');

        $this->gig->refresh();
        $this->assertNotNull($this->gig->settlement);
        $this->assertEquals(Settlement::STAGE_FECHAMENTO_ENVIADO, $this->gig->settlement->settlement_stage);
        $this->assertNotNull($this->gig->settlement->settlement_sent_at);
        $this->assertEquals('Enviado por email', $this->gig->settlement->communication_notes);
    }

    #[Test]
    public function it_can_mark_documentation_received(): void
    {
        // First create settlement in "enviado" stage
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
            'settlement_sent_at' => now(),
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.receiveDocument', $this->gig), [
                'documentation_type' => 'nf',
                'documentation_number' => 'NF-123456',
            ]);

        $response->assertRedirect(route('artists.settlements.index'));
        $response->assertSessionHas('success');

        $settlement->refresh();
        $this->assertEquals(Settlement::STAGE_DOCUMENTACAO_RECEBIDA, $settlement->settlement_stage);
        $this->assertEquals('nf', $settlement->documentation_type);
        $this->assertEquals('NF-123456', $settlement->documentation_number);
        $this->assertNotNull($settlement->documentation_received_at);
    }

    #[Test]
    public function it_can_upload_documentation_file(): void
    {
        Storage::fake('public');

        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
            'settlement_sent_at' => now(),
            'settlement_date' => now()->toDateString(),
        ]);

        $file = UploadedFile::fake()->create('nota_fiscal.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.receiveDocument', $this->gig), [
                'documentation_type' => 'nf',
                'documentation_file' => $file,
            ]);

        $response->assertRedirect(route('artists.settlements.index'));

        $settlement->refresh();
        $this->assertNotNull($settlement->documentation_file_path);
        Storage::disk('public')->assertExists($settlement->documentation_file_path);
    }

    #[Test]
    public function it_cannot_send_settlement_twice(): void
    {
        // Create settlement already in "enviado" stage
        Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
            'settlement_sent_at' => now(),
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.send', $this->gig));

        $response->assertStatus(422);
    }

    #[Test]
    public function it_cannot_receive_documentation_in_wrong_stage(): void
    {
        // Create settlement in "aguardando" stage (not ready for documentation)
        Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.receiveDocument', $this->gig), [
                'documentation_type' => 'nf',
            ]);

        $response->assertRedirect(route('artists.settlements.index'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function it_can_send_batch_settlements(): void
    {
        // Create additional gigs
        $gig2 = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'gig_date' => Carbon::today()->subDays(5),
            'artist_payment_status' => 'pendente',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('artists.settlements.sendBatch'), [
                'gig_ids' => [$this->gig->id, $gig2->id],
            ]);

        $response->assertRedirect(route('artists.settlements.index'));
        $response->assertSessionHas('success');

        $this->gig->refresh();
        $gig2->refresh();

        $this->assertEquals(Settlement::STAGE_FECHAMENTO_ENVIADO, $this->gig->settlement->settlement_stage);
        $this->assertEquals(Settlement::STAGE_FECHAMENTO_ENVIADO, $gig2->settlement->settlement_stage);
    }

    #[Test]
    public function settlement_stage_labels_are_correct(): void
    {
        $settlement = new Settlement(['settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA]);
        $this->assertEquals('Aguardando Conferência', $settlement->stage_label);

        $settlement->settlement_stage = Settlement::STAGE_FECHAMENTO_ENVIADO;
        $this->assertEquals('Ag. NF/Recibo', $settlement->stage_label);

        $settlement->settlement_stage = Settlement::STAGE_DOCUMENTACAO_RECEBIDA;
        $this->assertEquals('Pronto p/ Pagar', $settlement->stage_label);

        $settlement->settlement_stage = Settlement::STAGE_PAGO;
        $this->assertEquals('Pago', $settlement->stage_label);
    }

    #[Test]
    public function settlement_stage_colors_are_correct(): void
    {
        $settlement = new Settlement(['settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA]);
        $this->assertEquals('gray', $settlement->stage_color);

        $settlement->settlement_stage = Settlement::STAGE_FECHAMENTO_ENVIADO;
        $this->assertEquals('blue', $settlement->stage_color);

        $settlement->settlement_stage = Settlement::STAGE_DOCUMENTACAO_RECEBIDA;
        $this->assertEquals('yellow', $settlement->stage_color);

        $settlement->settlement_stage = Settlement::STAGE_PAGO;
        $this->assertEquals('green', $settlement->stage_color);
    }

    #[Test]
    public function it_validates_documentation_type_is_required(): void
    {
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
            'settlement_sent_at' => now(),
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.receiveDocument', $this->gig), [
                'documentation_number' => '12345',
            ]);

        $response->assertSessionHasErrors(['documentation_type']);
    }

    #[Test]
    public function it_cannot_settle_payment_from_wrong_stage(): void
    {
        // Tentar pagar quando ainda está em aguardando_conferencia
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('artists.settlements.settle', $this->gig), [
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('error');
        $settlement->refresh();
        $this->assertNotEquals(Settlement::STAGE_PAGO, $settlement->settlement_stage);
    }

    #[Test]
    public function it_cannot_settle_payment_from_fechamento_enviado(): void
    {
        // Tentar pagar quando ainda está em fechamento_enviado
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
            'settlement_sent_at' => now(),
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('artists.settlements.settle', $this->gig), [
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('error');
        $settlement->refresh();
        $this->assertEquals(Settlement::STAGE_FECHAMENTO_ENVIADO, $settlement->settlement_stage);
    }

    #[Test]
    public function it_can_settle_payment_from_documentacao_recebida(): void
    {
        // Registrar pagamento apenas quando está em documentacao_recebida
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_DOCUMENTACAO_RECEBIDA,
            'settlement_sent_at' => now(),
            'documentation_received_at' => now(),
            'documentation_type' => 'nf',
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('artists.settlements.settle', $this->gig), [
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('success');
        $settlement->refresh();
        $this->assertEquals(Settlement::STAGE_PAGO, $settlement->settlement_stage);
        $this->assertEquals('pago', $this->gig->fresh()->artist_payment_status);
    }

    #[Test]
    public function it_cannot_revert_from_aguardando_conferencia(): void
    {
        $settlement = Settlement::create([
            'gig_id' => $this->gig->id,
            'settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
            'settlement_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('artists.settlements.revert', $this->gig));

        $response->assertSessionHas('error');
    }
}
