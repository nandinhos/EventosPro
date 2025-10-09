# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EventosPro is a specialized management system for artist agencies and bookers who manage artistic events (Gigs). It centralizes and automates event management, offering detailed control of fees, expenses, commissions, and comprehensive financial tracking from booking through final settlement.

## Development Environment

### **CRITICAL: Laravel Sail Usage**

This project uses Laravel Sail as the MANDATORY development environment. **ALL commands must be executed through Sail**.

```bash
# Correct command pattern
./vendor/bin/sail artisan test
./vendor/bin/sail composer install
./vendor/bin/sail npm run dev

# Create alias for convenience
alias sail='./vendor/bin/sail'
```

**NEVER run commands directly on the host** (`php artisan`, `composer`, `npm`) - they will fail or cause environment inconsistencies.

## Common Commands

### Testing
```bash
# Run all tests
./vendor/bin/sail test

# Run tests with coverage
./vendor/bin/sail test --coverage

# Run specific test file
./vendor/bin/sail test tests/Unit/Services/AuditServiceTest.php

# Run tests with filter
./vendor/bin/sail test --filter=testCalculateGigAuditData

# Run with minimum coverage threshold
./vendor/bin/sail test --coverage --min=80
```

### Database Operations
```bash
# Run migrations
./vendor/bin/sail artisan migrate

# Refresh database and seed
./vendor/bin/sail artisan migrate:fresh --seed

# Create new migration
./vendor/bin/sail artisan make:migration create_table_name

# Interactive console (Tinker)
./vendor/bin/sail artisan tinker
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/sail bin pint

# Run static analysis with PHPStan
./vendor/bin/sail bin phpstan analyse

# Clear application cache
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

### Development Workflow
```bash
# Start environment
./vendor/bin/sail up -d

# Stop environment
./vendor/bin/sail down

# View logs
./vendor/bin/sail artisan pail

# Access container shell
./vendor/bin/sail shell
```

## Architecture Overview

### Service Layer Pattern

EventosPro implements a robust service layer that encapsulates complex business logic. Services are injected via dependency injection and handle:

- **GigFinancialCalculatorService**: Core financial calculations for gigs (gross fees, commissions, currency conversions)
- **AuditService**: Financial auditing and divergence analysis
- **FinancialReportService**: Report generation with complex filtering
- **DashboardService**: KPI aggregation and metrics
- **ExchangeRateService**: Currency conversion with Banco Central do Brasil API integration
- **ArtistFinancialsService** / **BookerFinancialsService**: Entity-specific financial metrics
- **UserManagementService**: User/Booker management with atomic transactions
- **FinancialProjectionService**: Cash flow projections and accounts receivable/payable

**Key principle**: Controllers should delegate complex logic to services, not perform calculations directly.

### Domain Models

Core entities with critical relationships:

- **Gig** (central entity): Represents artistic events
  - `belongsTo`: Artist, Booker
  - `hasMany`: Payment, GigCost
  - `hasOne`: Settlement
  - Uses SoftDeletes for historical preservation

- **Payment**: Client payment installments with currency conversion
- **GigCost**: Event expenses categorized by CostCenter
- **Settlement**: Financial settlements with artists/bookers
- **Artist** / **Booker**: Entity management with financial tracking

### Observer Pattern

The system uses Observers for automatic reactions to model events:

- **GigObserver**: Lifecycle events for Gigs
- **GigCostObserver**: Automatic recalculations when costs change

These are registered in `app/Providers/EventServiceProvider.php`.

### Currency Handling

All financial calculations normalize to BRL:

- Multiple currency support (USD, EUR, GBP)
- ExchangeRateService integrates with Banco Central API
- Fallback to configured default rates when API fails
- Values stored in original currency but displayed/calculated in BRL

**Important**: Laravel `decimal` casts return strings, not floats. In tests, use `assertIsString()` and compare string values.

## Testing Guidelines

### Test Coverage Requirements

- **Minimum general coverage**: 80%
- **Critical services**: 95% (AuditService, ExchangeRateService, ArtistFinancialsService)
- Coverage reports generated in `coverage-report/` directory

### Test Execution

**ALWAYS use Sail** for test execution:
```bash
./vendor/bin/sail test
```

### Critical Testing Rules

1. **Decimal Fields Return Strings**:
   ```php
   // ❌ WRONG
   $this->assertIsFloat($payment->due_value);

   // ✅ CORRECT
   $this->assertIsString($payment->due_value);
   $this->assertEquals('500.00', $payment->due_value);
   ```

2. **Service Mocking**:
   ```php
   App::shouldReceive('make')
       ->with(ExchangeRateService::class)
       ->andReturn($mockService);
   ```

3. **Configuration in Tests**:
   ```php
   Config::set('exchange_rates.default_rates.USD', 5.00);
   ```

4. **Use RefreshDatabase**: All feature/integration tests should use the trait to ensure clean state.

## Code Conventions

### Configuration Structure

Use hierarchical config files instead of cramming everything into `config/app.php`:

```php
// ✅ CORRECT
config('exchange_rates.default_rates.USD')
config('services.bcb_api.endpoint')

