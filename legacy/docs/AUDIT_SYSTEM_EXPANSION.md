# Expansão do Sistema de Auditoria - EventosPro

## 📋 Visão Geral

Este documento detalha a implementação da expansão do sistema de auditoria de dados do EventosPro, criando novos comandos especializados para validação de integridade financeira e consistência de dados.

**Data de Início:** 2025-10-07
**Desenvolvedor:** Claude Code + Fernando (Nando DEV)
**Branch:** `feature/filament-v4-upgrade`

---

## 🎯 Objetivos da Implementação

### Problema Identificado
O sistema possuía apenas um comando genérico de auditoria (`gig:audit-data`) que validava:
- Integridade referencial básica
- Regras simples de status de pagamento
- Consistência de comissões
- Campos obrigatórios
- Lógica de datas

### Solução Proposta
Criar **6 novos comandos especializados** para auditorias específicas:

1. ✅ `gig:audit-settlements` - Auditoria de acertos financeiros
2. ✅ `gig:audit-payments` - Auditoria de parcelas de pagamento do cliente
3. ✅ `gig:audit-business-rules` - Auditoria de regras de negócio complexas
4. ⏳ `gig:audit-currency` - Auditoria de consistência de moedas
5. ⏳ `gig:audit-costs` - Auditoria de custos das gigs
6. ⏳ `gig:audit-duplicates` - Detecção de gigs duplicadas

---

## 🏗️ Arquitetura da Solução

### Estrutura de Arquivos

```
app/Console/Commands/
├── AuditSettlementsCommand.php      # ✅ Fase 1
├── AuditPaymentsCommand.php         # ✅ Fase 1
├── AuditBusinessRulesCommand.php    # ✅ Fase 1
├── AuditCurrencyCommand.php         # ⏳ Fase 2
├── AuditCostsCommand.php            # ⏳ Fase 2
└── AuditDuplicatesCommand.php       # ⏳ Fase 2

app/Services/
├── AuditReportService.php           # ⏳ Fase 3 (novo)
└── CommissionPaymentValidationService.php  # Existente (usado)

storage/logs/
├── audit_settlements_*.json         # Relatórios gerados
├── audit_payments_*.json
├── audit_business_rules_*.json
└── ...

docs/
└── AUDIT_SYSTEM_EXPANSION.md        # Este arquivo
```

### Padrão Arquitetural

Todos os comandos seguem a mesma estrutura:

```php
class Audit{Name}Command extends Command
{
    // 1. Signature e description
    protected $signature = 'gig:audit-{name} {--scan-only} {--auto-fix} ...';
    protected $description = '...';

    // 2. Estatísticas
    protected array $stats = [
        'total_*' => 0,
        'issues_found' => 0,
        'corrections_applied' => 0,
        'errors' => 0,
    ];

    // 3. Issues encontradas
    protected array $issues = [];

    // 4. Injeção de dependências
    public function __construct(GigFinancialCalculatorService $calculator) { }

    // 5. Métodos principais
    public function handle() { }
    protected function performAudit() { }
    protected function audit{Entity}() { }
    protected function check{ValidationRule}() { }
    protected function applyFix() { }
    protected function displayFinalReport() { }
    protected function saveDetailedReport() { }
}
```

---

## 📦 Fase 1: Comandos Críticos (CONCLUÍDA)

### 1.1 AuditSettlementsCommand ✅

**Arquivo:** `app/Console/Commands/AuditSettlementsCommand.php`
**Comando:** `gig:audit-settlements`

#### Validações Implementadas

| # | Tipo | Severidade | Descrição | Auto-Fix |
|---|------|------------|-----------|----------|
| 1 | `referential_integrity` | Critical | Settlement sem gig válido | ✅ Sim |
| 2 | `payment_rule_violation` | Critical | Pagamento de artista para evento futuro sem exceção | ❌ Não |
| 3 | `payment_rule_violation` | Critical | Pagamento de booker para evento futuro sem exceção | ❌ Não |
| 4 | `value_divergence` | Critical/Warning | Divergência no valor pago ao artista | ❌ Não |
| 5 | `value_divergence` | Critical/Warning | Divergência no valor pago ao booker | ❌ Não |
| 6 | `missing_proof` | Warning | Pagamento sem comprovante | ❌ Não |
| 7 | `date_logic` | Warning | Data do settlement anterior à data do evento | ❌ Não |

