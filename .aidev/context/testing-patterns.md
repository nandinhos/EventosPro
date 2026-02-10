# Padrões de Testes - EventosPro

> **Diretrizes de testes** para Laravel 12 + PHPUnit
> **Fontes**: `doc-project/TESTING.md`, `doc-project/TESTING_BEST_PRACTICES.md`
> **Versão**: 1.0 | **Atualizado**: 2026-02-10

---

## 🧪 Estrutura de Testes

```
tests/
├── Feature/           # Testes de Integração (Controllers, Rotas)
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── LogoutTest.php
│   ├── Gigs/
│   │   ├── GigCrudTest.php
│   │   └── GigFinancialsTest.php
│   ├── Payments/
│   ├── Costs/
│   └── Reports/
├── Unit/              # Testes Unitários (Services, Models, Helpers)
│   ├── Services/
│   │   ├── AuditServiceTest.php
│   │   ├── ArtistFinancialsServiceTest.php
│   │   ├── BookerFinancialsServiceTest.php
│   │   ├── ExchangeRateServiceTest.php
│   │   ├── GigFinancialCalculatorServiceTest.php
│   │   ├── FinancialProjectionServiceTest.php
│   │   ├── CashFlowProjectionServiceTest.php
│   │   ├── DreProjectionServiceTest.php
│   │   ├── FinancialReportServiceTest.php
│   │   ├── UserManagementServiceTest.php
│   │   ├── DashboardServiceTest.php
│   │   ├── NotificationServiceTest.php
│   │   ├── SettlementServiceTest.php
│   │   ├── CurrencyConversionServiceTest.php
│   │   ├── CommissionPaymentValidationServiceTest.php
│   │   └── GigAuditCommandServiceTest.php
│   ├── Models/
│   │   ├── GigTest.php
│   │   ├── ArtistTest.php
│   │   ├── BookerTest.php
│   │   └── PaymentTest.php
│   └── Helpers/
└── TestCase.php       # Classe base para testes
```

---

## 📊 Requisitos de Cobertura

### Métricas de Cobertura

| Métrica | Mínimo Obrigatório | Meta Recomendada |
|---------|-------------------|------------------|
| **Cobertura Geral** | 80% | 90% |
| **Services Críticos** | 95% | 98% |
| **Routes/Controllers** | 75% | 85% |
| **Models** | 85% | 90% |

### Services Críticos (95%+)
- `AuditService`
- `ExchangeRateService`
- `GigFinancialCalculatorService`
- `CommissionPaymentValidationService`
- `FinancialReportService`

---

## 🚀 Comandos de Teste (Via Sail)

**REGRAS CRÍTICAS:**
- **SEMPRE** use `vendor/bin/sail artisan test`
- **NUNCA** execute testes direto (fora do container)
- **NÃO** use `--verbose` (saída mais limpa)

### Comandos Principais

```bash
# Executar todos os testes
vendor/bin/sail artisan test

# Executar com cobertura
vendor/bin/sail artisan test --coverage

# Executar arquivo específico
vendor/bin/sail artisan test tests/Unit/Services/AuditServiceTest.php

# Executar teste específico (por nome)
vendor/bin/sail artisan test --filter=testCalculateGigAuditData

# Executar apenas testes unitários
vendor/bin/sail artisan test tests/Unit

# Executar apenas testes de feature
vendor/bin/sail artisan test tests/Feature

# Executar com grupo específico
vendor/bin/sail artisan test --group=services
```

### Executar Antes de Commits

```bash
# 1. Format codigo (obrigatorio)
vendor/bin/sail bin pint --dirty

# 2. Rodar testes especificos da feature
vendor/bin/sail artisan test --filter=testNomeDaFeature

# 3. Se tudo passou, commit
```

---

## 🏭 Factories e Seeders Disponíveis

### Factories Principais

| Factory | Model | Descrição |
|---------|-------|-----------|
| `ArtistFactory` | `Artist` | Cria artistas com dados válidos |
| `BookerFactory` | `Booker` | Cria bookers com configuração de comissão |
| `GigFactory` | `Gig` | Cria gigs completas com artista opcional |
| `PaymentFactory` | `Payment` | Cria pagamentos de clientes |
| `GigCostFactory` | `GigCost` | Cria custos de gigs |
| `SettlementFactory` | `Settlement` | Cria acertos financeiros |
| `CostCenterFactory` | `CostCenter` | Cria centros de custo |
| `UserFactory` | `User` | Cria usuários com permissões |
| `TagFactory` | `Tag` | Cria tags para categorização |
| `ServiceTakerFactory` | `ServiceTaker` | Cria tomadores de serviço |
| `DebitNoteFactory` | `DebitNote` | Cria notas de débito |

