<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\Settlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettleBatchArtistPaymentsWithReimbursableExpensesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_includes_reimbursable_expenses_in_batch_artist_payment_settlement()
    {
        $artist = Artist::factory()->create(['name' => 'Test Artist']);
        $booker = Booker::factory()->create();
        $costCenter = CostCenter::factory()->create();

        // Cria gig com cachê de R$ 10.000 e 20% de comissão
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDay(), // Evento já aconteceu
            'cache_value' => 10000,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20,
            'artist_payment_status' => 'pendente',
        ]);

        // Adiciona despesa reembolsável de R$ 500 (confirmada)
        $gig->gigCosts()->create([
            'cost_center_id' => $costCenter->id,
            'value' => 500,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'is_invoice' => true, // Despesa reembolsável
            'description' => 'Hotel artista',
        ]);

        // Adiciona despesa não-reembolsável de R$ 300 (confirmada)
        $gig->gigCosts()->create([
            'cost_center_id' => $costCenter->id,
            'value' => 300,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'is_invoice' => false, // Despesa NÃO reembolsável
            'description' => 'Produção',
        ]);

        $paymentDate = Carbon::now()->format('Y-m-d');

        // Executa pagamento em massa
        $response = $this->actingAs($this->user)->post(route('reports.artist-payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => $paymentDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Recarrega o gig
        $gig->refresh();

        // Verifica que status foi atualizado
        $this->assertEquals('pago', $gig->artist_payment_status);

        // Verifica Settlement criado
        $settlement = Settlement::where('gig_id', $gig->id)->first();
        $this->assertNotNull($settlement);

        // Cálculos esperados:
        // Cachê bruto = 10000 - 500 (reemb) - 300 (não-reemb) = 9200
        // Comissão agência = 9200 * 0.20 = 1840
        // Cachê líquido = 9200 - 1840 = 7360
        // Valor total a pagar = 7360 + 500 (reembolsável) = 7860

        $this->assertEquals(7860, $settlement->artist_payment_value);
        $this->assertNotNull($settlement->artist_payment_paid_at);
        $this->assertEquals($paymentDate, $settlement->artist_payment_paid_at->format('Y-m-d'));
    }

    #[Test]
    public function it_calculates_correct_payment_value_without_reimbursable_expenses()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        // Gig sem despesas reembolsáveis
        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDay(),
            'cache_value' => 5000,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20,
            'artist_payment_status' => 'pendente',
        ]);

        $paymentDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('reports.artist-payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => $paymentDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settlement = Settlement::where('gig_id', $gig->id)->first();
        $this->assertNotNull($settlement);

        // Cachê bruto = 5000
        // Comissão = 1000
        // Cachê líquido = 4000
        // Sem despesas reembolsáveis, o total = 4000
        $this->assertEquals(4000, $settlement->artist_payment_value);
    }

    #[Test]
    public function it_handles_multiple_gigs_with_mixed_reimbursable_expenses_in_batch_payment()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        $costCenter = CostCenter::factory()->create();

        // Gig 1: Com despesas reembolsáveis
        $gig1 = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDay(),
            'cache_value' => 8000,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20,
            'artist_payment_status' => 'pendente',
        ]);

        $gig1->gigCosts()->create([
            'cost_center_id' => $costCenter->id,
            'value' => 400,
            'currency' => 'BRL',
            'is_confirmed' => true,
            'is_invoice' => true,
            'description' => 'Hotel',
        ]);

        // Gig 2: Sem despesas
        $gig2 = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDay(),
            'cache_value' => 6000,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20,
            'artist_payment_status' => 'pendente',
        ]);

        $paymentDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('reports.artist-payments.settleBatch'), [
            'gig_ids' => [$gig1->id, $gig2->id],
            'payment_date' => $paymentDate,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifica Settlement do Gig 1 (com despesas reembolsáveis)
        $settlement1 = Settlement::where('gig_id', $gig1->id)->first();
        // Cachê bruto = 8000 - 400 = 7600
        // Comissão = 7600 * 0.20 = 1520
        // Cachê líquido = 7600 - 1520 = 6080
        // Total = 6080 + 400 = 6480
        $this->assertEquals(6480, $settlement1->artist_payment_value);

        // Verifica Settlement do Gig 2 (sem despesas)
        $settlement2 = Settlement::where('gig_id', $gig2->id)->first();
        // Cachê bruto = 6000
        // Comissão = 1200
        // Total = 4800
        $this->assertEquals(4800, $settlement2->artist_payment_value);
    }

    #[Test]
    public function it_does_not_include_unconfirmed_reimbursable_expenses_in_payment()
    {
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();
        $costCenter = CostCenter::factory()->create();

        $gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'gig_date' => Carbon::now()->subDay(),
            'cache_value' => 5000,
            'currency' => 'BRL',
            'agency_commission_type' => 'PERCENT',
            'agency_commission_rate' => 20,
            'artist_payment_status' => 'pendente',
        ]);

        // Despesa reembolsável NÃO confirmada (não deve ser incluída)
        $gig->gigCosts()->create([
            'cost_center_id' => $costCenter->id,
            'value' => 300,
            'currency' => 'BRL',
            'is_confirmed' => false, // NÃO confirmada
            'is_invoice' => true,
            'description' => 'Hotel pendente confirmação',
        ]);

        $paymentDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('reports.artist-payments.settleBatch'), [
            'gig_ids' => [$gig->id],
            'payment_date' => $paymentDate,
        ]);

        $response->assertRedirect();

        $settlement = Settlement::where('gig_id', $gig->id)->first();

        // Despesa não confirmada não afeta o cálculo
        // Cachê bruto = 5000 (sem deduções)
        // Comissão = 1000
        // Total = 4000 (sem despesas reembolsáveis)
        $this->assertEquals(4000, $settlement->artist_payment_value);
    }
}
