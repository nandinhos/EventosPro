# Catálogo de Services - EventosPro

> **Referência Completa** dos 17 services de negócio do sistema
> **Fonte Detalhada**: `doc-project/SERVICES_API.md` (1400+ linhas)
> **Versão**: 1.0 | **Atualizado**: 2026-02-10

---

## 📋 Índice Rápido

| Categoria | Services |
|-----------|----------|
| **Financeiro (Core)** | GigFinancialCalculatorService |
| **Projeções** | FinancialProjectionService, CashFlowProjectionService, DreProjectionService |
| **Relatórios** | FinancialReportService, AuditService, DashboardService |
| **Validação** | CommissionPaymentValidationService, AuditService |
| **Gestão de Entidades** | ArtistFinancialsService, BookerFinancialsService, UserManagementService |
| **Suporte** | ExchangeRateService, CurrencyConversionService, NotificationService, SettlementService, GigAuditCommandService |

---

## 💰 Services Financeiros (Core)

### 1. GigFinancialCalculatorService

**Responsabilidade**: Centraliza todos os cálculos financeiros de gigs

**Localização**: `app/Services/GigFinancialCalculatorService.php`

**Dependências**: Nenhuma (service autônomo)

**Métodos Públicos** (11 métodos críticos):

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `calculateContractValueBrl(Gig $gig)` | float | Valor do contrato em BRL (com conversão de moeda) |
| `calculateTotalAgencyCommission(Gig $gig)` | float | Comissão da agência |
| `calculateTotalAgencyCommissionBrl(Gig $gig)` | float | Comissão da agência em BRL (com conversão) |
| `calculateNetValueForArtist(Gig $gig)` | float | Valor líquido para artista (pós-despesas) |
| `calculateNetValueForArtistBrl(Gig $gig)` | float | Valor líquido para artista em BRL |
| `calculateBookerCommission(Gig $gig)` | float | Comissão do booker |
| `calculateBookerCommissionBrl(Gig $gig)` | float | Comissão do booker em BRL |
| `calculateTotalGigCosts(Gig $gig)` | float | Total de custos da gig |
| `calculateTotalGigCostsBrl(Gig $gig)` | float | Total de custos em BRL |
| `getTotalPaidByClient(Gig $gig)` | float | Total pago pelo cliente |
| `getTotalPaidByClientBrl(Gig $gig)` | float | Total pago em BRL |

**Regras Importantes**:
- Conversão de moedas sempre para BRL quando necessário
- Avisos de conversão exibidos quando moeda original ≠ BRL
- Cálculo de comissão: `contract_value * commission_rate / 100`
- Valor líquido do artista: `contract - agency_commission - total_costs`

**Exemplo de Uso**:
```php
$calculator = app(GigFinancialCalculatorService::class);
$gig = Gig::find(1);

$contractBrl = $calculator->calculateContractValueBrl($gig);
$agencyCommission = $calculator->calculateTotalAgencyCommissionBrl($gig);
$netValue = $calculator->calculateNetValueForArtistBrl($gig);
```

**Fonte Detalhada**: `doc-project/GIG_FINANCIAL_CALCULATOR_API.md`

---

## 📈 Services de Projeções

### 2. FinancialProjectionService

**Responsabilidade**: Projeções financeiras de gigs e entidades

**Dependências**: GigFinancialCalculatorService

**Métodos Principais**:
- Projetar receitas de gigs futuras
- Projetar despesas por centro de custo
- Projetar fluxo de caixa por período

---

### 3. CashFlowProjectionService

**Responsabilidade**: Projeção de fluxo de caixa

**Dependências**: FinancialProjectionService, ExchangeRateService

**Métodos Principais**:
- Entradas previstas (pagamentos de clientes)
- Saídas previstas (pagamentos a artistas/bookers)
- Saldo projetado por período

---

### 4. DreProjectionService

**Responsabilidade**: Projeção de DRE (Demonstração do Resultado do Exercício)

**Dependências**: FinancialProjectionService

**Métodos Principais**:
- Receita bruta projetada
- Custos e despesas projetados
- Resultado líquido projetado
- Margens por categoria

---

## 📊 Services de Relatórios

### 5. FinancialReportService

**Responsabilidade**: Geração de relatórios financeiros

**Dependências**: GigFinancialCalculatorService, ExchangeRateService

