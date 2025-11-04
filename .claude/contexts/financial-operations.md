# Financial Operations Context

> **When to use**: Working with cachês, commissions, payments, currency conversion, or any financial calculations.

## 🎯 Critical Rules (Never Break)

1. **ALWAYS use `GigFinancialCalculatorService`** for calculations
2. **NEVER duplicate calculation logic**
3. **ALL monetary values normalize to BRL** for reports
4. **Decimal fields return STRINGS** (not floats)
5. **Test with mocked ExchangeRateService** (never real API)

## 💰 Financial Flow Overview

```
Client Payment → Agency → [Deductions] → Net to Artist
                      ↓
                Commissions → [Booker + Agency]
```

### Calculation Order

1. **Gross Fee (Cachê Bruto)** = Artist fee converted to BRL
2. **Agency Commission** = % of gross fee
3. **Booker Commission** = % of gross fee (separate)
4. **Reimbursable Costs** = Expenses artist must pay back
5. **Net to Artist** = Gross - Agency Commission - Reimbursable Costs

## 🔧 Core Service: GigFinancialCalculatorService

**Location**: `app/Services/GigFinancialCalculatorService.php`

### Essential Methods

```php
// 1. Gross fee in BRL (handles currency conversion)
calculateGrossFeeInBrl(Gig $gig): float

// 2. Agency commission in BRL
calculateAgencyCommissionBrl(Gig $gig): float

// 3. Net payout to artist in BRL
calculateArtistNetPayout(Gig $gig): float

// 4. Booker commission in BRL
calculateBookerCommissionBrl(Gig $gig): float

// 5. Total reimbursable costs
calculateTotalReimbursableCosts(Gig $gig): float
```

### Usage Pattern

```php
// In Controller
public function __construct(
    private GigFinancialCalculatorService $calculator
) {}

public function show(Gig $gig)
{
    $financials = [
        'gross_fee' => $this->calculator->calculateGrossFeeInBrl($gig),
        'agency_commission' => $this->calculator->calculateAgencyCommissionBrl($gig),
        'net_to_artist' => $this->calculator->calculateArtistNetPayout($gig),
        'booker_commission' => $this->calculator->calculateBookerCommissionBrl($gig),
    ];

    return view('gigs.show', compact('gig', 'financials'));
}
```

## 💱 Currency Handling

### Supported Currencies

- BRL (Brazilian Real) - Base currency
- USD (US Dollar)
- EUR (Euro)
- GBP (British Pound)

### ExchangeRateService

**Location**: `app/Services/ExchangeRateService.php`

```php
// Convert to BRL
convertToBRL(float $amount, string $currency): float

// Example
$exchangeService = app(ExchangeRateService::class);
$brlAmount = $exchangeService->convertToBRL(1000, 'USD');
```

### Currency Conversion Rules

1. **API First**: Uses Banco Central do Brasil API
2. **Fallback**: Uses config rates if API fails
3. **BRL → BRL**: No conversion, returns original
4. **Warnings**: Currency conversions include warnings in UI

### Configuration

**File**: `config/exchange_rates.php`

```php
return [
    'default_rates' => [
        'USD' => env('EXCHANGE_RATE_USD', 5.50),
        'EUR' => env('EXCHANGE_RATE_EUR', 6.00),
        'GBP' => env('EXCHANGE_RATE_GBP', 7.00),
    ],
    'cache_ttl' => 3600, // 1 hour
];
```

## 💸 Payment Validation Rules

### CommissionPaymentValidationService

**Location**: `app/Services/CommissionPaymentValidationService.php`

#### Rule: Past Events Only

**Cannot pay commissions/artist for future events** UNLESS authorized exception exists.

```php
validateBookerCommissionPayment(Gig $gig, bool $allowExceptions): array

// Returns
[
    'allowed' => bool,
    'reason' => string,
    'is_exception' => bool,
]
```

#### Exception Keywords

Detected in Settlement notes:
- "exceção"
- "antecipado"
- "autorizado"

#### Usage Example

```php
$validation = $this->validationService->validateBookerCommissionPayment($gig, true);

if (!$validation['allowed']) {
    throw new ValidationException($validation['reason']);
}

// Proceed with payment
```

## 📊 Financial Projections

### FinancialProjectionService

**Location**: `app/Services/FinancialProjectionService.php`

**Purpose**: Cash flow projections based on scheduled gigs

```php
// Set projection period
setPeriod(string $period): void
// Options: '30', '60', '90', 'next_semester', 'next_year'

// Get receivables from clients
getAccountsReceivable(): float

// Get payables to artists
getAccountsPayableArtists(): float

// Get payables to bookers (commissions)
getAccountsPayableBookers(): float

// Get projected net cash flow
getProjectedCashFlow(): float
```

#### Example

```php
$projection = app(FinancialProjectionService::class);
$projection->setPeriod('90'); // Next 90 days

$cashFlow = [
    'receivable' => $projection->getAccountsReceivable(),
    'payable_artists' => $projection->getAccountsPayableArtists(),
    'payable_bookers' => $projection->getAccountsPayableBookers(),
    'net_flow' => $projection->getProjectedCashFlow(),
];
```

## 🧮 Audit and Integrity

### AuditService

**Location**: `app/Services/AuditService.php`

**Purpose**: Detect financial divergences between contract and reality