// ❌ AVOID
config('app.default_exchange_rates.USD')
```

### Service Injection

Always use constructor injection for services in controllers:

```php
class GigController extends Controller
{
    public function __construct(
        private GigFinancialCalculatorService $calculator,
        private AuditService $auditService
    ) {}
}
```

### Query Optimization

**Always eager load relationships** to prevent N+1 queries:

```php
// ✅ CORRECT
$gigs = Gig::with(['artist', 'booker', 'payments'])->get();

// ❌ AVOID
$gigs = Gig::all(); // Causes N+1 when accessing relationships
```

### Validation

Use Form Request classes for complex validation:

```php
// In controller
public function store(StoreGigRequest $request)
{
    $gig = Gig::create($request->validated());
    // ...
}
```

### Soft Deletes

Important models use SoftDeletes to preserve history. When querying, be aware:
- Default queries exclude soft-deleted records
- Use `withTrashed()` to include soft-deleted records
- Use `onlyTrashed()` to get only soft-deleted records

## Financial Calculation Flow

Understanding the financial flow is critical for working with this system:

1. **Gross Fee (Cachê Bruto)**: Artist fee converted to BRL
2. **Agency Commission**: Calculated from gross fee
3. **Net to Artist**: Gross fee minus deductions (agency commission, reimbursables)
4. **Booker Commission**: Calculated from gross fee at booker-specific rate
5. **Expenses**: Tracked separately, categorized by CostCenter

**All calculations go through GigFinancialCalculatorService** for consistency.

## Important Business Rules

### Payment Validation

- **Artist payments**: Only allowed after gig date (events already performed)
- **Booker commissions**: Only allowed after gig date OR with explicit exception
- Exceptions tracked via Settlement notes with keywords: "exceção", "antecipado", "autorizado"

See `CommissionPaymentValidationService` for validation logic.

### Audit and Integrity

The system performs financial integrity checks via `AuditService`:
- Detects divergences between contract values and payments
- Classifies divergences (low/medium/high)
- Validates currency consistency
- Generates consolidated audit reports

## Documentation References

Comprehensive documentation available in `docs/`:
- `TESTING.md`: Complete testing guide with examples
- `SERVICES_API.md`: Detailed API documentation for all services
- `LARAVEL_SAIL_COMMANDS.md`: Full Sail command reference
- `ai_context/`: Architecture, context, and business rules

## Common Patterns

### Creating a New Service

1. Create class in `app/Services/`
2. Inject dependencies via constructor
3. Register in `AppServiceProvider` if needed
4. Create corresponding test in `tests/Unit/Services/`
5. Document in `SERVICES_API.md`

### Adding a New Route

Routes are organized in `routes/web.php`:
- Protected routes inside `middleware('auth')` group
- Use resource routes when following CRUD pattern
- Nested routes under relevant resources
- Follow RESTful naming conventions

### Working with Financial Data

```php
// Get financial data for a gig
$calculator = app(GigFinancialCalculatorService::class);
$grossFee = $calculator->calculateGrossCacheBrl($gig);
$netFee = $calculator->calculateArtistNetPayoutBrl($gig);
$commission = $calculator->calculateAgencyCommissionBrl($gig);
```

## PHPUnit Configuration

Tests use MySQL (not SQLite) to match production environment:
- Database: `testing` (auto-created by Sail)
- Configuration in `phpunit.xml`
- Uses same database engine as production for accuracy

## Tech Stack Summary

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js, Chart.js
- **Database**: MySQL 8.0+
- **Dev Environment**: Laravel Sail (Docker)
- **Testing**: PHPUnit with 80% minimum coverage
- **Code Quality**: Laravel Pint (PSR-12), PHPStan
- **Key Packages**: Filament (admin), Spatie Permissions, DomPDF, Laravel Excel

## Running a Single Test

```bash
# Specific test method
./vendor/bin/sail test --filter=test_method_name

# Specific test class
./vendor/bin/sail test tests/Unit/Services/AuditServiceTest.php
```

## Troubleshooting

### Tests Failing in CI
```bash
# Run locally first
./vendor/bin/sail test --env=testing

# Clear caches
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
```

### Database Issues
```bash
# Recreate test database
./vendor/bin/sail artisan migrate:fresh --env=testing
./vendor/bin/sail artisan db:seed --env=testing
```

### Coverage Issues
```bash
# Generate detailed HTML report
./vendor/bin/sail test --coverage --coverage-html=coverage-report

# View in browser
open coverage-report/index.html
```
