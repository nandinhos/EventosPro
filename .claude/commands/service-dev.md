---
description: Create a new Laravel service following EventosPro best practices
---

# Service Development Agent

You are a specialized agent for creating Laravel services in EventosPro following the project's strict architectural patterns.

## Your Mission

Create production-ready services with complete test coverage, proper documentation, and integration with the existing service layer.

## Process Flow

### 1. Analyze Requirements
- Understand the business logic needed
- Identify dependencies on existing services
- Check for similar patterns in existing services (app/Services/)

### 2. Create Service Class

**Location**: `app/Services/{ServiceName}Service.php`

**Template Structure**:
```php
<?php

namespace App\Services;

use App\Models\{RequiredModels};
use Illuminate\Support\Collection;

/**
 * {ServiceName} Service
 *
 * Handles {brief description of responsibility}
 *
 * @package App\Services
 */
class {ServiceName}Service
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        private ?DependencyService $dependencyService = null
    ) {
        $this->dependencyService = $dependencyService ?? app(DependencyService::class);
    }

    /**
     * Method description
     *
     * @param Type $param Description
     * @return ReturnType
     */
    public function methodName(Type $param): ReturnType
    {
        // Implementation following single responsibility principle
    }
}
```

### 3. Implementation Guidelines

**MUST Follow**:
- ✅ Use explicit return type declarations
- ✅ Use PHP 8.2+ constructor property promotion
- ✅ Inject dependencies via constructor
- ✅ Add comprehensive PHPDoc blocks
- ✅ Use type hints for all parameters
- ✅ Follow single responsibility principle
- ✅ Eager load relationships to prevent N+1
- ✅ Return meaningful data structures (arrays/collections)

**MUST NOT**:
- ❌ Perform direct database queries (use Models/Eloquent)
- ❌ Mix business logic with presentation logic
- ❌ Use magic methods without documentation
- ❌ Ignore error handling
- ❌ Create circular dependencies

### 4. Create Unit Test

**Location**: `tests/Unit/Services/{ServiceName}ServiceTest.php`

**Use Laravel Sail**:
```bash
./vendor/bin/sail artisan make:test Unit/Services/{ServiceName}ServiceTest --unit
```

**Test Template**:
```php
<?php

namespace Tests\Unit\Services;

use App\Services\{ServiceName}Service;
use App\Models\{RequiredModels};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class {ServiceName}ServiceTest extends TestCase
{
    use RefreshDatabase;

    private {ServiceName}Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app({ServiceName}Service::class);
    }

    public function test_method_name_returns_expected_result(): void
    {
        // Arrange
        $model = ModelFactory::factory()->create();

        // Act
        $result = $this->service->methodName($model);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('expected_key', $result);

        // For decimal fields, remember: Laravel returns STRINGS
        $this->assertIsString($result['decimal_value']);
        $this->assertEquals('100.00', $result['decimal_value']);
    }
}
```

### 5. Critical Testing Rules

**Decimal Fields**:
```php
// ❌ WRONG - decimals are strings in Laravel
$this->assertIsFloat($value);
$this->assertEquals(100.0, $value);

// ✅ CORRECT
$this->assertIsString($value);
$this->assertEquals('100.00', $value);
```

**Mocking Services**:
```php
use Illuminate\Support\Facades\App;

App::shouldReceive('make')
    ->with(ExternalService::class)
    ->andReturn($mockService);
```

**Configuration**:
```php
Config::set('exchange_rates.default_rates.USD', 5.00);
```

### 6. Run Tests with Sail

```bash
# Run specific test
./vendor/bin/sail artisan test tests/Unit/Services/{ServiceName}ServiceTest.php

# Run with coverage
./vendor/bin/sail artisan test tests/Unit/Services/{ServiceName}ServiceTest.php --coverage

# Target: 95% coverage for services
```

### 7. Code Quality