**Métodos Principais**:

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `setFilters(array $filters)` | self | Define filtros de data (start_date, end_date) |
| `generatePerformanceReport(Collection $gigs)` | array | Relatório de performance por gig |
| `generateMonthlySummary(Collection $gigs)` | array | Resumo mensal consolidado |
| `generateDueDatesReport(Collection $payments)` | array | Relatório de vencimentos agrupado |

**Estrutura do Relatório de Performance**:
```php
[
    'period' => [
        'start_date' => 'Y-m-d',
        'end_date' => 'Y-m-d'
    ],
    'total_gigs' => int,
    'total_cache_brl' => float,
    'total_revenue_brl' => float,
    'total_costs_brl' => float,
    'total_agency_commission_brl' => float,
    'total_booker_commission_brl' => float,
    'net_profit_brl' => float,
    'gigs_data' => [
        [
            'id' => int,
            'date' => 'date',
            'artist_name' => string,
            'cache_brl' => float,
            'revenue_brl' => float,
            'costs_brl' => float,
            'agency_commission_brl' => float,
            'booker_commission_brl' => float,
            'net_profit_brl' => float,
            'client_payments' => array,
            'costs_breakdown' => array
        ]
    ]
]
```

---

### 6. AuditService

**Responsabilidade**: Auditoria e análise financeira de gigs

**Dependências**: GigFinancialCalculatorService

**Métodos Principais**:

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `calculateGigAuditData(Gig $gig)` | array | Dados de auditoria de um gig |
| `calculateBulkAuditData(Collection $gigs)` | array | Auditoria em lote |
| `validateGigIntegrity(Gig $gig)` | array | Valida integridade dos dados |
| `generateConsolidatedReport(Collection $gigs)` | string | Relatório consolidado |

**Estrutura de Auditoria Individual**:
```php
[
    'gig_id' => int,
    'contract_value_brl' => float,
    'total_paid_brl' => float,
    'total_pending_brl' => float,
    'divergence_amount_brl' => float,
    'divergence_percentage' => float,
    'has_divergence' => bool,
    'divergence_classification' => string, // 'low', 'medium', 'high'
    'payment_status' => string,
    'overdue_payments' => int,
    'upcoming_payments' => int,
    'currency_inconsistencies' => array,
    'observations' => array
]
```

**Classificação de Divergência**:
- **low**: < 5%
- **medium**: 5% - 15%
- **high**: > 15%

---

### 7. DashboardService

**Responsabilidade**: Dados consolidados para o dashboard

**Dependências**: GigFinancialCalculatorService, Gig, Carbon, DB

**Métodos Principais**:

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `setFilters(array $filters)` | self | Define período (start_date, end_date) |
| `getFirstAndLastMonth()` | array | Primeiro e último mês com dados |
| `getDashboardData()` | array | Dados completos do dashboard |
| `getChartLabels()` | array | Labels do gráfico faturamento |
| `getChartData()` | array | Dados do gráfico faturamento |

**Dados Retornados**:
- Contadores gerais (totalGigsCount, overdueClientPaymentsCount, etc)
- Métricas do período (faturamento, comissões)
- Métricas de vendas (gigs por data de contrato)
- URLs de relatórios
- Próximas gigs (5 próximas)
- Dados do gráfico (labels, data, count)

---

## ✅ Services de Validação

### 8. CommissionPaymentValidationService

**Responsabilidade**: Valida pagamentos de comissões (artistas e bookers)

**Métodos Principais**:

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `validateBookerCommissionPayment(Gig $gig, bool $allowExceptions)` | array | Valida pagamento ao booker |
| `validateArtistPayment(Gig $gig, bool $allowExceptions)` | array | Valida pagamento ao artista |
| `validateBatchPayment($gigs, bool $allowExceptions)` | array | Valida múltiplos gigs |
| `createPaymentException(Gig $gig, string $reason, string $authorizedBy)` | bool | Cria exceção autorizada |

**Regras de Validação**:
- Eventos **já realizados**: sempre válidos
- Eventos **futuros**: inválidos por padrão
- Eventos futuros com **exceção autorizada**: válidos se `allowExceptions = true`

**Palavras-chave para Exceção**:
- "exceção" ou "excecao"
- "antecipado"
- "autorizado"

**Estrutura de Retorno Individual**:
```php
[
    'valid' => bool,
    'message' => string
]
```

**Estrutura de Retorno em Lote**:
```php
[
    'valid_gigs' => Collection,
    'invalid_gigs' => Collection,
    'errors' => array
]
```

---

## 👤 Services de Gestão de Entidades