### Exemplo de Uso de Factory

```php
use App\Models\Gig;

// Criar gig simples
$gig = Gig::factory()->create();

// Criar gig com relacionamentos
$gig = Gig::factory()
    ->for(Artist::factory())
    ->has(Payment::factory()->count(3))
    ->has(GigCost::factory()->count(2))
    ->create();

// Criar gig com estados customizados
$gig = Gig::factory()
    ->withCurrency('USD')
    ->withDate('2024-12-31')
    ->create();
```

### Seeders para Dados Base

| Seeder | Descrição |
|--------|-----------|
| `RolesAndPermissionsSeeder` | Cria roles e permissões do sistema |
| `UserSeeder` | Cria usuário admin inicial |
| `ArtistSeeder` | Popula artistas base |
| `BookerSeeder` | Popula bookers base |
| `CostCenterSeeder` | Popula centros de custo base |

---

## 🎯 Padrão TDD (Test-Driven Development)

### Ciclo RED-GREEN-REFACTOR

```
1. RED   → Criar teste que falha
2. GREEN → Implementar código minimo para passar
3. REFACTOR → Melhorar sem quebrar testes
```

### Exemplo Prático

#### Step 1: RED (Cria teste que falha)

```php
// tests/Unit/Services/AuditServiceTest.php

public function test_calculate_gig_audit_data_with_no_payments()
{
    // Arrange
    $gig = Gig::factory()->create([
        'contract_value' => 10000.00,
        'currency' => 'BRL',
    ]);

    $service = new AuditService();

    // Act
    $result = $service->calculateGigAuditData($gig);

    // Assert
    $this->assertEquals(10000.00, $result['contract_value_brl']);
    $this->assertEquals(0.00, $result['total_paid_brl']);
    $this->assertTrue($result['has_divergence']);
}

// ❌ Falha porque o método não existe ou não implementa
```

#### Step 2: GREEN (Implementa minimo)

```php
// app/Services/AuditService.php

public function calculateGigAuditData(Gig $gig): array
{
    return [
        'contract_value_brl' => $gig->contract_value,
        'total_paid_brl' => 0.00,
        'has_divergence' => true,
    ];
}

// ✅ Agora o teste passa
```

#### Step 3: REFACTOR (Melhora implementacao)

```php
// app/Services/AuditService.php

public function calculateGigAuditData(Gig $gig): array
{
    $contractValueBrl = $this->calculator->calculateContractValueBrl($gig);
    $totalPaidBrl = $this->getTotalPaidBrl($gig);
    $divergenceAmount = $contractValueBrl - $totalPaidBrl;

    return [
        'gig_id' => $gig->id,
        'contract_value_brl' => $contractValueBrl,
        'total_paid_brl' => $totalPaidBrl,
        'total_pending_brl' => max(0, $divergenceAmount),
        'divergence_amount_brl' => abs($divergenceAmount),
        'divergence_percentage' => $contractValueBrl > 0
            ? (abs($divergenceAmount) / $contractValueBrl) * 100
            : 0,
        'has_divergence' => abs($divergenceAmount) > 0.01,
        'divergence_classification' => $this->classifyDivergence(abs($divergenceAmount), $contractValueBrl),
        'payment_status' => $this->getPaymentStatus($gig),
        'overdue_payments' => $gig->payments()->overdue()->count(),
        'upcoming_payments' => $gig->payments()->upcoming()->count(),
        'currency_inconsistencies' => $this->checkCurrencyInconsistencies($gig),
        'observations' => $this->generateObservations($gig),
    ];
}

// ✅ Ainda passa, mas agora é robusto e completo
```

---

## 📝 Padrão Arrange-Act-Assert

### Estrutura Padrão

```php
public function test_nome_descritivo_do_cenario(): void
{
    // ==================== ARRANGE ====================
    // Criar dados necessarios
    $gig = Gig::factory()
        ->for(Artist::factory())
        ->has(Payment::factory()->count(2)->confirmed())
        ->create();

    // Configurar servicos/mocks
    $service = new AuditService();

    // ==================== ACT ====================
    // Executar acao a ser testada
    $result = $service->calculateGigAuditData($gig);

    // ==================== ASSERT ====================
    // Verificar resultados
    $this->assertIsArray($result);
    $this->assertArrayHasKey('contract_value_brl', $result);
    $this->assertEquals(2, $result['total_payments_count']);
    $this->assertTrue($result['has_divergence'] ?? false);
}
```

### Exemplo: Teste de Service