#### Integração com Serviços

- **GigFinancialCalculatorService**
  - `calculateArtistNetPayout(Gig $gig)` - Calcula valor esperado para artista
  - `calculateBookerCommissionBrl(Gig $gig)` - Calcula valor esperado para booker

- **CommissionPaymentValidationService**
  - `validateArtistPayment(Gig $gig, bool $allowExceptions)` - Valida pagamento de artista
  - `validateBookerCommissionPayment(Gig $gig, bool $allowExceptions)` - Valida pagamento de booker

#### Exemplo de Uso

```bash
# Escanear apenas (sem correções)
sail artisan gig:audit-settlements --scan-only

# Correção automática
sail artisan gig:audit-settlements --auto-fix --batch-size=200

# Com filtro de data
sail artisan gig:audit-settlements --date-from=2025-01-01 --date-to=2025-12-31

# Modo interativo (padrão)
sail artisan gig:audit-settlements
```

#### Relatório Gerado

**Localização:** `storage/logs/audit_settlements_YYYY-MM-DD_HH-mm-ss.json`

```json
{
  "timestamp": "2025-10-07T14:30:00.000000Z",
  "command": "gig:audit-settlements",
  "stats": {
    "total_settlements": 150,
    "issues_found": 8,
    "corrections_applied": 2,
    "errors": 0
  },
  "issues": [
    {
      "settlement_id": 123,
      "gig_id": 456,
      "settlement_date": "2025-09-15",
      "issues": [
        {
          "type": "value_divergence",
          "severity": "critical",
          "description": "Divergência no valor pago ao artista...",
          "field": "artist_payment_value",
          "current_value": "5000.00",
          "suggested_value": "5250.00",
          "details": "Divergência percentual: 5%"
        }
      ]
    }
  ]
}
```

---

### 1.2 AuditPaymentsCommand ✅

**Arquivo:** `app/Console/Commands/AuditPaymentsCommand.php`
**Comando:** `gig:audit-payments`

#### Validações Implementadas

| # | Tipo | Severidade | Descrição | Auto-Fix |
|---|------|------------|-----------|----------|
| 1 | `orphan_payments` | Critical | Payments sem gig válido | ✅ Sim |
| 2 | `payment_total_divergence` | Critical/Warning | Soma de parcelas ≠ valor do contrato | ❌ Não |
| 3 | `overpayment` | Warning | Valor recebido > valor devido | ❌ Não |
| 4 | `confirmed_without_value` | Warning | Parcela confirmada sem `received_value_actual` | ✅ Sim |
| 5 | `currency_mismatch` | Critical | Moeda da parcela diferente da gig | ✅ Sim |
| 6 | `overdue_payments` | Warning | Parcelas vencidas há >30 dias | ❌ Não |
| 7 | `payment_status_inconsistency` | Critical | Status `payment_status` inconsistente | ✅ Sim |

#### Regras de Negócio

**Validação de Totais:**
```
Divergência = |cache_value - Σ(payments.due_value)|
Tolerância: R$ 0.01
```

**Sincronização de Status:**
```
Se todas parcelas confirmadas E payment_status ≠ 'pago'
  → Sugestão: atualizar para 'pago'

Se há parcelas pendentes E payment_status = 'pago'
  → Sugestão: atualizar para 'a_vencer'
```

#### Exemplo de Uso

```bash
# Escanear e gerar relatório
sail artisan gig:audit-payments --scan-only

# Corrigir automaticamente inconsistências críticas
sail artisan gig:audit-payments --auto-fix

# Processar em lotes maiores
sail artisan gig:audit-payments --batch-size=500
```

---

### 1.3 AuditBusinessRulesCommand ✅

