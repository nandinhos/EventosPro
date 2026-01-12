# EventosPro Agent System

Sistema de agentes especializados para desenvolvimento de alta qualidade no EventosPro, integrando Laravel Boost MCP e seguindo as melhores práticas do projeto.

## 📋 Visão Geral

O sistema de agentes do EventosPro é composto por **agentes especializados** que trabalham em conjunto para entregar código de produção com alta qualidade, cobertura de testes adequada e documentação completa.

### Objetivos

- ✅ **Qualidade**: Código seguindo PSR-12, PHPStan level 5+, 80%+ coverage
- ✅ **Consistência**: Padrões arquiteturais uniformes em todo o projeto
- ✅ **Produtividade**: Automação de tarefas repetitivas e validações
- ✅ **Documentação**: Manter docs sincronizados com código
- ✅ **Segurança**: Validações de segurança em todas as etapas

## 🤖 Agentes Disponíveis

### 1. Service Development Agent (`/service-dev`)

**Especialidade**: Criar services seguindo padrões EventosPro

**Quando usar**:
- Criar novo service para lógica de negócio
- Implementar cálculos complexos
- Agregar dados de múltiplos models
- Integrar com APIs externas

**O que faz**:
- Cria service class com injeção de dependências
- Implementa PHPDoc completo
- Cria testes unitários (target: 95% coverage)
- Valida uso correto de services existentes
- Previne N+1 queries

**Exemplo de uso**:
```bash
# Para Claude Code
/service-dev

# Prompt
"Crie um service para calcular as métricas de performance mensal de artistas"
```

**Entregáveis**:
- `app/Services/{ServiceName}Service.php`
- `tests/Unit/Services/{ServiceName}ServiceTest.php`
- Documentação em `docs/SERVICES_API.md`

---

### 2. Test Automation Agent (`/test-automation`)

**Especialidade**: Gerar testes abrangentes com alta cobertura

**Quando usar**:
- Criar testes unitários para services
- Criar testes feature para controllers
- Aumentar cobertura de testes
- Validar edge cases

**O que faz**:
- Gera testes unitários e feature
- Garante tratamento correto de decimais (strings!)
- Implementa mocks de services externos
- Previne N+1 queries em testes
- Valida cobertura mínima (80% geral, 95% services)

**Pontos críticos**:
```php
// ⚠️ CRÍTICO: Decimals retornam strings no Laravel
$this->assertIsString($payment->due_value);
$this->assertEquals('500.00', $payment->due_value);

// ⚠️ SEMPRE use Sail para rodar testes
./vendor/bin/sail artisan test
```

**Entregáveis**:
- Testes com coverage >= 80% (95% para services)
- Testes de edge cases
- Validação de N+1 queries

---

### 3. Financial Logic Agent (`/financial-logic`)

**Especialidade**: Implementar cálculos financeiros precisos

**Quando usar**:
- Implementar cálculos de cachê/comissões
- Conversão de moedas
- Validações de pagamento
- Projeções financeiras
- Relatórios financeiros

**O que faz**:
- Usa `GigFinancialCalculatorService` como base
- Garante conversão para BRL
- Implementa regras de validação de pagamento
- Valida precisão decimal (2 casas)
- Integra com `ExchangeRateService`

**Services financeiros principais**:
- `GigFinancialCalculatorService`: Cálculos de gig
- `ExchangeRateService`: Conversão de moedas
- `CommissionPaymentValidationService`: Validação de pagamentos
- `FinancialProjectionService`: Projeções de fluxo de caixa
- `AuditService`: Auditoria financeira

**Regras críticas**:
```php
// ✅ SEMPRE use services para cálculos
$calculator = app(GigFinancialCalculatorService::class);
$grossFee = $calculator->calculateGrossFeeInBrl($gig);

// ❌ NUNCA calcule diretamente
$grossFee = $gig->cache_value * 5.0; // Taxa hard-coded!
```

---

