# GigFinancialCalculatorService - Documentação Detalhada

## 📋 Visão Geral

O `GigFinancialCalculatorService` é o service central para todos os cálculos financeiros relacionados a gigs no sistema EventosPro. Ele implementa a lógica complexa de cálculo de cachês, comissões e despesas.

## 🏗️ Arquitetura

### Fluxo de Cálculo

```
Valor do Contrato (BRL)
    ↓
- Despesas Confirmadas
    ↓
= Cachê Bruto
    ↓
- Comissão Bruta da Agência
    ↓
= Cachê Líquido do Artista
```

### Hierarquia de Comissões

```
Comissão Bruta da Agência
    ↓
- Comissão do Booker
    ↓
= Comissão Líquida da Agência
```

## 📊 Métodos Principais

### 💰 Cálculos de Cachê

#### `calculateGrossCashBrl(Gig $gig): float`

**Descrição**: Calcula o "Cachê Bruto" da Gig em BRL.

**Fórmula**: `Valor do Contrato (BRL) - Total de Despesas Confirmadas (BRL)`

**Parâmetros**:
- `$gig` (Gig): Instância do modelo Gig

**Retorno**: Valor do cachê bruto em BRL (mínimo 0)

**Comportamento**:
- Carrega automaticamente as despesas relacionadas (`costs`)
- Usa o accessor `cacheValueBrlDetails` para conversão de moeda
- Considera apenas despesas confirmadas (`is_confirmed = true`)
- Retorna 0 se não conseguir converter o valor do contrato
- Registra logs de debug e warning

**Exemplo**:
```php
$calculator = app(GigFinancialCalculatorService::class);
$gig = Gig::find(1);
$grossCash = $calculator->calculateGrossCashBrl($gig);

echo "Cachê Bruto: R$ " . number_format($grossCash, 2);
```

#### `calculateArtistNetPayoutBrl(Gig $gig): float`

**Descrição**: Calcula o "Cachê Líquido do Artista" (antes do reembolso de despesas).

**Fórmula**: `Cachê Bruto - Comissão Bruta da Agência`

**Retorno**: Valor do cachê líquido em BRL (mínimo 0)

**Exemplo**:
```php
$netPayout = $calculator->calculateArtistNetPayoutBrl($gig);
echo "Cachê Líquido do Artista: R$ " . number_format($netPayout, 2);
```

### 🏢 Cálculos de Comissão da Agência

#### `calculateAgencyGrossCommissionBrl(Gig $gig): float`

**Descrição**: Calcula a "Comissão Bruta da Agência".

**Base de Cálculo**: Cachê Bruto (já com despesas deduzidas)

**Tipos de Comissão**:
1. **PERCENT**: Percentual sobre o cachê bruto
2. **FIXED**: Valor fixo
3. **Fallback**: Percentual padrão (20%) se tipo não especificado

**Campos Utilizados**:
- `agency_commission_type`: Tipo da comissão
- `agency_commission_rate`: Taxa percentual (padrão: 20%)
- `agency_commission_value`: Valor fixo

**Exemplo**:
```php
// Gig com comissão de 15%
$gig->agency_commission_type = 'PERCENT';
$gig->agency_commission_rate = 15.0;

$commission = $calculator->calculateAgencyGrossCommissionBrl($gig);
echo "Comissão da Agência: R$ " . number_format($commission, 2);
```

#### `calculateAgencyNetCommissionBrl(Gig $gig): float`

**Descrição**: Calcula a "Comissão Líquida da Agência".

**Fórmula**: `Comissão Bruta da Agência - Comissão do Booker`

### 🤝 Cálculos de Comissão do Booker

#### `calculateBookerCommissionBrl(Gig $gig): float`

**Descrição**: Calcula a "Comissão do Booker".

**Pré-requisitos**:
- Gig deve ter `booker_id` definido
- Retorna 0 se não houver booker

**Base de Cálculo**: Cachê Bruto

**Tipos de Comissão**:
1. **PERCENT**: Percentual sobre o cachê bruto
2. **FIXED**: Valor fixo
3. **Fallback**: Percentual se `booker_commission_rate` estiver definido

