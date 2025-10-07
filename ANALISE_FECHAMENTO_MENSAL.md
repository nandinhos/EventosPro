# Análise: Fechamento Mensal - Integração com GigFinancialCalculatorService

## 📊 Status Atual

### Métricas Implementadas

O relatório de Fechamento Mensal (`MonthlyClosingController.php`) **parcialmente** utiliza o `GigFinancialCalculatorService`, mas com **inconsistências críticas**.

#### ✅ Métricas Corretamente Implementadas:
1. **Comissão Booker** - `calculateBookerCommissionBrl()`
2. **Comissão Agência Líquida** - `calculateAgencyNetCommissionBrl()`
3. **Cachê Líquido Base** (booker_data) - `calculateGrossCashBrl()`

#### ❌ Problemas Identificados:

**1. Cachê Bruto (total_cache_brl) - INCORRETO**
```php
// Linha 256 - Controller
$totalCacheBrl = $gigs->sum('cache_value_brl');
```
**Problema**: Usa valor direto do banco, **ignorando despesas confirmadas**.

**Correto**: Deveria usar `calculateGrossCashBrl()` que deduz despesas.

**2. Cálculo Manual na View**
```php
// Linha 119-122 - View
$totalNetValue = $reportData['total_cache_brl'] -
                 $reportData['total_booker_commission'] -
                 $reportData['total_agency_commission'];
```
**Problema**: Cálculo duplicado, deveria vir do controller.

**3. Despesas Ignoradas**
```php
// Linha 257-259 - Controller
$totalDespesas = $gigs->sum(function ($gig) {
    return $gig->costs->where('is_confirmed', true)->sum('value'); // ❌ Soma 'value', não 'value_brl'
});
```
**Problema**:
- Soma campo `value` (moeda original) ao invés de `value_brl`
- Não usa método do service
- **Valor nem é exibido no relatório!**

## 🚨 Métricas FALTANTES do GigFinancialCalculatorService

O service possui **14 métodos**, mas o Fechamento Mensal usa apenas **3**!

### Métricas Críticas Ausentes:

| Método do Service | Descrição | Importância | Status |
|-------------------|-----------|-------------|--------|
| `calculateArtistNetPayoutBrl()` | Cachê líquido do artista (antes reembolso) | 🔴 CRÍTICO | ❌ Ausente |
| `calculateArtistInvoiceValueBrl()` | Valor da NF do artista | 🔴 CRÍTICO | ❌ Ausente |
| `calculateAgencyGrossCommissionBrl()` | Comissão bruta da agência (antes deduzir booker) | 🟡 IMPORTANTE | ❌ Ausente |
| `calculateTotalConfirmedExpensesBrl()` | Total de despesas confirmadas | 🔴 CRÍTICO | ❌ Ausente |
| `calculateTotalReimbursableExpensesBrl()` | Despesas reembolsáveis (NF artista) | 🟡 IMPORTANTE | ❌ Ausente |
| `calculateTotalReceivedInOriginalCurrency()` | Total já recebido do cliente | 🔴 CRÍTICO | ❌ Ausente |
| `calculateTotalReceivableInOriginalCurrency()` | Total a receber (parcelas pendentes) | 🔴 CRÍTICO | ❌ Ausente |
| `calculatePendingBalanceInOriginalCurrency()` | Saldo pendente de recebimento | 🔴 CRÍTICO | ❌ Ausente |

## 🎯 Proposta de Melhoria

### 1. Novos Cards de Métricas (Grid 2x4)

#### Linha Superior - Receita & Despesas:
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Cachê Bruto     │ Despesas        │ Cachê Líquido   │ Valor Recebido  │
│ (contrato)      │ Confirmadas     │ Base            │ (do cliente)    │
│ R$ XXX,XX       │ R$ XXX,XX       │ R$ XXX,XX       │ R$ XXX,XX       │
│ X gigs          │ X itens         │ - XX%           │ XX% recebido    │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

#### Linha Inferior - Comissões & Líquido:
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Comissão        │ Comissão        │ Cachê Líquido   │ Saldo Pendente  │
│ Booker          │ Agência         │ Artista         │ a Receber       │
│ R$ XXX,XX       │ R$ XXX,XX       │ R$ XXX,XX       │ R$ XXX,XX       │
│ XX% do bruto    │ XX% do bruto    │ (+ reembolsos)  │ parcelas pend.  │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

### 2. Atualizar Tabela "Faturamento por Booker"

**Colunas Atuais:**
- Booker
- Cachê Líquido
- Comissão Booker
- Valor Líquido
- Gigs

**Colunas Propostas** (adicionar):
```
| Booker | Cachê Bruto | Despesas | Cachê Líquido | Com. Booker | Com. Agência | NF Artista | Recebido | Pendente | Gigs |
```

### 3. Atualizar Tabela "Detalhes das Gigs"

**Colunas Atuais:**
- Data
- Local
- Cachê Bruto
- Comissão Booker
- Comissão Agência

