# Testing Best Practices - EventosPro

Este documento define os padrões e melhores práticas para escrever testes no projeto EventosPro.

---

## ✅ Padrões Aceitos para Declaração de Métodos de Teste

### 1. PHP 8 Attributes (RECOMENDADO) ⭐

Este é o padrão **PREFERIDO** e **RECOMENDADO** para todos os novos testes.

```php
<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleServiceTest extends TestCase
{
    #[Test]
    public function it_validates_user_input()
    {
        // Arrange
        $input = ['email' => 'invalid-email'];

        // Act
        $result = $this->service->validate($input);

        // Assert
        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function it_processes_valid_data_correctly()
    {
        // test code
    }
}
```

**Vantagens**:
- ✅ Padrão moderno do PHP 8+
- ✅ Type-safe e verificável por IDEs
- ✅ Totalmente compatível com PHPUnit 11 e 12
- ✅ Sem dependência de doc-comments
- ✅ Suportado nativamente pelo Laravel

### 2. Nomeação com Prefixo `test_` (Alternativa Válida)

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserControllerTest extends TestCase
{
    public function test_it_creates_a_new_user()
    {
        $response = $this->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(201);
    }

    public function test_it_validates_required_fields()
    {
        // test code
    }
}
```

**Vantagens**:
- ✅ Padrão clássico do PHPUnit
- ✅ Funcionará em todas as versões futuras
- ✅ Não requer imports adicionais
- ✅ Fácil de identificar visualmente

---

## ❌ Padrões NÃO Aceitos (Deprecated)

### Doc-Comment Annotations - **NUNCA USAR**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class BadExampleTest extends TestCase
{
    /** @test */  // ❌ DEPRECATED - Será removido no PHPUnit 12
    public function it_does_something()
    {
        // test code
    }
}
```

**Por que NÃO usar**:
- ❌ Deprecated desde PHPUnit 10
- ❌ Será **removido completamente** no PHPUnit 12
- ❌ Gera warnings no PHPUnit 11
- ❌ Baseado em parsing de strings (não type-safe)
- ❌ Difícil para ferramentas de análise estática

**Warning gerado**:
```
WARN  Metadata found in doc-comment for method Tests\Feature\BadExampleTest::it_does_something().
Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12.
Update your test code to use attributes instead.
```

---

## 📋 Checklist para Novos Testes

Antes de criar um novo teste ou modificar um existente, verifique:

- [ ] **Importar o attribute Test**: `use PHPUnit\Framework\Attributes\Test;`
- [ ] **Usar `#[Test]` attribute** ou prefixo `test_`
- [ ] **NUNCA usar `/** @test */`** doc-comments
- [ ] Seguir o padrão AAA (Arrange, Act, Assert)
- [ ] Usar factories para criação de modelos
- [ ] Nomear testes de forma descritiva (`it_does_something_when_condition`)
- [ ] Executar `./vendor/bin/sail artisan test` antes de commit
- [ ] Verificar ausência de warnings PHPUnit
- [ ] Usar `RefreshDatabase` trait quando necessário

---

## 🎯 Padrões de Nomenclatura

### Para Testes Unitários

**Padrão**: `it_[does_what]_[when_condition]`

```php
#[Test]
public function it_calculates_total_when_all_items_are_valid()

#[Test]
public function it_throws_exception_when_input_is_invalid()

#[Test]
public function it_returns_empty_collection_when_no_records_exist()
```

### Para Testes de Feature

**Padrão**: `it_[action]_[expected_result]`

```php
#[Test]
public function it_creates_user_with_valid_data()

#[Test]
public function it_validates_required_fields_on_registration()

#[Test]
public function it_redirects_unauthenticated_users_to_login()
```

---

## 🔧 Estrutura de Teste (Pattern AAA)

Sempre organize testes seguindo o padrão **Arrange-Act-Assert**:

```php
#[Test]
public function it_calculates_commission_correctly()
{
    // Arrange: Prepara os dados necessários
    $gig = Gig::factory()->create([
        'cache_value' => 10000,
        'agency_commission_rate' => 20,
    ]);

    // Act: Executa a ação sendo testada
    $commission = $this->calculator->calculateCommission($gig);

    // Assert: Verifica o resultado esperado
    $this->assertEquals(2000, $commission);
}
```

---

## 📊 Cobertura de Testes

### Requisitos Mínimos

- **Geral**: 80% de cobertura de código
- **Serviços críticos**: 95% de cobertura
  - `GigFinancialCalculatorService`
  - `AuditService`
  - `ExchangeRateService`
  - `CommissionPaymentValidationService`

### Executar Cobertura

```bash
# Relatório de cobertura no terminal
./vendor/bin/sail artisan test --coverage

# Relatório HTML detalhado
./vendor/bin/sail artisan test --coverage --coverage-html=coverage-report

# Verificar cobertura mínima
./vendor/bin/sail artisan test --coverage --min=80
```