### 9. ArtistFinancialsService

**Responsabilidade**: Métricas financeiras de artistas

**Dependências**: GigFinancialCalculatorService

**Métodos Principais**:

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `getFinancialMetrics(Artist $artist, ?Collection $gigs)` | array | Métricas financeiras |

**Retorno**:
```php
[
    'total_gigs' => int,
    'cache_received_brl' => float,
    'cache_pending_brl' => float,
    'totalGrossFee' => float
]
```

---

### 10. BookerFinancialsService

**Responsabilidade**: Métricas financeiras de bookers

**Dependências**: GigFinancialCalculatorService

**Métodos Principais**:
- Total de gigs por booker
- Comissões recebidas
- Comissões pendentes
- Taxa média de comissão

---

### 11. UserManagementService

**Responsabilidade**: Gestão completa de usuários e associação com bookers

**Dependências**: User, Booker, DB, Hash, Log

**Métodos Principais**:

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `createUser(array $userData)` | User | Cria usuário +/- booker |
| `updateUser(User $user, array $userData)` | User | Atualiza usuário +/- booker |
| `deleteUser(User $user)` | bool | Soft delete usuário +/- booker |

**Parâmetros de userData**:
- `name` (string): Nome do usuário
- `email` (string): Email do usuário
- `password` (string): Senha do usuário
- `is_booker` (bool, opcional): Se usuario é booker
- `booker_creation_type` (string, opcional): 'new' ou 'existing'
- `booker_name` (string, opcional): Nome do novo booker
- `existing_booker_id` (int, opcional): ID do booker existente
- `default_commission_rate` (float, opcional): Taxa de comissão
- `contact_info` (string, opcional): Informações de contato

**Regras de Negócio**:
- As operações são atômicas (usam transações)
- Um booker só pode estar associado a um usuário por vez
- Nomes de bookers são sempre convertidos para maiúsculas
- Se password não fornecido, mantém senha atual

---

## 🔄 Services de Suporte

### 12. ExchangeRateService

**Responsabilidade**: Conversão de moedas e taxas de câmbio

**Métodos Principais**:
- Converter valor entre moedas
- Obter taxa de câmbio atual
- Histórico de taxas

---

### 13. CurrencyConversionService

**Responsabilidade**: Serviços específicos de conversão de moedas

**Dependências**: ExchangeRateService

**Métodos Principais**:
- Conversão de valores de gigs para BRL
- Conversão de custos para BRL
- Conversão de comissões para BRL

---

### 14. NotificationService

**Responsabilidade**: Sistema de notificações do sistema

**Métodos Principais**:
- Notificar usuários
- Enviar alertas de vencimento
- Notificações de sistema

---

### 15. SettlementService

**Responsabilidade**: Gestão de acertos financeiros

**Métodos Principais**:
- Criar acerto para artista
- Criar acerto para booker
- Atualizar status de acerto
- Histórico de acertos

---

### 16. GigAuditCommandService

**Responsabilidade**: Comando de auditoria de gigs (CLI)

**Métodos Principais**:
- Executar auditoria em lote
- Gerar relatório de divergências
- Validar integridade de gigs

---

## 📝 Patterns de Uso

### Padrão 1: Injeção de Dependência no Controller

```php
use App\Services\GigFinancialCalculatorService;

class GigController extends Controller
{
    public function __construct(
        private GigFinancialCalculatorService $calculator
    ) {}

    public function show(Gig $gig)
    {
        $netValue = $this->calculator->calculateNetValueForArtistBrl($gig);
        // ...
    }
}
```

### Padrão 2: Resolução via Container

```php
$service = app(ClassName::class);
$result = $service->methodName($params);
```

### Padrão 3: Service com Filtros

```php
$service = app(ClassName::class);
$data = $service->setFilters([
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31'
])->getData();
```

---

## ⚠️ Regras Essenciais

1. **GigFinancialCalculatorService é OBRIGATÓRIO** para todos os cálculos financeiros
2. **Nunca calcule valores diretamente no Controller** - use os services
3. **Conversão para BRL** é obrigatória em relatórios
4. **Validação de pagamentos** via `CommissionPaymentValidationService` antes de pagar
5. **Auditoria** deve ser executada no fechamento de cada gig

---

**Versão**: 1.0
**Fonte Detalhada**: `doc-project/SERVICES_API.md` (1400+ linhas)
**Próximo Referência**: `.aidev/context/routes-catalog.md`