```php
// tests/Unit/Services/ArtistFinancialsServiceTest.php

public function test_get_financial_metrics_returns_correct_calculations(): void
{
    // Arrange
    $artist = Artist::factory()->name('Test Artist')->create();

    // Criar gigs com pagamentos confirmados
    $gig1 = Gig::factory()
        ->for($artist)
        ->has(Payment::factory()->confirmed()->amount(5000))
        ->create();

    $gig2 = Gig::factory()
        ->for($artist)
        ->has(Payment::factory()->confirmed()->amount(3000))
        ->create();

    // Criar gig com pagamento pendente
    $gig3 = Gig::factory()
        ->for($artist)
        ->has(Payment::factory()->pending()->amount(2000))
        ->create();

    $service = new ArtistFinancialsService();

    // Act
    $metrics = $service->getFinancialMetrics($artist);

    // Assert
    $this->assertEquals(3, $metrics['total_gigs']);
    $this->assertEquals(8000.00, $metrics['cache_received_brl']); // 5000 + 3000
    $this->assertEquals(2000.00, $metrics['cache_pending_brl']); // 2000
}
```

---

## 🎭 Mocks e Stubs

### Quando Usar Mocks
- Serviços externos (APIs, email)
- Operações lentas (I/O)
- Dependências não isoladas

### Exemplo: Mock de ExchangeRateService

```php
use App\Services\ExchangeRateService;

public function test_calculate_contract_value_brl_with_usd_conversion(): void
{
    // Arrange
    $gig = Gig::factory()->create([
        'contract_value' => 1000.00,
        'currency' => 'USD',
    ]);

    // Mock do ExchangeRateService
    $exchangeService = $this->mock(ExchangeRateService::class, function ($mock) {
        $mock->shouldReceive('getRate')
            ->with('USD', 'BRL')
            ->andReturn(5.0); // Taxa fixa para teste
    });

    $calculator = new GigFinancialCalculatorService($exchangeService);

    // Act
    $result = $calculator->calculateContractValueBrl($gig);

    // Assert
    $this->assertEquals(5000.00, $result); // 1000 * 5.0
}
```

### Exemplo: Fake de Notification

```php
use Illuminate\Support\Facades\Notification;

public function test_create_user_sends_welcome_email(): void
{
    // Arrange
    Notification::fake();
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
    ];

    // Act
    $service = new UserManagementService();
    $user = $service->createUser($userData);

    // Assert - verificar se notificacao foi enviada
    Notification::assertSentTo($user, WelcomeEmail::class);
}
```

---

## 🔍 Testes de Feature (Controllers/Rotas)

### Padrão: Teste de CRUD

```php
// tests/Feature/Gigs/GigCrudTest.php

public function test_authenticated_user_can_create_gig(): void
{
    // Arrange
    $user = User::factory()->withPermission('create gig')->create();
    $artist = Artist::factory()->create();

    // Act
    $response = $this->actingAs($user)
        ->post(route('gigs.store'), [
            'artist_id' => $artist->id,
            'event_date' => '2024-12-31',
            'contract_value' => 10000.00,
            'currency' => 'BRL',
            'description' => 'Test gig',
        ]);

    // Assert
    $response->assertRedirect(route('gigs.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('gigs', [
        'artist_id' => $artist->id,
        'contract_value' => 10000.00,
    ]);
}

public function test_unauthorized_user_cannot_create_gig(): void
{
    // Arrange
    $user = User::factory()->create(); // Sem permissao

    // Act
    $response = $this->actingAs($user)
        ->post(route('gigs.store'), [
            'artist_id' => Artist::factory()->create()->id,
            'event_date' => '2024-12-31',
            'contract_value' => 10000.00,
            'currency' => 'BRL',
        ]);

    // Assert - 403 Forbidden
    $response->assertForbidden();
}

public function test_guest_cannot_access_gigs(): void
{
    $response = $this->get(route('gigs.index'));
    $response->assertRedirect(route('login'));
}
```

---

## 📱 Testes de Feature (Livewire/Filament)

Se o projeto usar Livewire com Filament:

```php
// tests/Feature/Filament/ArtistResourceTest.php

public function test_can_create_artist_via_filament(): void
{
    // Arrange
    $user = User::factory()->admin()->create();

    // Act - simular acao no Filament resource
    Livewire::actingAs($user)
        ->test(ArtistResource\Pages\CreateArtist::class)
        ->set('data.name', 'Test Artist')
        ->set('data.contact_info', 'test@example.com')
        ->call('create');

    // Assert
    $this->assertDatabaseHas('artists', [
        'name' => 'Test Artist',
        'contact_info' => 'test@example.com',
    ]);
}
```