**Colunas Propostas** (adicionar):
```
| Data | Artista | Local | Cachê Bruto | Despesas | Líquido | Com. Booker | Com. Agência | NF Artista | Status Pgto |
```

### 4. Novo Gráfico: "Composição Financeira"

Gráfico de barras empilhadas mostrando:
- Cachê Bruto (base)
- - Despesas (vermelho)
- - Comissão Booker (roxo)
- - Comissão Agência (índigo)
- = Cachê Líquido Artista (verde)

### 5. Novo Card: "Status de Recebimento"

```
┌─────────────────────────────────────────┐
│ Status de Recebimento do Cliente        │
│                                         │
│ Total Contratado:    R$ XXX,XXX.XX     │
│ Já Recebido:         R$ XXX,XXX.XX (XX%)│
│ A Receber:           R$ XXX,XXX.XX (XX%)│
│ Saldo Pendente:      R$ XXX,XXX.XX     │
│                                         │
│ [==============░░░░░░░] XX% recebido   │
└─────────────────────────────────────────┘
```

## 📝 Alterações no Controller

### Adicionar ao método `generateReportData()`:

```php
// Métricas de Despesas
$totalDespesasConfirmadas = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateTotalConfirmedExpensesBrl($gig);
});

$totalDespesasReembolsaveis = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateTotalReimbursableExpensesBrl($gig);
});

// Métricas de Artista
$totalArtistNetPayout = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateArtistNetPayoutBrl($gig);
});

$totalArtistInvoice = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateArtistInvoiceValueBrl($gig);
});

// Métricas de Recebimento
$totalRecebido = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateTotalReceivedInOriginalCurrency($gig);
});

$totalAReceber = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateTotalReceivableInOriginalCurrency($gig);
});

$saldoPendente = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculatePendingBalanceInOriginalCurrency($gig);
});

// Comissão Bruta da Agência
$totalAgencyGrossCommission = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateAgencyGrossCommissionBrl($gig);
});
```

### Retornar no array:

```php
return [
    // ... existentes ...
    'total_despesas_confirmadas' => $totalDespesasConfirmadas,
    'total_despesas_reembolsaveis' => $totalDespesasReembolsaveis,
    'total_artist_net_payout' => $totalArtistNetPayout,
    'total_artist_invoice' => $totalArtistInvoice,
    'total_recebido' => $totalRecebido,
    'total_a_receber' => $totalAReceber,
    'saldo_pendente' => $saldoPendente,
    'total_agency_gross_commission' => $totalAgencyGrossCommission,
];
```

## 🔧 Correções Urgentes

### 1. Corrigir Cachê Bruto (LINHA 256)

**Antes:**
```php
$totalCacheBrl = $gigs->sum('cache_value_brl');
```

**Depois:**
```php
$totalCacheBruto = $gigs->sum('cache_value_brl'); // Valor do contrato
$totalCacheLiquidoBase = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateGrossCashBrl($gig); // Contrato - Despesas
});
```

### 2. Corrigir Despesas (LINHA 257-259)

**Antes:**
```php
$totalDespesas = $gigs->sum(function ($gig) {
    return $gig->costs->where('is_confirmed', true)->sum('value');
});
```

**Depois:**
```php
$totalDespesasConfirmadas = $gigs->sum(function ($gig) {
    return $this->gigCalculator->calculateTotalConfirmedExpensesBrl($gig);
});
```

### 3. Remover Cálculo Manual da View

**Antes (view linha 119-122):**
```php
$totalNetValue = $reportData['total_cache_brl'] -
                 $reportData['total_booker_commission'] -
                 $reportData['total_agency_commission'];
```

**Depois (enviar do controller):**
```php
'total_net_value' => $totalCacheLiquidoBase - $totalBookerCommission - $totalAgencyCommission
```

## 📊 Resumo de Impacto

### Antes (Status Atual):
- ❌ Cachê Bruto calculado errado (ignora despesas)
- ❌ Despesas calculadas errado (moeda incorreta)
- ❌ Despesas não exibidas no relatório
- ❌ Sem informação de recebimento
- ❌ Sem informação de cachê do artista
- ❌ Sem informação de NF do artista
- ⚠️ Uso parcial do service (21% dos métodos)

### Depois (Proposta):
- ✅ Todos os cálculos via service (100% padronizado)
- ✅ 8 novos cards de métricas
- ✅ Tabelas enriquecidas com dados financeiros completos
- ✅ Novo gráfico de composição financeira
- ✅ Status de recebimento detalhado
- ✅ Visão completa do fluxo financeiro

## 🎯 Benefícios

1. **Precisão**: Todos os cálculos centralizados no service
2. **Consistência**: Mesma lógica em todo o sistema
3. **Visibilidade**: Métricas financeiras completas
4. **Auditoria**: Rastreabilidade total dos valores
5. **Gestão**: Decisões baseadas em dados precisos

---

**Próximo Passo**: Implementar as alterações no controller e view.
