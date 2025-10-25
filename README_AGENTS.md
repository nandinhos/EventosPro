# 🤖 EventosPro Agent System - Quick Start

Sistema de agentes especializados para desenvolvimento de alta qualidade no EventosPro.

## 🚀 Início Rápido

### Agentes Disponíveis (Slash Commands)

| Comando | Uso | Quando Usar |
|---------|-----|-------------|
| `/service-dev` | Criar services | Lógica de negócio, cálculos, agregações |
| `/test-automation` | Gerar testes | Aumentar coverage, testar edge cases |
| `/financial-logic` | Lógica financeira | Cálculos de cachê, comissões, conversões |
| `/quality-check` | Validar qualidade | Antes de commit, antes de PR |
| `/doc-update` | Atualizar docs | Após mudanças em code/APIs |
| `/model-migration` | Criar models | Novas entidades, mudanças de schema |
| `/dev-orchestrator` | Feature completa | Desenvolver funcionalidade end-to-end |

### Exemplos Práticos

#### Criar um Novo Service
```
/service-dev

Prompt: "Crie um service para calcular métricas de performance mensal de artistas,
incluindo total de gigs, cachê médio e taxa de cancelamento"
```

#### Aumentar Cobertura de Testes
```
/test-automation

Prompt: "Crie testes para GigFinancialCalculatorService cobrindo casos de borda:
gigs sem pagamentos, múltiplas moedas, valores zerados"
```

#### Implementar Cálculo Financeiro
```
/financial-logic

Prompt: "Implementar cálculo de imposto de renda retido na fonte para cachês
de artistas, seguindo tabela progressiva da Receita Federal"
```

#### Validar Qualidade Antes de Commit
```
/quality-check

Prompt: "Executar checklist completo de qualidade: Pint, PHPStan, testes,
coverage, N+1 queries, security"
```

#### Criar Nova Entidade
```
/model-migration

Prompt: "Criar model Contract para contratos de artistas, com campos:
artist_id, start_date, end_date, terms (JSON), valor mensal (decimal),
relacionamento com Gigs"
```

#### Desenvolver Feature Completa
```
/dev-orchestrator

Prompt: "Implementar sistema de avaliação de eventos: model Rating com nota 1-5,
comentário, relacionamento com Gig e User, CRUD completo, dashboard agregado"
```

## 📋 Workflow Típico

### Feature Simples (Service)
```
1. /service-dev → Criar service
2. /test-automation → Criar testes (95%+)
3. /quality-check → Validar qualidade
4. /doc-update → Documentar
```

### Feature Completa
```
1. /dev-orchestrator → Coordena todo processo
   ├─ /model-migration (se precisar entidade)
   ├─ /service-dev (lógica)
   ├─ Controllers + Routes
   ├─ Views
   ├─ /test-automation (testes)
   ├─ /quality-check (QA)
   └─ /doc-update (docs)
```

### Correção de Bug
```
1. /test-automation → Criar teste que reproduz
2. Corrigir código
3. /quality-check → Validar sem regressões
4. /doc-update → Atualizar se necessário
```

## ⚡ Comandos Essenciais

### Desenvolvimento
```bash
# Iniciar ambiente
./vendor/bin/sail up -d

# Criar service
./vendor/bin/sail artisan make:class Services/NewService

# Rodar testes
./vendor/bin/sail artisan test

# Cobertura
./vendor/bin/sail artisan test --coverage --min=80
```

### Qualidade
```bash
# Formatação (Pint)
./vendor/bin/sail bash -c "vendor/bin/pint"

# Análise estática (PHPStan)
./vendor/bin/sail bash -c "vendor/bin/phpstan analyse"

# Checklist completo
./vendor/bin/sail bash -c "vendor/bin/pint" && \
./vendor/bin/sail bash -c "vendor/bin/phpstan analyse" && \
./vendor/bin/sail artisan test --coverage --min=80
```

## 🚨 Regras Críticas

### 1. SEMPRE Use Laravel Sail
```bash
# ✅ CORRETO
./vendor/bin/sail artisan test

# ❌ ERRADO
php artisan test
```

### 2. Decimais São Strings
```php
// ✅ CORRETO em testes
$this->assertIsString($payment->due_value);
$this->assertEquals('500.00', $payment->due_value);

// ❌ ERRADO
$this->assertIsFloat($payment->due_value);
```