---

## 🔒 Testes de Permissão (Policies)

### Padrão de Teste de Policy

```php
// tests/Unit/Policies/GigPolicyTest.php

use App\Policies\GigPolicy;
use App\Models\Gig;
use App\Models\User;

public function test_user_with_permission_can_view_gig(): void
{
    // Arrange
    $user = User::factory()->withPermission('view any gig')->create();
    $gig = Gig::factory()->create();
    $policy = new GigPolicy();

    // Act & Assert
    $this->assertTrue($policy->view($user, $gig));
}

public function test_user_without_permission_cannot_view_gig(): void
{
    // Arrange
    $user = User::factory()->create();
    $gig = Gig::factory()->create();
    $policy = new GigPolicy();

    // Act & Assert
    $this->assertFalse($policy->view($user, $gig));
}

public function test_owner_can_view_own_gig(): void
{
    // Arrange
    $user = User::factory()->withPermission('view own gig')->create();
    $gig = Gig::factory()->for($user)->create();
    $policy = new GigPolicy();

    // Act & Assert
    $this->assertTrue($policy->view($user, $gig));
}
```

---

## 🎬 Testes de Observers

### Padrão de Teste de Observer

```php
// tests/Unit/Observers/GigObserverTest.php

use App\Models\Gig;
use Illuminate\Support\Facades\Event;

public function test_gig_observer_deletes_related_data_on_soft_delete(): void
{
    // Arrange
    $gig = Gig::factory()
        ->has(Payment::factory()->count(3))
        ->has(GigCost::factory()->count(2))
        ->create();

    $paymentId = $gig->payments->first()->id;
    $costId = $gig->costs->first()->id;

    // Act
    $gig->delete(); // Soft delete

    // Assert
    $this->assertSoftDeleted('gigs', ['id' => $gig->id]);
    $this->assertSoftDeleted('payments', ['id' => $paymentId]);
    $this->assertSoftDeleted('gig_costs', ['id' => $costId]);
}

public function test_gig_observer_recaptures_on_cost_update(): void
{
    // Arrange
    $gig = Gig::factory()->create([
        'contract_value' => 10000.00,
    ]);
    $cost = GigCost::factory()->for($gig)->create([
        'amount' => 1000.00,
    ]);

    // Act
    $cost->update(['amount' => 1500.00]);

    // Assert - verificar se recalculo foi feito
    $gig->refresh();
    // Assertions sobre recalculo...
}
```

---

## 🚦 Boas Práticas

### 1. Testes Independentes
- Cada teste deve funcionar sozinho
- Use `RefreshDatabase` trait para banco limpo
- Não depende da ordem de execucao

### 2. Testes Rápidos
- Evite `sleep()` e operacoes lentas
- Use mocks para APIs externas
- Testes unitarios devem rodar em segundos

### 3. Testes Legíveis
- Nomes descritivos: `test_calculate_net_value_with_costs`
- Comentarios no que não for obvio
- AAA (Arrange-Act-Assert) bem claro

### 4. Cobertura Significativa
- Nao teste apenas para cobertura
- Teste caminhos de erro (401, 403, 404, 422, 500)
- Teste casos extremos (boundary cases)

---

## ⚠️ Arquivos Excluídos da Cobertura

```xml
<!-- phpunit.xml -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <!-- Não testar estes arquivos -->
        <directory>./app/Console/</directory>
        <directory>./app/Exceptions/</directory>
        <directory>./app/Http/Middleware/</directory>
        <file>./app/Providers/RouteServiceProvider.php</file>
    </exclude>
</coverage>
```

---

## 📈 Relatórios de Cobertura

```bash
# Gerar relatório HTML
vendor/bin/sail artisan test --coverage --coverage-html=coverage-report

# Abrir no navegador
open coverage-report/index.html

# Gerar relatório com limite mínimo (falha se < 80%)
vendor/bin/sail artisan test --coverage --min=80
```

---

## 🎯 Checklist de TDD

- [ ] **RED**: Teste criado e falhando
- [ ] **GREEN**: Implementação mínima passando
- [ ] **REFACTOR**: Código limpo sem quebrar teste
- [ ] **COVERAGE**: Verificar se 80%+ alcançado
- [ ] **PINT**: `vendor/bin/sail bin pint --dirty`
- [ ] **RERUN**: Executar testes novamente

---

**Versão**: 1.0
**Cobertura Mínima Obrigatória**: 80%
**Cobertura Target**: 90%
**Services Críticos**: 95%+
**Próxima Referência**: Voltar para `.aidev/context/project-summary.md`