```php
// Single gig audit
calculateGigAuditData(Gig $gig): array

// Bulk audit
calculateBulkAuditData(Collection $gigs): array

// Data integrity validation
validateGigIntegrity(Gig $gig): array
```

#### Divergence Classification

| Divergence % | Priority | Color |
|--------------|----------|-------|
| > 5% | High | Red |
| 1-5% | Medium | Yellow |
| < 1% | Low | Green |

#### Example Output

```php
[
    'gig_id' => 123,
    'contracted_value_brl' => 10000.00,
    'total_paid_brl' => 9500.00,
    'divergence_brl' => 500.00,
    'divergence_percentage' => 5.0,
    'priority' => 'medium',
    'issues' => [
        'currency_inconsistency' => false,
        'missing_payments' => true,
    ]
]
```

## 📝 Domain Models Reference

### Gig Model

**Monetary Fields** (all use `decimal:2` cast → **return STRINGS**):
- `cache_value` - Artist fee amount
- `cache_currency` - Currency (BRL/USD/EUR/GBP)
- `agency_commission_percentage` - Agency commission %
- `technical_rider_value` - Technical costs

**Relationships**:
- `hasMany(Payment::class)` - Client payments
- `hasMany(GigCost::class)` - Event expenses
- `hasOne(Settlement::class)` - Artist/Booker settlements
- `belongsTo(Artist::class)`
- `belongsTo(Booker::class)`

### Payment Model

**Fields**:
- `due_value` (decimal) - Amount due
- `due_date` - Payment deadline
- `payment_confirmed` (boolean) - Paid?
- `confirmation_date` - When paid

### GigCost Model

**Fields**:
- `cost_value` (decimal) - Expense amount
- `is_reimbursable` (boolean) - Artist pays back?
- `cost_center_id` - Category

**Relationship**:
- `belongsTo(CostCenter::class)`

### Settlement Model

**Fields**:
- `artist_payment_value` (decimal)
- `booker_commission_value` (decimal)
- `payment_date`
- `notes` - Includes exception keywords

## ✅ Testing Financial Logic

### Decimal Assertion Pattern

```php
#[Test]
public function it_calculates_gross_fee()
{
    $gig = Gig::factory()->create([
        'cache_value' => 10000.00,
        'cache_currency' => 'BRL',
    ]);

    $result = $this->calculator->calculateGrossFeeInBrl($gig);

    // ✅ CORRECT
    $this->assertIsFloat($result);  // Service returns float
    $this->assertEquals(10000.00, $result);

    // For model attributes
    $this->assertIsString($gig->cache_value);  // Model cast returns string
    $this->assertEquals('10000.00', $gig->cache_value);
}
```

### Mock ExchangeRateService

```php
#[Test]
public function it_converts_usd_to_brl()
{
    // Mock service
    $mockExchange = Mockery::mock(ExchangeRateService::class);
    $mockExchange->shouldReceive('convertToBRL')
        ->with(1000, 'USD')
        ->andReturn(5500.00);

    App::shouldReceive('make')
        ->with(ExchangeRateService::class)
        ->andReturn($mockExchange);

    // Test code using mocked service
    $gig = Gig::factory()->create([
        'cache_value' => 1000,
        'cache_currency' => 'USD',
    ]);

    $calculator = new GigFinancialCalculatorService($mockExchange);
    $result = $calculator->calculateGrossFeeInBrl($gig);

    $this->assertEquals(5500.00, $result);
}
```

### Use Config for Simple Tests

```php
#[Test]
public function it_uses_config_exchange_rate()
{
    Config::set('exchange_rates.default_rates.USD', 5.50);

    $gig = Gig::factory()->create([
        'cache_value' => 1000,
        'cache_currency' => 'USD',
    ]);

    // Uses config fallback
    $result = $this->calculator->calculateGrossFeeInBrl($gig);

    $this->assertEquals(5500.00, $result);
}
```

## 🚨 Common Pitfalls

### 1. Calculating Directly in Controller

**❌ WRONG:**
```php
$grossFee = $gig->cache_value * 1.0; // What about currency?
$commission = $grossFee * 0.20; // What about custom rates?
```

**✅ CORRECT:**
```php
$grossFee = $this->calculator->calculateGrossFeeInBrl($gig);
$commission = $this->calculator->calculateAgencyCommissionBrl($gig);
```

### 2. Forgetting Currency Conversion

**❌ WRONG:**
```php
$totalRevenue = Gig::sum('cache_value'); // Mixed currencies!
```

**✅ CORRECT:**
```php
$gigs = Gig::all();
$totalRevenue = $gigs->sum(fn($gig) =>
    $this->calculator->calculateGrossFeeInBrl($gig)
);
```

### 3. Comparing Decimals as Floats

**❌ WRONG:**
```php
if ($payment->due_value == 500.00) { } // String == Float comparison
```

**✅ CORRECT:**
```php
if ((float) $payment->due_value == 500.00) { }
// Or
if ($payment->due_value === '500.00') { }
```

## 📖 Related Documentation

- Full Service API: `docs/SERVICES_API.md`
- Business Rules: `docs/ai_context/1_context.md`
- Architecture: `.claude/guides/02-architecture.md`
- Testing: `.claude/guides/04-testing.md`

---

**Last Updated**: 2025-10-27