---

## 🚀 Executando Testes

### Comandos Essenciais

```bash
# Todos os testes
./vendor/bin/sail artisan test

# Teste específico por filtro
./vendor/bin/sail artisan test --filter=it_calculates_commission

# Arquivo de teste específico
./vendor/bin/sail artisan test tests/Unit/Services/AuditServiceTest.php

# Com cobertura
./vendor/bin/sail artisan test --coverage

# Parar no primeiro erro
./vendor/bin/sail artisan test --stop-on-failure
```

### Antes de Commitar

**⚠️ IMPORTANTE**: EventosPro possui um **pre-commit hook** que valida automaticamente o código.

```bash
# 1. Formatar código
./vendor/bin/sail bash -c "vendor/bin/pint --dirty"

# 2. Executar testes
./vendor/bin/sail artisan test

# 3. Verificar warnings (não deve haver warnings PHPUnit)

# 4. Commit SEMPRE dentro do Sail
./vendor/bin/sail bash -c "git add . && git commit -m 'sua mensagem'"
```

#### Pre-Commit Hook Automático

O projeto possui um hook (`.git/hooks/pre-commit`) que executa automaticamente:
- ✅ Validação de formatação via Laravel Pint
- ✅ Execução de testes
- ✅ Verificação de sintaxe PHP

**Se tentar commitar fora do Sail**:
```
⚠️  ATENÇÃO: Commits devem ser feitos dentro do container Laravel Sail!
📋 Use: ./vendor/bin/sail bash -c "git add . && git commit"
```

**Benefícios**:
- Garante que código não formatado não seja commitado
- Previne commits com testes falhando
- Mantém qualidade consistente no repositório
- Executa validações no ambiente correto (container)

---

## 🏗️ Exemplos Completos

### Teste de Service (Unit)

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GigFinancialCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GigFinancialCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(GigFinancialCalculatorService::class);
    }

    #[Test]
    public function it_calculates_gross_cash_correctly()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'cache_value' => 10000,
            'currency' => 'BRL',
        ]);

        // Act
        $result = $this->calculator->calculateGrossCashBrl($gig);

        // Assert
        $this->assertEquals(10000, $result);
    }
}
```

### Teste de Controller (Feature)

```php
<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use App\Models\Gig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GigControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_displays_gig_details_for_authenticated_user()
    {
        // Arrange
        $gig = Gig::factory()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('gigs.show', $gig));

        // Assert
        $response->assertStatus(200);
        $response->assertSee($gig->location_event_details);
    }

    #[Test]
    public function it_redirects_unauthenticated_users_to_login()
    {
        // Arrange
        $gig = Gig::factory()->create();

        // Act
        $response = $this->get(route('gigs.show', $gig));

        // Assert
        $response->assertRedirect(route('login'));
    }
}
```

---

## 🔄 Migrando Testes Antigos

Se você encontrar testes usando o padrão deprecated `/** @test */`:

### Antes (Deprecated)

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class OldTest extends TestCase
{
    /** @test */
    public function it_does_something()
    {
        // test code
    }
}
```

### Depois (Moderno)

```php
<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OldTest extends TestCase
{
    #[Test]
    public function it_does_something()
    {
        // test code
    }
}
```

**Passos**:
1. Adicionar `use PHPUnit\Framework\Attributes\Test;` no topo
2. Substituir `/** @test */` por `#[Test]`
3. Executar `./vendor/bin/sail bash -c "vendor/bin/pint"`
4. Executar testes para validar

---

## 📚 Referências

- [PHPUnit 11 Documentation](https://docs.phpunit.de/en/11.0/)
- [Laravel Testing Documentation](https://laravel.com/docs/11.x/testing)
- [PHPUnit Attributes](https://docs.phpunit.de/en/11.0/attributes.html)
- [EventosPro Testing Guide](./TESTING.md)

---

## 🆘 FAQ

### Por que PHPUnit está gerando warnings sobre `@test`?

O PHPUnit 10+ deprecou annotations em doc-comments. Use `#[Test]` attributes (PHP 8+) ou prefixo `test_` nos métodos.

### Posso usar `test_` e `#[Test]` no mesmo arquivo?

Sim, mas não é recomendado. Escolha um padrão e siga consistentemente.

### Qual padrão devo usar em novos testes?

**Sempre use `#[Test]` attributes** - é o padrão moderno e recomendado.

### Como evitar esses warnings no futuro?

1. Sempre use `#[Test]` em novos testes
2. Configure seu editor para sugerir o template correto
3. Revise PRs para garantir conformidade
4. Execute testes localmente antes de commitar

---

**Última atualização**: 2025-10-26
**Versão**: 1.0
**Maintainer**: DevTeam EventosPro