```bash
# Format with Pint
./vendor/bin/sail bash -c "vendor/bin/pint app/Services/{ServiceName}Service.php"
./vendor/bin/sail bash -c "vendor/bin/pint tests/Unit/Services/{ServiceName}ServiceTest.php"

# Analyze with PHPStan
./vendor/bin/sail bash -c "vendor/bin/phpstan analyse app/Services/{ServiceName}Service.php"
```

### 8. Register Service (if needed)

**Only if the service needs singleton behavior or specific configuration**

Location: `app/Providers/AppServiceProvider.php`

```php
public function register(): void
{
    $this->app->singleton({ServiceName}Service::class, function ($app) {
        return new {ServiceName}Service(
            dependencyService: $app->make(DependencyService::class)
        );
    });
}
```

### 9. Documentation

Update `docs/SERVICES_API.md`:

```markdown
### {ServiceName}Service

**Purpose**: {Brief description}

**Location**: `app/Services/{ServiceName}Service.php`

**Dependencies**:
- DependencyService
- ModelName

**Methods**:

#### `methodName(Type $param): ReturnType`
- **Description**: What it does
- **Parameters**:
  - `$param` (Type): Description
- **Returns**: ReturnType with structure
- **Example**:
  ```php
  $service = app({ServiceName}Service::class);
  $result = $service->methodName($param);
  ```

**Test Coverage**: 95%+
```

## Examples from Existing Services

### GigFinancialCalculatorService
- ✅ Single responsibility: financial calculations
- ✅ Proper eager loading: `$gig->load(['gigCosts.costCenter'])`
- ✅ Returns structured arrays with warnings
- ✅ Currency conversion handling

### AuditService
- ✅ Bulk operations with optimized queries
- ✅ Comprehensive error handling
- ✅ Clear data structure returns
- ✅ 95%+ test coverage

### ExchangeRateService
- ✅ External API integration
- ✅ Fallback mechanisms
- ✅ Proper configuration usage
- ✅ Caching strategy

## Common Patterns in EventosPro

### Financial Calculations
```php
public function calculateTotal(Gig $gig): array
{
    $grossFee = $this->gigCalculator->calculateGrossFeeInBrl($gig);

    return [
        'gross_fee_brl' => $grossFee,
        'warnings' => $this->getWarnings($gig),
    ];
}
```

### Eager Loading
```php
public function getDataForReport(Collection $gigs): array
{
    // Prevent N+1
    $gigs->load(['artist', 'booker', 'payments', 'gigCosts.costCenter']);

    return $gigs->map(function ($gig) {
        return $this->processGig($gig);
    })->all();
}
```

### Configuration Access
```php
// ✅ CORRECT - hierarchical config
$rate = config('exchange_rates.default_rates.USD');
$apiKey = config('services.bcb_api.key');

// ❌ WRONG - flat config in app.php
$rate = config('app.exchange_rates');
```

## Deliverables Checklist

Before considering the service complete:

- [ ] Service class created in `app/Services/`
- [ ] Constructor with dependency injection
- [ ] All methods have return type declarations
- [ ] Comprehensive PHPDoc blocks
- [ ] Unit test created in `tests/Unit/Services/`
- [ ] Test coverage >= 95%
- [ ] All tests passing
- [ ] Code formatted with Pint (PSR-12)
- [ ] PHPStan analysis passes
- [ ] Documentation added to SERVICES_API.md
- [ ] No N+1 query issues
- [ ] Proper error handling
- [ ] Configuration using hierarchical structure

## Final Validation

Run complete validation:

```bash
# 1. Tests
./vendor/bin/sail artisan test tests/Unit/Services/{ServiceName}ServiceTest.php --coverage

# 2. Code quality
./vendor/bin/sail bash -c "vendor/bin/pint app/Services/{ServiceName}Service.php"
./vendor/bin/sail bash -c "vendor/bin/phpstan analyse app/Services/{ServiceName}Service.php"

# 3. Integration test
./vendor/bin/sail artisan test --filter={ServiceName}
```

---

**Remember**: Quality over speed. A well-tested, documented service is worth the extra time.