### 3. Use Services para Cálculos
```php
// ✅ CORRETO
$calculator = app(GigFinancialCalculatorService::class);
$total = $calculator->calculateGrossFeeInBrl($gig);

// ❌ ERRADO
$total = $gig->cache_value * 5.0; // Hard-coded!
```

### 4. Eager Loading Obrigatório
```php
// ✅ CORRETO
$gigs = Gig::with(['artist', 'booker'])->get();

// ❌ ERRADO - N+1 queries!
$gigs = Gig::all();
```

## 📊 Métricas de Qualidade

| Métrica | Target Mínimo |
|---------|---------------|
| **Test Coverage (Geral)** | 80% |
| **Test Coverage (Services)** | 95% |
| **PHPStan** | Level 5, 0 erros |
| **Pint** | 100% compliance |
| **N+1 Queries** | 0 |
| **Security Issues** | 0 |

## 🎯 Quando Usar Cada Agente

### Service Development (`/service-dev`)
- ✅ Criar lógica de negócio complexa
- ✅ Agregar dados de múltiplos models
- ✅ Integração com APIs externas
- ❌ Lógica simples de controller

### Test Automation (`/test-automation`)
- ✅ Criar suite completa de testes
- ✅ Aumentar coverage abaixo do target
- ✅ Testar edge cases
- ❌ Teste único e trivial

### Financial Logic (`/financial-logic`)
- ✅ Cálculos de cachê/comissão
- ✅ Conversão de moedas
- ✅ Validação de pagamentos
- ❌ Lógica não-financeira

### Quality Check (`/quality-check`)
- ✅ Antes de cada commit
- ✅ Antes de pull request
- ✅ Após refactoring
- ❌ Durante desenvolvimento ativo

### Doc Update (`/doc-update`)
- ✅ Após criar/modificar service
- ✅ Após mudança de API
- ✅ Nova business rule
- ❌ Mudança interna trivial

### Model/Migration (`/model-migration`)
- ✅ Nova entidade do domínio
- ✅ Mudança de schema
- ✅ Novos relacionamentos
- ❌ Modificação de dados apenas

### Dev Orchestrator (`/dev-orchestrator`)
- ✅ Feature completa end-to-end
- ✅ Múltiplos componentes
- ✅ Necessita coordenação
- ❌ Mudança isolada simples

## 📚 Documentação Completa

Para documentação detalhada, consulte:

- **[docs/AGENT_SYSTEM.md](docs/AGENT_SYSTEM.md)**: Documentação completa do sistema de agentes
- **[CLAUDE.md](CLAUDE.md)**: Overview do projeto EventosPro
- **[docs/SERVICES_API.md](docs/SERVICES_API.md)**: API completa de todos os services
- **[docs/TESTING.md](docs/TESTING.md)**: Padrões e exemplos de testes

## 🔧 Troubleshooting

### Testes falhando com tipo incorreto
```
Problema: assertIsFloat falha em campo decimal
Solução: Usar assertIsString (decimals são strings no Laravel)
```

### Comando não encontrado
```
Problema: php artisan test não funciona
Solução: Usar ./vendor/bin/sail artisan test
```

### Coverage abaixo do mínimo
```
Problema: Coverage em 75%
Solução: /test-automation para aumentar
```

### N+1 queries detectadas
```
Problema: Muitas queries em loop
Solução: Adicionar eager loading with(['relations'])
```

### PHPStan erros de tipo
```
Problema: Type mismatch errors
Solução: Adicionar type hints e PHPDoc
```

## 💡 Dicas Pro

1. **Use `/dev-orchestrator`** para features novas - ele coordena tudo
2. **Rode `/quality-check`** antes de cada commit
3. **Sempre use factories** em testes (nunca criação manual)
4. **Eager load relationships** para prevenir N+1
5. **Mantenha docs atualizados** com `/doc-update`
6. **95% coverage** em services financeiros é obrigatório

## 🆘 Ajuda

Se precisar de ajuda:

1. Consulte [docs/AGENT_SYSTEM.md](docs/AGENT_SYSTEM.md) para detalhes
2. Verifique [CLAUDE.md](CLAUDE.md) para padrões do projeto
3. Revise código existente similar em `app/Services/`
4. Use Laravel Boost `search-docs` para documentação oficial

---

**Versão**: 1.0
**Última atualização**: 2025-10-21

Para começar, simplesmente use um dos comandos slash acima e descreva o que precisa! 🚀