**Arquivo:** `app/Console/Commands/AuditBusinessRulesCommand.php`
**Comando:** `gig:audit-business-rules`

#### Validações Implementadas

| # | Tipo | Severidade | Descrição | Auto-Fix |
|---|------|------------|-----------|----------|
| 1 | `commission_exceeds_cache` | Critical | Comissão da agência > valor do cachê | ✅ Sim |
| 2 | `commission_exceeds_cache` | Critical | Comissão do booker > valor do cachê | ✅ Sim |
| 3 | `booker_commission_without_booker` | Warning | Comissão de booker sem booker atribuído | ✅ Sim |
| 4 | `liquid_commission_incorrect` | Critical | `liquid_commission_value` incorreto | ✅ Sim |
| 5 | `completed_without_settlement` | Warning | Evento >30 dias sem settlement | ❌ Não |
| 6 | `cancelled_with_payments` | Warning | Gig cancelada com pagamentos confirmados | ❌ Não |
| 7 | `invalid_commission_rate` | Critical | Taxa percentual fora do intervalo 0-100 | ❌ Não |

#### Fórmulas Aplicadas

**Comissão Líquida:**
```php
liquid_commission_value = agency_commission_value - booker_commission_value
```

**Recálculo Automático:**
```php
// Usando GigFinancialCalculatorService
$gig->agency_commission_value = $calculator->calculateAgencyGrossCommissionBrl($gig);
$gig->booker_commission_value = $calculator->calculateBookerCommissionBrl($gig);
$gig->liquid_commission_value = $gig->agency_commission_value - $gig->booker_commission_value;
```

#### Exemplo de Uso

```bash
# Modo scan-only (recomendado primeiro)
sail artisan gig:audit-business-rules --scan-only

# Recalcular comissões automaticamente
sail artisan gig:audit-business-rules --auto-fix

# Focar em eventos recentes
sail artisan gig:audit-business-rules --date-from=2025-01-01
```

---

## 🧪 Fase 2: Testes e Validação

### 2.1 Estratégia de Testes

#### Testes Manuais

Para cada comando, executar a seguinte sequência:

```bash
# 1. Escanear sem correções
sail artisan gig:audit-{comando} --scan-only

# 2. Verificar relatório gerado
cat storage/logs/audit_{comando}_*.json | jq '.stats'

# 3. Se houver issues críticas, executar com auto-fix
sail artisan gig:audit-{comando} --auto-fix

# 4. Validar no banco de dados
sail artisan tinker
>>> Gig::find({gig_id})->refresh();
```

#### Cenários de Teste

**Settlement:**
- [ ] Settlement com gig válido e valores corretos
- [ ] Settlement com divergência de valores (<5%)
- [ ] Settlement com divergência de valores (>5%)
- [ ] Settlement para evento futuro sem exceção
- [ ] Settlement para evento futuro COM exceção (notas)
- [ ] Settlement órfão (sem gig_id válido)

**Payments:**
- [ ] Gig com soma de parcelas = cache_value
- [ ] Gig com soma de parcelas ≠ cache_value
- [ ] Parcela confirmada sem received_value_actual
- [ ] Parcela com moeda diferente da gig
- [ ] Gig com status "pago" e parcelas pendentes
- [ ] Gig com todas parcelas pagas mas status ≠ "pago"

**Business Rules:**
- [ ] Comissão da agência < valor do cachê (válido)
- [ ] Comissão da agência > valor do cachê (inválido)
- [ ] Booker sem comissão (válido)
- [ ] Comissão de booker sem booker_id (inválido)
- [ ] liquid_commission_value calculado corretamente
- [ ] liquid_commission_value incorreto

### 2.2 Checklist de Validação

Antes de fazer commit, validar:

- [ ] Comando executa sem erros
- [ ] Progress bar funciona corretamente
- [ ] Relatório JSON é gerado em `storage/logs/`
- [ ] Estatísticas estão corretas (`total_*`, `issues_found`, etc.)
- [ ] Correções auto-fix funcionam corretamente
- [ ] Logs detalhados no Laravel Log
- [ ] Confirmação interativa funciona (modo padrão)
- [ ] Filtros de data funcionam (`--date-from`, `--date-to`)
- [ ] Batch processing funciona sem memory overflow
- [ ] Código segue Laravel Pint standards