### 4. Quality Assurance Agent (`/quality-check`)

**Especialidade**: Validações de qualidade e performance

**Quando usar**:
- Antes de commit
- Antes de pull request
- Após mudanças significativas
- Verificação de performance

**O que faz**:
- Executa Laravel Pint (formatação PSR-12)
- Roda PHPStan (análise estática)
- Valida cobertura de testes
- Detecta N+1 queries
- Verifica segurança (CSRF, mass assignment, SQL injection)

**Checklist completo**:
```bash
# 1. Formatação
./vendor/bin/sail bash -c "vendor/bin/pint"

# 2. Análise estática
./vendor/bin/sail bash -c "vendor/bin/phpstan analyse"

# 3. Testes e cobertura
./vendor/bin/sail artisan test --coverage --min=80

# 4. Services críticos (95%)
./vendor/bin/sail artisan test tests/Unit/Services/ --coverage --min=95
```

**Gates de qualidade**:
- PSR-12 compliance (Pint)
- PHPStan level 5+ (sem erros)
- 80%+ coverage geral
- 95%+ coverage services críticos
- 0 N+1 queries
- Todas validações de segurança

---

### 5. Documentation Agent (`/doc-update`)

**Especialidade**: Manter documentação sincronizada

**Quando usar**:
- Após criar/modificar service
- Após adicionar business rule
- Após criar/modificar model
- Após mudanças em APIs públicas

**O que faz**:
- Atualiza `docs/SERVICES_API.md`
- Atualiza `CLAUDE.md` (se crítico)
- Atualiza `docs/ai_context/model-relationships.md`
- Gera PHPDoc blocks completos
- Cria devlog entries

**Arquivos de documentação**:
```
docs/
├── SERVICES_API.md          # API completa de todos os services
├── TESTING.md               # Padrões de teste
├── ai_context/
│   ├── 1_context.md         # Contexto de negócio
│   ├── 2_architecture.md    # Arquitetura do sistema
│   ├── 4_rules.md           # Regras de negócio
│   └── model-relationships.md # Relacionamentos de models
└── devlog/                  # Logs de desenvolvimento
    └── YYYY-MM-DD-feature.md
```

**Template PHPDoc**:
```php
/**
 * {Brief description}
 *
 * {Detailed description}
 *
 * @param Type $param Description
 * @return ReturnType Description
 * @throws ExceptionType When this happens
 *
 * @example
 * ```php
 * $service = app(ServiceName::class);
 * $result = $service->method($param);
 * ```
 */
```

---

### 6. Migration & Model Agent (`/model-migration`)

**Especialidade**: Criar migrations, models e factories

**Quando usar**:
- Criar nova entidade
- Modificar schema existente
- Adicionar relacionamentos
- Criar factories para testes

**O que faz**:
- Cria migrations com tipos corretos
- Cria models com relationships
- Implementa casts apropriados (decimal → string!)
- Cria factories com estados
- Configura observers (se necessário)

**Padrões críticos**:
```php
// ✅ CORRETO: Decimal para dinheiro
$table->decimal('amount', 10, 2);

// ❌ ERRADO: Float/Double para dinheiro
$table->float('amount'); // Problemas de precisão!

// ✅ Model cast
protected function casts(): array
{
    return [
        'amount' => 'decimal:2', // Retorna string!
    ];
}
```

**Entregáveis**:
- Migration file
- Model com relationships e casts
- Factory com estados
- Observer (se aplicável)
- Testes de model

---

### 7. Development Orchestrator (`/dev-orchestrator`)

**Especialidade**: Coordenar desenvolvimento completo de features

**Quando usar**:
- Desenvolver feature completa
- Implementar nova funcionalidade
- Refatorar módulo inteiro

**O que faz**:
- Coordena todos os agentes especializados
- Segue workflow de desenvolvimento completo
- Garante todos os quality gates
- Valida integração entre componentes