**Campos Utilizados**:
- `booker_commission_type`: Tipo da comissão
- `booker_commission_rate`: Taxa percentual (padrão: 5%)
- `booker_commission_value`: Valor fixo

**Exemplo**:
```php
// Gig com booker e comissão de 8%
$gig->booker_id = 1;
$gig->booker_commission_type = 'PERCENT';
$gig->booker_commission_rate = 8.0;

$bookerCommission = $calculator->calculateBookerCommissionBrl($gig);
echo "Comissão do Booker: R$ " . number_format($bookerCommission, 2);
```

### 💸 Cálculos de Despesas

#### `calculateTotalConfirmedExpensesBrl(Gig $gig): float`

**Descrição**: Calcula o total de todas as despesas confirmadas.

**Critério**: Apenas despesas com `is_confirmed = true`

**Retorno**: Soma total das despesas confirmadas em BRL

#### `calculateTotalPendingExpensesBrl(Gig $gig): float`

**Descrição**: Calcula o total de despesas pendentes de confirmação.

**Critério**: Apenas despesas com `is_confirmed = false`

### 💳 Cálculos de Pagamentos

#### `calculateTotalPaid(Gig $gig): float`

**Descrição**: Calcula o total já pago para o gig.

**Critério**: Soma pagamentos confirmados convertidos para BRL

#### `calculateTotalPending(Gig $gig): float`

**Descrição**: Calcula o total pendente de pagamento.

**Fórmula**: `Cachê Líquido do Artista - Total Pago`

## 🔄 Fluxo Completo de Cálculo

```php
$calculator = app(GigFinancialCalculatorService::class);
$gig = Gig::with(['costs', 'payments'])->find(1);

// 1. Cálculos base
$contractValue = $gig->cacheValueBrlDetails['value'];
$totalExpenses = $calculator->calculateTotalConfirmedExpensesBrl($gig);
$grossCash = $calculator->calculateGrossCashBrl($gig);

// 2. Comissões
$agencyGrossCommission = $calculator->calculateAgencyGrossCommissionBrl($gig);
$bookerCommission = $calculator->calculateBookerCommissionBrl($gig);
$agencyNetCommission = $calculator->calculateAgencyNetCommissionBrl($gig);

// 3. Cachê do artista
$artistNetPayout = $calculator->calculateArtistNetPayoutBrl($gig);

// 4. Status de pagamento
$totalPaid = $calculator->calculateTotalPaid($gig);
$totalPending = $calculator->calculateTotalPending($gig);

// 5. Resumo financeiro
$summary = [
    'contract_value_brl' => $contractValue,
    'total_expenses_brl' => $totalExpenses,
    'gross_cash_brl' => $grossCash,
    'agency_gross_commission_brl' => $agencyGrossCommission,
    'booker_commission_brl' => $bookerCommission,
    'agency_net_commission_brl' => $agencyNetCommission,
    'artist_net_payout_brl' => $artistNetPayout,
    'total_paid_brl' => $totalPaid,
    'total_pending_brl' => $totalPending
];
```

## 🎯 Casos de Uso Comuns

### 1. Validação Financeira

```php
public function validateGigFinancials(Gig $gig): array
{
    $calculator = app(GigFinancialCalculatorService::class);
    
    $grossCash = $calculator->calculateGrossCashBrl($gig);
    $artistPayout = $calculator->calculateArtistNetPayoutBrl($gig);
    $totalPaid = $calculator->calculateTotalPaid($gig);
    
    $errors = [];
    
    if ($grossCash <= 0) {
        $errors[] = 'Cachê bruto deve ser positivo';
    }
    
    if ($totalPaid > $artistPayout) {
        $errors[] = 'Total pago excede cachê líquido do artista';
    }
    
    return [
        'is_valid' => empty($errors),
        'errors' => $errors
    ];
}
```

### 2. Relatório Financeiro