---

## 📝 Fase 3: Commits e Versionamento

### 3.1 Estratégia de Commits

Cada comando será commitado individualmente após validação:

```bash
# Formato do commit
git add app/Console/Commands/Audit{Name}Command.php
git commit -m "feat: add gig:audit-{name} command for {description}

- Implements {X} validation rules
- Supports --scan-only and --auto-fix modes
- Generates JSON reports in storage/logs/
- Integrates with {Service1}, {Service2}

Tested scenarios:
- {scenario 1}
- {scenario 2}
- {scenario 3}

Closes #{issue_number}"
```

### 3.2 Sequência de Commits Planejada

#### Commit 1: Documentation
```bash
git add docs/AUDIT_SYSTEM_EXPANSION.md
git commit -m "docs: add comprehensive audit system expansion documentation

- Documents implementation strategy for 6 new audit commands
- Details validation rules and auto-fix capabilities
- Includes testing checklist and commit strategy
- Provides usage examples and integration details"
```

#### Commit 2: Settlements Audit
```bash
# Após testar AuditSettlementsCommand
git add app/Console/Commands/AuditSettlementsCommand.php
git commit -m "feat: add gig:audit-settlements command for financial settlements audit

- Implements 7 critical validation rules for settlements
- Validates payment rules for future events (exception handling)
- Detects value divergences between settlements and calculations
- Checks for missing payment proofs
- Integrates with GigFinancialCalculatorService and CommissionPaymentValidationService

Tested scenarios:
- Settlement with valid gig and correct values
- Settlement with value divergence (<5% and >5%)
- Settlement for future event without authorized exception
- Orphan settlements (invalid gig_id)

Auto-fix capabilities:
- Removes orphan settlements automatically"
```

#### Commit 3: Payments Audit
```bash
# Após testar AuditPaymentsCommand
git add app/Console/Commands/AuditPaymentsCommand.php
git commit -m "feat: add gig:audit-payments command for payment parcels validation

- Implements 7 validation rules for client payment parcels
- Validates payment totals vs contract value
- Detects overpayments and overdue payments
- Synchronizes payment status with confirmed parcels
- Fixes currency mismatches automatically

Tested scenarios:
- Gig with correct parcel sum
- Gig with parcel sum divergence
- Confirmed parcel without received_value_actual
- Parcel with different currency than gig
- Payment status inconsistencies

Auto-fix capabilities:
- Removes orphan payments
- Fills missing received_value_actual
- Synchronizes currencies
- Updates payment_status"
```

#### Commit 4: Business Rules Audit
```bash
# Após testar AuditBusinessRulesCommand
git add app/Console/Commands/AuditBusinessRulesCommand.php
git commit -m "feat: add gig:audit-business-rules command for complex business logic validation

- Implements 7 validation rules for business logic
- Validates commissions vs cache value
- Recalculates liquid commission automatically
- Detects completed gigs without settlement (>30 days)
- Validates cancelled gigs with confirmed payments

Tested scenarios:
- Commission exceeding cache value
- Booker commission without booker assigned
- Incorrect liquid_commission_value calculation
- Completed events without settlement
- Invalid commission rate (outside 0-100%)

Auto-fix capabilities:
- Recalculates commissions using GigFinancialCalculatorService
- Zeros booker commission when no booker assigned
- Recalculates liquid_commission_value"
```

### 3.3 Comandos Git Preparados

```bash
# Após testes completos da Fase 1
git add docs/AUDIT_SYSTEM_EXPANSION.md
git commit -m "docs: add comprehensive audit system expansion documentation"

git add app/Console/Commands/AuditSettlementsCommand.php
git commit -m "feat: add gig:audit-settlements command for financial settlements audit"

git add app/Console/Commands/AuditPaymentsCommand.php
git commit -m "feat: add gig:audit-payments command for payment parcels validation"

git add app/Console/Commands/AuditBusinessRulesCommand.php
git commit -m "feat: add gig:audit-business-rules command for complex business logic validation"
```