**Workflow completo**:
```
1. Análise & Planejamento
   ↓
2. Database Layer (/model-migration)
   ↓
3. Service Layer (/service-dev + /financial-logic)
   ↓
4. Controller & Routes
   ↓
5. Views (se aplicável)
   ↓
6. Testing Suite (/test-automation)
   ↓
7. Quality Assurance (/quality-check)
   ↓
8. Documentation (/doc-update)
   ↓
9. Integration Testing
   ↓
10. Final Review & Delivery
```

**Quality Gates**:
- Gate 1 (Pós-Service): Tests 95%+, PHPStan OK
- Gate 2 (Pós-Testing): Coverage 80%+, All tests pass
- Gate 3 (Pós-QA): Pint OK, No N+1, Security OK
- Gate 4 (Final): All criteria met, Docs complete

---

## 🔄 Workflows Comuns

### Workflow 1: Nova Feature Financeira

```markdown
**Requisito**: Adicionar cálculo de impostos em gigs

**Agentes utilizados**:
1. `/model-migration` → Adicionar campos de imposto
2. `/financial-logic` → Criar TaxCalculationService
3. `/service-dev` → Integrar com GigFinancialCalculatorService
4. `/test-automation` → Testes completos (95%+)
5. `/quality-check` → Validação completa
6. `/doc-update` → Documentar cálculos

**Resultado**: Feature completa, testada, documentada
```

### Workflow 2: Novo Service

```markdown
**Requisito**: Service para métricas de performance de artistas

**Agentes utilizados**:
1. `/service-dev` → Criar ArtistPerformanceService
2. `/test-automation` → Unit tests (95%+ coverage)
3. `/quality-check` → Pint + PHPStan
4. `/doc-update` → Adicionar a SERVICES_API.md

**Resultado**: Service pronto para uso
```

### Workflow 3: Correção de Bug

```markdown
**Requisito**: Corrigir cálculo de comissão

**Agentes utilizados**:
1. `/test-automation` → Criar teste que reproduz bug
2. Corrigir código
3. `/quality-check` → Validar sem regressões
4. `/doc-update` → Atualizar docs se comportamento mudou

**Resultado**: Bug corrigido com teste de regressão
```

## 🎯 Decisão: Qual Agente Usar?

### Precisa criar lógica de negócio?
- Cálculo financeiro → `/financial-logic`
- Agregação de dados → `/service-dev`
- Regra de negócio complexa → `/service-dev`

### Precisa criar entidade?
- Nova tabela → `/model-migration`
- Modificar schema → `/model-migration`

### Precisa aumentar qualidade?
- Cobertura baixa → `/test-automation`
- Código desformatado → `/quality-check`
- Performance ruim → `/quality-check` (detectar N+1)

### Precisa documentar?
- Novo service → `/doc-update`
- Nova regra → `/doc-update`
- API pública mudou → `/doc-update`

### Feature completa?
- Nova funcionalidade end-to-end → `/dev-orchestrator`

## 📊 Métricas de Qualidade

### Requisitos Mínimos

| Métrica | Target | Crítico |
|---------|--------|---------|
| Test Coverage (Overall) | 80% | N/A |
| Test Coverage (Services) | 95% | ✅ |
| PHPStan Level | 5 | ✅ |
| Pint Compliance | 100% | ✅ |
| N+1 Queries | 0 | ✅ |
| Security Issues | 0 | ✅ |

### Services Críticos (95%+ Coverage)

- AuditService
- GigFinancialCalculatorService
- ExchangeRateService
- ArtistFinancialsService
- BookerFinancialsService
- FinancialReportService
- CommissionPaymentValidationService

## 🚨 Regras Críticas (SEMPRE seguir)

### 1. Laravel Sail Obrigatório
```bash
# ✅ CORRETO
./vendor/bin/sail artisan test
./vendor/bin/sail bash -c "vendor/bin/pint"

# ❌ ERRADO
php artisan test
vendor/bin/pint
```

