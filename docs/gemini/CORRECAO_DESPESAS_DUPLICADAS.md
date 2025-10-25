# Correção: Duplicidade no Card "Total Despesas"

**Data:** 25/10/2025
**Tipo:** Bug Fix
**Prioridade:** Alta

## 🐛 Problema Identificado

O card "**Total Despesas**" estava exibindo o mesmo valor que o card "**Custo Operacional Mensal**", indicando uma duplicidade de dados.

### Análise do Problema

1. **Linha 119 do Controller (antes da correção):**
   ```php
   'total_payable_expenses' => $projectedExpenses['total_monthly'], // Custos fixos mensais
   ```

2. **Linha 193-194 da View (antes da correção):**
   ```blade
   title="Custo Operacional Mensal"
   :value="'R$ ' . number_format($global_metrics['total_payable_expenses'] ?? 0, 2, ',', '.')"
   ```

**Diagnóstico:** Ambos os cards estavam usando `$global_metrics['total_payable_expenses']`, que estava sendo populado com dados de `AgencyFixedCost` (custos operacionais fixos).

---

## ✅ Solução Implementada

### 1. **Criação de Novo Método no Controller**

Adicionado o método `calculateTotalGigExpenses()` em `FinancialProjectionController.php`:

```php
/**
 * Calcula o total de despesas de eventos (GigCost).
 * Retorna despesas pendentes e confirmadas de todos os eventos.
 */
private function calculateTotalGigExpenses(): array
{
    // Despesas pendentes (não confirmadas)
    $pendingExpenses = \App\Models\GigCost::query()
        ->where('is_confirmed', false)
        ->whereHas('gig') // Garante que apenas custos de gigs não-deletados sejam incluídos
        ->get();

    // Despesas confirmadas
    $confirmedExpenses = \App\Models\GigCost::query()
        ->where('is_confirmed', true)
        ->whereHas('gig')
        ->get();

    $totalPending = $pendingExpenses->sum(function ($cost) {
        return $cost->value_brl;
    });

    $totalConfirmed = $confirmedExpenses->sum(function ($cost) {
        return $cost->value_brl;
    });

    return [
        'total_expenses' => $totalPending + $totalConfirmed,
        'total_pending' => $totalPending,
        'total_confirmed' => $totalConfirmed,
        'pending_count' => $pendingExpenses->count(),
        'confirmed_count' => $confirmedExpenses->count(),
    ];
}
```

### 2. **Atualização do Array `$globalMetrics`**

**Antes:**
```php
$globalMetrics = [
    'total_payable_expenses' => $projectedExpenses['total_monthly'], // ERRADO: custos fixos
    'operational_cost_count' => $projectedExpenses['expense_count'],
    // ...
];
```

**Depois:**
```php
$gigExpenses = $this->calculateTotalGigExpenses(); // Novo método

$globalMetrics = [
    'total_payable_expenses' => $gigExpenses['total_expenses'], // CORRETO: despesas de eventos (GigCost)
    'operational_cost_count' => $projectedExpenses['expense_count'],
    'operational_cost_monthly' => $projectedExpenses['total_monthly'], // Novo: custos fixos separados
    // ...
];
```

### 3. **Atualização da View**

**Antes:**
```blade
{{-- Card 1: Total Despesas --}}
<x-metrics.value-card
    title="Total Despesas"
    :value="'R$ ' . number_format($global_metrics['total_payable_expenses'], 2, ',', '.')"
    subtitle="Custos operacionais"  {{-- ERRADO --}}
    color="orange" />

{{-- Card 2: Custo Operacional Mensal --}}
<x-metrics.value-card
    title="Custo Operacional Mensal"
    :value="'R$ ' . number_format($global_metrics['total_payable_expenses'] ?? 0, 2, ',', '.')"  {{-- DUPLICADO --}}
    :count="$global_metrics['operational_cost_count'] ?? 0"
    subtitle="itens de custo"
    color="gray" />
```

**Depois:**
```blade
{{-- Card 1: Total Despesas de Eventos (GigCost) --}}
<x-metrics.value-card
    title="Total Despesas de Eventos"
    :value="'R$ ' . number_format($global_metrics['total_payable_expenses'], 2, ',', '.')"
    subtitle="Despesas relacionadas aos eventos"  {{-- CORRETO --}}
    color="orange" />

{{-- Card 2: Custo Operacional Mensal (AgencyFixedCost) --}}
<x-metrics.value-card
    title="Custo Operacional Mensal"
    :value="'R$ ' . number_format($global_metrics['operational_cost_monthly'] ?? 0, 2, ',', '.')"  {{-- CORRETO --}}
    :count="$global_metrics['operational_cost_count'] ?? 0"
    subtitle="itens de custo fixo"
    color="gray" />
```