---

## 🔄 Próximas Etapas (Fase 4+)

### Comandos Restantes

**4.1 AuditCurrencyCommand** (Fase 2)
- Validar moedas consistentes entre gig, payments e costs
- Detectar taxas de câmbio desatualizadas
- Recalcular valores em BRL usando `ExchangeRateService`

**4.2 AuditCostsCommand** (Fase 2)
- Validar custos órfãos
- Verificar cost_center válido
- Sincronizar moedas de custos

**4.3 AuditDuplicatesCommand** (Fase 3)
- Detectar gigs com mesmo contract_number
- Identificar eventos duplicados (mesmo artista, data, local)
- Sugerir unificação de gigs soft-deleted

### Interface Web (Fase 3)

**Atualizar:** `resources/views/audit/data-audit.blade.php`

Adicionar:
- Dropdown para seleção do tipo de auditoria
- Cards individuais para cada comando
- Dashboard consolidado (health score geral)
- Histórico de auditorias executadas
- Botão "Executar Todas as Auditorias"

### Serviço Consolidado (Fase 3)

**Criar:** `app/Services/AuditReportService.php`

Funcionalidades:
- Consolidar relatórios de múltiplas auditorias
- Gerar health score geral do sistema
- Agendar auditorias automáticas (cron)
- Enviar notificações de issues críticas

---

## 📊 Métricas de Sucesso

### KPIs

- **Cobertura de Validações:** 95%+ das regras de negócio auditadas
- **Taxa de Correções Automáticas:** >80% das issues críticas auto-corrigíveis
- **Tempo de Execução:** <5 minutos para 1000+ gigs
- **Taxa de Erro:** <1% dos comandos com falhas
- **Redução de Inconsistências:** >70% em 30 dias após implementação

### Relatório Consolidado (Futuro)

```json
{
  "overall_health_score": 92.5,
  "audits_run": [
    {
      "command": "gig:audit-settlements",
      "last_run": "2025-10-07T14:30:00Z",
      "issues_found": 8,
      "critical_issues": 2,
      "status": "needs_attention"
    },
    {
      "command": "gig:audit-payments",
      "last_run": "2025-10-07T14:35:00Z",
      "issues_found": 3,
      "critical_issues": 0,
      "status": "ok"
    }
  ],
  "trends": {
    "last_7_days": {
      "total_issues": 45,
      "resolved_issues": 38,
      "new_issues": 7
    }
  }
}
```

---

## 🔒 Considerações de Segurança

### Permissões

- Comandos devem ser executados apenas por usuários admin/auditor
- Logs detalhados para rastreabilidade de correções
- Confirmação obrigatória para correções críticas em modo interativo

### Backup

- **SEMPRE** fazer backup do banco antes de executar com `--auto-fix`
- Testar primeiro com `--scan-only` em produção
- Validar relatórios antes de aplicar correções em massa

---

## 📚 Referências

- [Laravel Artisan Commands](https://laravel.com/docs/11.x/artisan)
- [Laravel Pint](https://laravel.com/docs/11.x/pint)
- [CLAUDE.md - Regras de Negócio](../CLAUDE.md)
- [Services API Documentation](./SERVICES_API.md)

---

## 👥 Equipe

**Desenvolvedor:** Gabriel (gacpac) + Claude Code
**Revisor:** TBD
**Data:** 2025-10-07

---

## 📝 Changelog

### [Unreleased] - 2025-10-07

#### Added
- AuditSettlementsCommand com 7 validações de settlements
- AuditPaymentsCommand com 7 validações de payments
- AuditBusinessRulesCommand com 7 validações de regras de negócio
- Documentação completa em AUDIT_SYSTEM_EXPANSION.md

#### Changed
- N/A

#### Fixed
- N/A

---

**Fim da Documentação - Fase 1**
