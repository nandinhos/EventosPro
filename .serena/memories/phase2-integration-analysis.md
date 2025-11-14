# Fase 2: Análise de Integração - AgencyFixedCosts

**Data**: 2025-11-14

## 🎯 Objetivo
Integrar campos `due_date` e `cost_type` com Services financeiros.

## 📊 Achados Críticos

### 1. DreProjectionService ✅ Parcialmente OK
- ✅ Usa AgencyFixedCost corretamente
- ✅ Usa `reference_month` (competência) - CORRETO
- ❌ NÃO diferencia GIG vs AGENCY

### 2. CashFlowProjectionService ❌ CRÍTICO
**4 Bugs Identificados:**

1. **Bug #1**: Linha 520 - campo `payment_day` NÃO EXISTE
2. **Bug #2**: `calculateProjectedExpenses()` não é usado
3. **Bug #3**: NÃO usa `due_date` (deveria usar caixa!)
4. **Bug #4**: NÃO inclui agency_fixed_costs em outflows

## 🔧 Plano de Correção

### Correção 1: DreProjectionService
- Adicionar segregação GIG vs AGENCY

### Correção 2: CashFlowProjectionService
- Remover `payment_day`
- Integrar custos em `calculateMonthlyOutflows()`
- Usar `due_date` para regime de caixa
