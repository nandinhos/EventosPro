# Documentação das APIs dos Services - EventosPro

## 📋 Visão Geral

Este documento descreve as APIs dos services principais do sistema EventosPro, incluindo métodos, parâmetros, retornos e exemplos de uso.

## 🏗️ Arquitetura dos Services

Os services seguem o padrão de injeção de dependência e são registrados no container do Laravel. Cada service tem responsabilidades específicas:

- **AuditService**: Auditoria e análise financeira de gigs
- **ArtistFinancialsService**: Métricas financeiras de artistas
- **ExchangeRateService**: Conversão de moedas e taxas de câmbio
- **GigFinancialCalculatorService**: Cálculos financeiros de gigs
- **UserManagementService**: Gerenciamento de usuários
- **DashboardService**: Dados para dashboard
- **FinancialReportService**: Relatórios financeiros
- **FinancialProjectionService**: Projeções financeiras
- **BookerFinancialsService**: Métricas financeiras de bookers

---

## 🔍 AuditService

### Descrição
Service responsável por auditoria e análise financeira de gigs, incluindo cálculos de divergências, validações de integridade e geração de relatórios consolidados.

### Dependências
- `GigFinancialCalculatorService`

### Métodos Principais

#### `calculateGigAuditData(Gig $gig): array`

**Descrição**: Calcula dados de auditoria para um gig específico.

**Parâmetros**:
- `$gig` (Gig): Instância do modelo Gig

**Retorno**:
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

**Exemplo de Uso**:
```php
$auditService = app(AuditService::class);
$gig = Gig::find(1);
$auditData = $auditService->calculateGigAuditData($gig);

if ($auditData['has_divergence']) {
    Log::warning('Divergência encontrada', $auditData);
}
```

#### `calculateBulkAuditData(Collection $gigs): array`

**Descrição**: Calcula dados de auditoria para múltiplos gigs.

**Parâmetros**:
- `$gigs` (Collection): Coleção de gigs

**Retorno**:
```php
[
    'total_gigs' => int,
    'gigs_with_divergence' => int,
    'total_divergence_amount_brl' => float,
    'average_divergence_percentage' => float,
    'gigs_data' => array // Array de dados individuais
]
```

#### `validateGigIntegrity(Gig $gig): array`

**Descrição**: Valida a integridade dos dados financeiros de um gig.

**Retorno**:
```php
[
    'is_valid' => bool,
    'errors' => array,
    'warnings' => array
]
```

#### `generateConsolidatedReport(Collection $gigs): string`

**Descrição**: Gera relatório consolidado de auditoria.

**Retorno**: String com relatório formatado

---

## 💰 ArtistFinancialsService

### Descrição
Service para cálculo de métricas financeiras de artistas, incluindo cachês recebidos e pendentes.

### Dependências
- `GigFinancialCalculatorService`

### Métodos Principais

#### `getFinancialMetrics(Artist $artist, ?Collection $gigs = null): array`

**Descrição**: Calcula métricas financeiras de um artista.

**Parâmetros**:
- `$artist` (Artist): Instância do modelo Artist
- `$gigs` (Collection, opcional): Coleção de gigs pré-filtrada

**Retorno**:
```php
[
    'total_gigs' => int,
    'cache_received_brl' => float,
    'cache_pending_brl' => float
]
```

**Exemplo de Uso**:
```php
$financialsService = app(ArtistFinancialsService::class);
$artist = Artist::find(1);
$metrics = $financialsService->getFinancialMetrics($artist);

echo "Total recebido: R$ " . number_format($metrics['cache_received_brl'], 2);
```

---

## 💱 ExchangeRateService

### Descrição
Service para conversão de moedas e obtenção de taxas de câmbio, com integração à API do Banco Central do Brasil.

### Métodos Principais

#### `getExchangeRate(string $currency): float`

**Descrição**: Obtém taxa de câmbio para uma moeda específica.

**Parâmetros**:
- `$currency` (string): Código da moeda (USD, EUR, etc.)

**Retorno**: Taxa de câmbio em relação ao BRL

**Exemplo de Uso**:
```php
$exchangeService = app(ExchangeRateService::class);
$usdRate = $exchangeService->getExchangeRate('USD');
echo "1 USD = R$ " . number_format($usdRate, 4);
```

#### `convertToBRL(float $amount, string $currency): float`

**Descrição**: Converte valor para BRL.

**Parâmetros**:
- `$amount` (float): Valor a ser convertido
- `$currency` (string): Moeda de origem

**Retorno**: Valor convertido em BRL

#### `getMultipleRates(array $currencies): array`

**Descrição**: Obtém taxas para múltiplas moedas.

