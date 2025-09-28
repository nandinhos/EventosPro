<?php

namespace Tests\Unit\Services;

use App\Models\Gig;
use App\Models\Payment;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;    protected AuditService $auditService;    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
    }

    #[Test]
    public function it_validates_gig_integrity_with_valid_gig()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'currency' => 'USD',
            'due_value' => 500.00,
        ]);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['issues']);
        $this->assertArrayHasKey('validated_at', $result);
    }

    #[Test]
    public function it_validates_gig_integrity_with_invalid_contract_value()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 0,
            'currency' => 'USD',
        ]);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertFalse($result['is_valid']);
        $this->assertContains('Valor do contrato não definido ou inválido', $result['issues']);
    }

    #[Test]
    public function it_validates_gig_integrity_with_missing_currency()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD', // Usar moeda válida
        ]);

        // Forçar currency para empty string após criação
        $gig->update(['currency' => '']);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertFalse($result['is_valid']);
        $this->assertContains('Moeda não definida', $result['issues']);
    }

    #[Test]
    public function it_validates_gig_integrity_with_no_payments()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertFalse($result['is_valid']);
        $this->assertContains('Nenhum pagamento registrado', $result['issues']);
    }

    #[Test]
    public function it_validates_gig_integrity_with_currency_inconsistency()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'currency' => 'EUR', // Moeda diferente do contrato
            'due_value' => 500.00,
        ]);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertFalse($result['is_valid']);
        $this->assertContains('Inconsistência de moedas entre contrato e pagamentos', $result['issues']);
    }

    #[Test]
    public function it_calculates_basic_audit_data_structure()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        Payment::factory()->create([
            'gig_id' => $gig->id,
            'currency' => 'USD',
            'due_value' => 500.00,
            'confirmed_at' => now(),
        ]);

        $result = $this->auditService->calculateGigAuditData($gig);

        // Verificar estrutura básica do resultado
        $this->assertArrayHasKey('valor_contrato', $result);
        $this->assertArrayHasKey('total_pago', $result);
        $this->assertArrayHasKey('total_pendente', $result);
        $this->assertArrayHasKey('divergencia', $result);
        $this->assertArrayHasKey('divergencia_percentual', $result);
        $this->assertArrayHasKey('observacao', $result);
        $this->assertArrayHasKey('tem_divergencia', $result);
        $this->assertArrayHasKey('status_divergencia', $result);
        $this->assertArrayHasKey('analise_detalhada', $result);
        $this->assertArrayHasKey('ultima_atualizacao', $result);

        // Verificar tipos de dados
        $this->assertIsFloat($result['valor_contrato']);
        $this->assertIsFloat($result['total_pago']);
        $this->assertIsFloat($result['total_pendente']);
        $this->assertIsFloat($result['divergencia']);
        $this->assertIsFloat($result['divergencia_percentual']);
        $this->assertIsString($result['observacao']);
        $this->assertIsBool($result['tem_divergencia']);
        $this->assertIsString($result['status_divergencia']);
        $this->assertIsArray($result['analise_detalhada']);
        $this->assertIsString($result['ultima_atualizacao']);
    }

    #[Test]
    public function it_calculates_bulk_audit_data()
    {
        $gigs = Gig::factory()->count(3)->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        foreach ($gigs as $gig) {
            Payment::factory()->create([
                'gig_id' => $gig->id,
                'currency' => 'USD',
                'due_value' => 500.00,
            ]);
        }

        $result = $this->auditService->calculateBulkAuditData($gigs);

        $this->assertCount(3, $result);
        foreach ($gigs as $gig) {
            $this->assertArrayHasKey($gig->id, $result);
            $this->assertArrayHasKey('valor_contrato', $result[$gig->id]);
            $this->assertArrayHasKey('divergencia', $result[$gig->id]);
        }
    }

    #[Test]
    public function it_generates_consolidated_report_structure()
    {
        $gigs = Gig::factory()->count(2)->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        foreach ($gigs as $gig) {
            Payment::factory()->create([
                'gig_id' => $gig->id,
                'currency' => 'USD',
                'due_value' => 500.00,
            ]);
        }

        $result = $this->auditService->generateConsolidatedReport($gigs);

        // Verificar estrutura do relatório
        $this->assertArrayHasKey('resumo', $result);
        $this->assertArrayHasKey('estatisticas_status', $result);
        $this->assertArrayHasKey('dados_detalhados', $result);
        $this->assertArrayHasKey('gerado_em', $result);

        // Verificar estrutura do resumo
        $resumo = $result['resumo'];
        $this->assertArrayHasKey('total_gigs', $resumo);
        $this->assertArrayHasKey('gigs_com_divergencia', $resumo);
        $this->assertArrayHasKey('percentual_divergencia', $resumo);
        $this->assertArrayHasKey('total_divergencia', $resumo);
        $this->assertArrayHasKey('total_contrato', $resumo);
        $this->assertArrayHasKey('total_pago', $resumo);
        $this->assertArrayHasKey('total_pendente', $resumo);

        $this->assertEquals(2, $resumo['total_gigs']);
        $this->assertEquals(2000.00, $resumo['total_contrato']);
    }

    #[Test]
    public function it_handles_gig_with_overdue_payments_in_detailed_analysis()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        // Criar pagamentos vencidos
        Payment::factory()->count(2)->create([
            'gig_id' => $gig->id,
            'currency' => 'USD',
            'due_value' => 250.00,
            'due_date' => Carbon::now()->subDays(5),
            'confirmed_at' => null,
        ]);

        $result = $this->auditService->calculateGigAuditData($gig);

        $this->assertEquals(2, $result['analise_detalhada']['pagamentos_vencidos']);
        $this->assertStringContainsString('🔴', $result['observacao']);
    }

    #[Test]
    public function it_handles_gig_with_multiple_currencies_in_detailed_analysis()
    {
        $gig = Gig::factory()->create([
            'cache_value' => 1000.00,
            'currency' => 'USD',
        ]);

        // Criar pagamentos em moedas diferentes
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'currency' => 'USD',
            'due_value' => 500.00,
        ]);
        Payment::factory()->create([
            'gig_id' => $gig->id,
            'currency' => 'EUR',
            'due_value' => 400.00,
        ]);

        $result = $this->auditService->calculateGigAuditData($gig);

        $this->assertTrue($result['analise_detalhada']['tem_multiplas_moedas']);
        $this->assertContains('USD', $result['analise_detalhada']['moedas_envolvidas']);
        $this->assertContains('EUR', $result['analise_detalhada']['moedas_envolvidas']);
        $this->assertStringContainsString('🌍', $result['observacao']);
    }
}