### 2. Decimais São Strings
```php
// ✅ CORRETO
$this->assertIsString($payment->due_value);
$this->assertEquals('500.00', $payment->due_value);

// ❌ ERRADO
$this->assertIsFloat($payment->due_value);
```

### 3. SEMPRE Use Services para Cálculos
```php
// ✅ CORRETO
$calculator = app(GigFinancialCalculatorService::class);
$total = $calculator->calculateGrossFeeInBrl($gig);

// ❌ ERRADO
$total = $gig->cache_value * 5.0;
```

### 4. Eager Loading Obrigatório
```php
// ✅ CORRETO
$gigs = Gig::with(['artist', 'booker', 'payments'])->get();

// ❌ ERRADO
$gigs = Gig::all(); // N+1!
```

### 5. Configuração Hierárquica
```php
// ✅ CORRETO
config('exchange_rates.default_rates.USD')

// ❌ ERRADO
config('app.exchange_rate_usd')
env('EXCHANGE_RATE_USD') // Fora de config files!
```

## 🔧 Comandos Rápidos

### Setup Inicial
```bash
# Iniciar ambiente
./vendor/bin/sail up -d

# Instalar dependências
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

### Desenvolvimento
```bash
# Criar service
./vendor/bin/sail artisan make:class Services/NewService

# Criar teste
./vendor/bin/sail artisan make:test Unit/Services/NewServiceTest --unit

# Rodar testes
./vendor/bin/sail artisan test
```

### Qualidade
```bash
# Formatação
./vendor/bin/sail bash -c "vendor/bin/pint"

# Análise estática
./vendor/bin/sail bash -c "vendor/bin/phpstan analyse"

# Cobertura
./vendor/bin/sail artisan test --coverage --min=80
```

### Banco de Dados
```bash
# Migrations
./vendor/bin/sail artisan migrate

# Fresh + seed
./vendor/bin/sail artisan migrate:fresh --seed

# Tinker
./vendor/bin/sail artisan tinker
```

## 📚 Recursos Adicionais

### Documentação Principal
- **CLAUDE.md**: Overview completo do projeto
- **AGENTS.md**: Quick reference para desenvolvimento
- **docs/SERVICES_API.md**: API completa dos services
- **docs/TESTING.md**: Padrões e exemplos de testes

### Integração com Laravel Boost

O sistema está integrado com **Laravel Boost MCP**, oferecendo:
- `search-docs`: Busca em documentação oficial (Laravel, Filament, etc.)
- `list-artisan-commands`: Lista comandos disponíveis
- `tinker`: Executa código PHP interativo
- `database-query`: Consultas diretas ao banco

**Exemplo de uso**:
```
"Preciso criar um Filament resource para Gigs. Use search-docs para encontrar a sintaxe correta do Filament 4"
```

## 🎓 Boas Práticas

### DO (Faça)
- ✅ Use agentes especializados para suas áreas
- ✅ Siga o workflow completo do orchestrator
- ✅ Rode quality checks antes de commit
- ✅ Mantenha documentação sincronizada
- ✅ Escreva testes antes ou durante desenvolvimento
- ✅ Use factories em testes
- ✅ Eager load relationships

### DON'T (Não Faça)
- ❌ Pular testes ("vou fazer depois")
- ❌ Commitar código sem rodar Pint
- ❌ Usar floats para valores monetários
- ❌ Calcular finanças fora de services
- ❌ Executar comandos fora do Sail
- ❌ Criar N+1 queries
- ❌ Ignorar warnings de coverage

## 🚀 Próximos Passos

1. **Familiarize-se** com os agentes disponíveis
2. **Experimente** workflows simples primeiro
3. **Use** `/dev-orchestrator` para features completas
4. **Mantenha** métricas de qualidade
5. **Documente** padrões que surgem no projeto

---

**Criado**: 2025-10-21
**Versão**: 1.0
**Manutenção**: Atualizar quando novos agentes forem adicionados