---

## 📊 Diferença Entre os Dois Cards

| Card | Fonte de Dados | Descrição | Tabela do Banco |
|------|---------------|-----------|-----------------|
| **Total Despesas de Eventos** | `GigCost` | Despesas relacionadas a eventos específicos (transporte, hotel, produção, etc.) | `gig_costs` |
| **Custo Operacional Mensal** | `AgencyFixedCost` | Custos fixos da agência (aluguel, salários, internet, etc.) | `agency_fixed_costs` |

### Exemplos Práticos

**Total Despesas de Eventos (`GigCost`):**
- Transporte do artista: R$ 500
- Hotel: R$ 800
- Produção local: R$ 1.200
- **Total:** R$ 2.500

**Custo Operacional Mensal (`AgencyFixedCost`):**
- Aluguel do escritório: R$ 2.000
- Salário administrativo: R$ 5.000
- Internet e telefone: R$ 300
- **Total:** R$ 7.300

---

## 🔍 Lógica do Cálculo de `GigCost`

O método `calculateTotalGigExpenses()` soma:

1. **Despesas Pendentes** (`is_confirmed = false`): Ainda não foram pagas/confirmadas
2. **Despesas Confirmadas** (`is_confirmed = true`): Já foram pagas/confirmadas

**Total = Pendentes + Confirmadas**

Além disso, o método utiliza o accessor `value_brl` do modelo `GigCost`, que converte automaticamente valores em moedas estrangeiras para BRL usando a taxa de câmbio da data da despesa.

---

## 📁 Arquivos Modificados

### 1. **Controller**
- **Arquivo:** `/app/Http/Controllers/FinancialProjectionController.php`
- **Mudanças:**
  - Adicionado método `calculateTotalGigExpenses()` (linhas 251-284)
  - Atualizado `$globalMetrics` para incluir `operational_cost_monthly` (linha 122)
  - Modificado `total_payable_expenses` para usar dados de `GigCost` (linha 120)

### 2. **View**
- **Arquivo:** `/resources/views/projections/dashboard.blade.php`
- **Mudanças:**
  - Card "Total Despesas" renomeado para "**Total Despesas de Eventos**" (linha 185)
  - Subtitle alterado de "Custos operacionais" para "**Despesas relacionadas aos eventos**" (linha 187)
  - Card "Custo Operacional Mensal" agora usa `$global_metrics['operational_cost_monthly']` (linha 194)
  - Subtitle alterado de "itens de custo" para "**itens de custo fixo**" (linha 196)

---

## 🧪 Como Testar

1. **Acessar o módulo de Projeções:**
   ```
   http://localhost/projections
   ```

2. **Verificar os valores dos cards:**
   - **Total Despesas de Eventos** deve mostrar a soma de todos os `GigCost` (confirmados e pendentes)
   - **Custo Operacional Mensal** deve mostrar a soma de todos os `AgencyFixedCost` ativos

3. **Validar com SQL (opcional):**
   ```sql
   -- Total Despesas de Eventos
   SELECT SUM(value) FROM gig_costs WHERE deleted_at IS NULL;

   -- Custo Operacional Mensal
   SELECT SUM(monthly_value) FROM agency_fixed_costs WHERE is_active = 1;
   ```

---

## ✅ Status

- ✅ Problema identificado
- ✅ Método `calculateTotalGigExpenses()` criado
- ✅ Controller atualizado
- ✅ View corrigida
- ✅ Documentação criada
- ⏳ Testes manuais pendentes (pelo usuário)

---

## 🎯 Próximos Passos (Sugestões)

1. **Adicionar detalhamento de despesas:** Criar uma tabela expansível mostrando as despesas por evento (similar às tabelas de recebíveis)
2. **Filtro por período:** Permitir filtrar despesas por data (eventos passados vs futuros)
3. **Gráfico de despesas:** Visualização de despesas por categoria/centro de custo
4. **Alertas:** Notificar quando despesas pendentes ultrapassarem certo valor

---

**Autor:** Claude Code (Anthropic)
**Revisão:** Pendente
**Aprovação:** Pendente
