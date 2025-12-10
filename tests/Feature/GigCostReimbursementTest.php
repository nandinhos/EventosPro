<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GigCostReimbursementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Gig $gig;
    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $artist = Artist::factory()->create();
        $this->gig = Gig::factory()->create([
            'artist_id' => $artist->id,
            'gig_date' => now()->subDays(5),
        ]);
        
        $this->costCenter = CostCenter::factory()->create([
            'name' => 'Despesas do Artista',
        ]);
    }

    /**
     * Teste: Ao criar despesa com is_invoice=true, reimbursement_stage é definido automaticamente
     */
    public function test_creating_cost_with_invoice_sets_reimbursement_stage(): void
    {
        $response = $this->actingAs($this->user)->postJson("/gigs/{$this->gig->id}/costs", [
            'cost_center_id' => $this->costCenter->id,
            'description' => 'Despesa de teste',
            'value' => 150.00,
            'currency' => 'BRL',
            'expense_date' => now()->format('Y-m-d'),
            'is_invoice' => true,
            'is_confirmed' => true,
        ]);

        $response->assertStatus(201);

        $cost = GigCost::where('gig_id', $this->gig->id)->first();
        $this->assertNotNull($cost);
        $this->assertTrue($cost->is_invoice);
        $this->assertEquals(GigCost::STAGE_AGUARDANDO_COMPROVANTE, $cost->reimbursement_stage);
    }

    /**
     * Teste: Ao criar despesa sem is_invoice, reimbursement_stage permanece NULL
     */
    public function test_creating_cost_without_invoice_keeps_stage_null(): void
    {
        $response = $this->actingAs($this->user)->postJson("/gigs/{$this->gig->id}/costs", [
            'cost_center_id' => $this->costCenter->id,
            'description' => 'Despesa normal',
            'value' => 100.00,
            'currency' => 'BRL',
            'expense_date' => now()->format('Y-m-d'),
            'is_invoice' => false,
        ]);

        $response->assertStatus(201);

        $cost = GigCost::where('gig_id', $this->gig->id)->first();
        $this->assertNull($cost->reimbursement_stage);
    }

    /**
     * Teste: Toggle invoice para ON define reimbursement_stage
     */
    public function test_toggle_invoice_on_sets_reimbursement_stage(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => false,
            'reimbursement_stage' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/gigs/{$this->gig->id}/costs/{$cost->id}/toggle-invoice");

        $response->assertStatus(200);

        $cost->refresh();
        $this->assertTrue($cost->is_invoice);
        $this->assertEquals(GigCost::STAGE_AGUARDANDO_COMPROVANTE, $cost->reimbursement_stage);
    }

    /**
     * Teste: Toggle invoice para OFF limpa reimbursement_stage
     */
    public function test_toggle_invoice_off_clears_reimbursement_stage(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => GigCost::STAGE_PAGO,
            'reimbursement_notes' => 'NF-123',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/gigs/{$this->gig->id}/costs/{$cost->id}/toggle-invoice");

        $response->assertStatus(200);

        $cost->refresh();
        $this->assertFalse($cost->is_invoice);
        $this->assertNull($cost->reimbursement_stage);
        $this->assertNull($cost->reimbursement_notes);
    }

    /**
     * Teste: Atualizar estágio para PAGO funciona corretamente
     */
    public function test_update_stage_to_pago(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => GigCost::STAGE_AGUARDANDO_COMPROVANTE,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/gigs/{$this->gig->id}/costs/{$cost->id}/reimbursement-stage", [
                'stage' => 'pago',
                'proof_type' => 'nf',
                'notes' => 'NF-456',
            ]);

        $response->assertStatus(200);

        $cost->refresh();
        $this->assertEquals(GigCost::STAGE_PAGO, $cost->reimbursement_stage);
        $this->assertEquals('nf', $cost->reimbursement_proof_type);
        $this->assertEquals('NF-456', $cost->reimbursement_notes);
        $this->assertNotNull($cost->reimbursement_confirmed_at);
        $this->assertEquals($this->user->id, $cost->reimbursement_confirmed_by);
    }

    /**
     * Teste: Reverter estágio para AGUARDANDO limpa dados de confirmação
     */
    public function test_revert_stage_to_aguardando(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => GigCost::STAGE_PAGO,
            'reimbursement_confirmed_at' => now(),
            'reimbursement_confirmed_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/gigs/{$this->gig->id}/costs/{$cost->id}/reimbursement-stage", [
                'stage' => 'aguardando_comprovante',
            ]);

        $response->assertStatus(200);

        $cost->refresh();
        $this->assertEquals(GigCost::STAGE_AGUARDANDO_COMPROVANTE, $cost->reimbursement_stage);
        $this->assertNull($cost->reimbursement_confirmed_at);
        $this->assertNull($cost->reimbursement_confirmed_by);
    }

    /**
     * Teste: Accessor effective_reimbursement_stage mapeia estágios legados para 'pago'
     */
    public function test_effective_stage_maps_legacy_stages_to_pago(): void
    {
        $costLegacy1 = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'reimbursement_stage' => 'comprovante_recebido',
        ]);

        $costLegacy2 = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'reimbursement_stage' => 'conferido',
        ]);

        $costLegacy3 = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'reimbursement_stage' => 'reembolsado',
        ]);

        $this->assertEquals('pago', $costLegacy1->effective_reimbursement_stage);
        $this->assertEquals('pago', $costLegacy2->effective_reimbursement_stage);
        $this->assertEquals('pago', $costLegacy3->effective_reimbursement_stage);
    }

    /**
     * Teste: Effective stage trata NULL como aguardando_comprovante
     */
    public function test_effective_stage_treats_null_as_aguardando(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'reimbursement_stage' => null,
        ]);

        $this->assertEquals(GigCost::STAGE_AGUARDANDO_COMPROVANTE, $cost->effective_reimbursement_stage);
    }

    /**
     * Teste: API rejeita estágios inválidos
     */
    public function test_update_stage_rejects_invalid_stages(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => GigCost::STAGE_AGUARDANDO_COMPROVANTE,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/gigs/{$this->gig->id}/costs/{$cost->id}/reimbursement-stage", [
                'stage' => 'comprovante_recebido', // Estágio legado - não aceito
            ]);

        $response->assertStatus(422);
    }

    /**
     * Teste: API rejeita atualizar estágio em despesa não-reembolsável
     */
    public function test_update_stage_rejects_non_reimbursable_cost(): void
    {
        $cost = GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/gigs/{$this->gig->id}/costs/{$cost->id}/reimbursement-stage", [
                'stage' => 'pago',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Teste: Despesas com NULL são tratadas como aguardando no contador
     */
    public function test_null_stage_counts_as_pending(): void
    {
        // Criar despesas com diferentes estados
        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => null, // NULL deve contar como aguardando
        ]);

        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => GigCost::STAGE_AGUARDANDO_COMPROVANTE,
        ]);

        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => GigCost::STAGE_PAGO,
        ]);

        // Consultar despesas como no controller
        $costs = $this->gig->gigCosts()->where('is_invoice', true)->get();
        
        $legacyPaidStages = ['comprovante_recebido', 'conferido', 'reembolsado', 'pago'];
        
        $pendingCount = $costs->filter(fn($c) => 
            !$c->reimbursement_stage || $c->reimbursement_stage === 'aguardando_comprovante'
        )->count();
        
        $paidCount = $costs->filter(fn($c) => 
            in_array($c->reimbursement_stage, $legacyPaidStages)
        )->count();

        // 2 aguardando (NULL + aguardando_comprovante), 1 pago
        $this->assertEquals(2, $pendingCount);
        $this->assertEquals(1, $paidCount);
    }

    /**
     * Teste: Estágios legados são contados como pago
     */
    public function test_legacy_stages_count_as_paid(): void
    {
        // Criar despesas com estágios legados
        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => 'comprovante_recebido',
        ]);

        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => 'conferido',
        ]);

        GigCost::factory()->create([
            'gig_id' => $this->gig->id,
            'cost_center_id' => $this->costCenter->id,
            'is_invoice' => true,
            'reimbursement_stage' => 'reembolsado',
        ]);

        $costs = $this->gig->gigCosts()->where('is_invoice', true)->get();
        
        $legacyPaidStages = ['comprovante_recebido', 'conferido', 'reembolsado', 'pago'];
        
        $paidCount = $costs->filter(fn($c) => 
            in_array($c->reimbursement_stage, $legacyPaidStages)
        )->count();

        // Todos os 3 devem ser contados como pago
        $this->assertEquals(3, $paidCount);
    }
}