**Retorno**:
```php
[
    'USD' => 5.25,
    'EUR' => 6.10,
    // ...
]
```

#### `isSupportedCurrency(string $currency): bool`

**Descrição**: Verifica se uma moeda é suportada.

---

## 🧮 GigFinancialCalculatorService

### Descrição
Service para cálculos financeiros específicos de gigs, incluindo valores líquidos e conversões.

### Métodos Principais

#### `calculateArtistNetPayout(Gig $gig): float`

**Descrição**: Calcula valor líquido a ser pago ao artista.

**Retorno**: Valor em BRL

#### `calculateTotalPaid(Gig $gig): float`

**Descrição**: Calcula total já pago para o gig.

#### `calculateTotalPending(Gig $gig): float`

**Descrição**: Calcula total pendente de pagamento.

---

## 👥 UserManagementService

### Descrição
Service para gerenciamento de usuários, incluindo criação, atualização e controle de permissões.

### Métodos Principais

#### `createUser(array $data): User`

**Descrição**: Cria novo usuário.

**Parâmetros**:
```php
[
    'name' => string,
    'email' => string,
    'password' => string,
    'role' => string // opcional
]
```

#### `updateUser(User $user, array $data): User`

**Descrição**: Atualiza dados do usuário.

#### `assignRole(User $user, string $role): bool`

**Descrição**: Atribui papel ao usuário.

---

## 📊 DashboardService

### Descrição
Service para agregação de dados do dashboard principal.

### Métodos Principais

#### `getDashboardData(): array`

**Descrição**: Retorna dados consolidados para o dashboard.

**Retorno**:
```php
[
    'total_gigs' => int,
    'total_revenue_brl' => float,
    'pending_payments_brl' => float,
    'active_artists' => int,
    'recent_activities' => array,
    'monthly_stats' => array
]
```

---

## 📈 FinancialReportService

### Descrição
Service para geração de relatórios financeiros detalhados.

### Métodos Principais

#### `generateMonthlyReport(int $year, int $month): array`

**Descrição**: Gera relatório mensal.

#### `generateArtistReport(Artist $artist, ?Carbon $startDate = null, ?Carbon $endDate = null): array`

**Descrição**: Gera relatório específico de artista.

---

## 🔮 FinancialProjectionService

### Descrição
Service para projeções financeiras e análises preditivas.

### Métodos Principais

#### `projectMonthlyRevenue(int $months = 12): array`

**Descrição**: Projeta receita para os próximos meses.

#### `calculateTrends(): array`

**Descrição**: Calcula tendências financeiras.

---

## 🎯 BookerFinancialsService

### Descrição
Service para métricas financeiras de bookers.

### Métodos Principais

#### `getBookerMetrics(Booker $booker): array`

**Descrição**: Calcula métricas financeiras de um booker.

---

## 🛠️ Padrões de Uso

### Injeção de Dependência

```php
// Em controllers
class GigController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ArtistFinancialsService $financialsService
    ) {}
}

// Em outros services
class CustomService
{
    public function __construct(
        private ExchangeRateService $exchangeService
    ) {}
}
```

### Tratamento de Erros

```php
try {
    $rate = $exchangeService->getExchangeRate('USD');
} catch (ExchangeRateException $e) {
    Log::error('Erro ao obter taxa de câmbio', ['error' => $e->getMessage()]);
    $rate = $exchangeService->getDefaultRate('USD');
}
```

### Cache

Muitos services utilizam cache para otimizar performance:

```php
// ExchangeRateService usa cache de 1 hora
// DashboardService usa cache de 15 minutos
// FinancialReportService usa cache de 1 dia
```

## 🧪 Testes

Todos os services possuem testes unitários em `tests/Unit/Services/`:

- `AuditServiceTest.php`
- `ArtistFinancialsServiceTest.php`
- `ExchangeRateServiceTest.php`
- E outros...

### Executando Testes dos Services

**⚠️ IMPORTANTE**: Use Laravel Sail para executar todos os comandos:

```bash
# Testar todos os services
sail artisan test tests/Unit/Services/

# Testar service específico
sail artisan test tests/Unit/Services/ExchangeRateServiceTest.php

# Testar com cobertura
sail artisan test tests/Unit/Services/ --coverage

# Debug de services via Tinker
sail artisan tinker
# > app(App\Services\ExchangeRateService::class)->getExchangeRate('USD')
```

## 📞 Suporte

Para dúvidas sobre os services:
1. Consulte esta documentação
2. Verifique os testes unitários para exemplos
3. Consulte o código fonte dos services
4. Entre em contato com a equipe de desenvolvimento