```php
public function generateFinancialReport(Gig $gig): array
{
    $calculator = app(GigFinancialCalculatorService::class);
    
    return [
        'gig_id' => $gig->id,
        'artist' => $gig->artist->name,
        'event' => $gig->event->name,
        'contract_value' => $gig->cache_value,
        'contract_currency' => $gig->cache_currency,
        'financials' => [
            'gross_cash_brl' => $calculator->calculateGrossCashBrl($gig),
            'agency_commission_brl' => $calculator->calculateAgencyGrossCommissionBrl($gig),
            'booker_commission_brl' => $calculator->calculateBookerCommissionBrl($gig),
            'artist_net_payout_brl' => $calculator->calculateArtistNetPayoutBrl($gig),
            'total_expenses_brl' => $calculator->calculateTotalConfirmedExpensesBrl($gig)
        ],
        'payment_status' => [
            'total_paid_brl' => $calculator->calculateTotalPaid($gig),
            'total_pending_brl' => $calculator->calculateTotalPending($gig)
        ]
    ];
}
```

### 3. Simulação de Cenários

```php
public function simulateCommissionChange(Gig $gig, float $newRate): array
{
    $calculator = app(GigFinancialCalculatorService::class);
    
    // Estado atual
    $currentCommission = $calculator->calculateAgencyGrossCommissionBrl($gig);
    $currentArtistPayout = $calculator->calculateArtistNetPayoutBrl($gig);
    
    // Simular mudança
    $originalRate = $gig->agency_commission_rate;
    $gig->agency_commission_rate = $newRate;
    
    $newCommission = $calculator->calculateAgencyGrossCommissionBrl($gig);
    $newArtistPayout = $calculator->calculateArtistNetPayoutBrl($gig);
    
    // Restaurar valor original
    $gig->agency_commission_rate = $originalRate;
    
    return [
        'current' => [
            'commission_brl' => $currentCommission,
            'artist_payout_brl' => $currentArtistPayout
        ],
        'simulated' => [
            'commission_brl' => $newCommission,
            'artist_payout_brl' => $newArtistPayout
        ],
        'difference' => [
            'commission_brl' => $newCommission - $currentCommission,
            'artist_payout_brl' => $newArtistPayout - $currentArtistPayout
        ]
    ];
}
```

## ⚠️ Considerações Importantes

### Conversão de Moedas
- Todos os cálculos são feitos em BRL
- Usa o accessor `cacheValueBrlDetails` para conversão
- Retorna 0 se conversão falhar

### Tratamento de Erros
- Valores negativos são convertidos para 0
- Logs de warning para problemas de conversão
- Logs de debug para rastreamento de cálculos

### Performance
- Carrega relacionamentos automaticamente quando necessário
- Use `with()` para otimizar queries em operações em lote

### Precisão
- Todos os retornos são convertidos para `float`
- Use `number_format()` para exibição formatada

## 🧪 Testes

O service possui testes abrangentes em `tests/Unit/Services/GigFinancialCalculatorServiceTest.php` cobrindo:

- Cálculos com diferentes tipos de comissão
- Conversão de moedas
- Tratamento de valores negativos
- Cenários com e sem booker
- Validação de despesas
- Casos extremos e edge cases

### Executando Testes

**⚠️ IMPORTANTE**: Use Laravel Sail para executar todos os comandos:

```bash
# Testar o GigFinancialCalculatorService
sail artisan test tests/Unit/Services/GigFinancialCalculatorServiceTest.php

# Testar com cobertura
sail artisan test tests/Unit/Services/GigFinancialCalculatorServiceTest.php --coverage

# Testar métodos específicos
sail artisan test --filter=testCalculateGrossCashBrl

# Debug via Tinker
sail artisan tinker
# > $gig = App\Models\Gig::first()
# > app(App\Services\GigFinancialCalculatorService::class)->calculateGrossCashBrl($gig)
```

## 📞 Suporte

Para dúvidas sobre cálculos financeiros:
1. Consulte esta documentação
2. Verifique os testes unitários
3. Analise os logs de debug
4. Entre em contato com a equipe financeira