<?php

namespace Tests\Unit;

use App\Models\Gig;
use App\Services\AuditService;
use App\Services\GigFinancialCalculatorService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    private AuditService $auditService;

    private $financialCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->financialCalculator = Mockery::mock(GigFinancialCalculatorService::class);
        $this->auditService = new AuditService($this->financialCalculator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_validates_gig_integrity_with_valid_gig()
    {
        $payments = collect([
            (object) ['currency' => 'USD'],
        ]);

        $gig = Mockery::mock(Gig::class);
        $gig->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $gig->shouldReceive('getAttribute')->with('cache_value')->andReturn(1000.00);
        $gig->shouldReceive('getAttribute')->with('currency')->andReturn('USD');
        $gig->shouldReceive('getAttribute')->with('payments')->andReturn($payments);
        $gig->shouldReceive('__get')->with('cache_value')->andReturn(1000.00);
        $gig->shouldReceive('__get')->with('currency')->andReturn('USD');
        $gig->shouldReceive('__get')->with('payments')->andReturn($payments);
        $gig->shouldReceive('relationLoaded')->with('payments')->andReturn(true);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['issues']);
        $this->assertArrayHasKey('validated_at', $result);
    }

    #[Test]
    public function it_validates_gig_integrity_with_invalid_contract_value()
    {
        $payments = collect();

        $gig = Mockery::mock(Gig::class);
        $gig->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $gig->shouldReceive('getAttribute')->with('cache_value')->andReturn(0);
        $gig->shouldReceive('getAttribute')->with('currency')->andReturn('USD');
        $gig->shouldReceive('getAttribute')->with('payments')->andReturn($payments);
        $gig->shouldReceive('__get')->with('cache_value')->andReturn(0);
        $gig->shouldReceive('__get')->with('currency')->andReturn('USD');
        $gig->shouldReceive('__get')->with('payments')->andReturn($payments);
        $gig->shouldReceive('relationLoaded')->with('payments')->andReturn(true);

        $result = $this->auditService->validateGigIntegrity($gig);

        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['issues']);
        $this->assertContains('Valor do contrato não definido ou inválido', $result['issues']);
        $this->assertContains('Nenhum pagamento registrado', $result['issues']);
        $this->assertArrayHasKey('validated_at', $result);
    }
}
