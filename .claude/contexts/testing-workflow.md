# Testing Workflow Context

> **When to use**: Writing tests, debugging test failures, improving coverage, understanding test patterns.

## 🎯 Testing Philosophy

**Every change must be programmatically tested**

- New feature → New tests
- Bug fix → Test that reproduces bug + fix
- Refactor → Existing tests still pass

## 📊 Coverage Requirements

| Scope | Minimum Coverage | Notes |
|-------|------------------|-------|
| Overall | 80% | Project-wide |
| Critical Services | 95% | GigFinancialCalculatorService, AuditService, ExchangeRateService |
| Controllers | 70% | Focus on happy path + validation |
| Models | 60% | Relationships, scopes, accessors |

## 🏃 Running Tests

### Basic Commands

```bash
# All tests
sail artisan test

# Specific test file
sail artisan test tests/Unit/Services/AuditServiceTest.php

# Specific test method
sail artisan test --filter=it_calculates_gross_fee

# With coverage
sail artisan test --coverage

# With minimum coverage threshold
sail artisan test --coverage --min=80

# Parallel execution (faster)
sail artisan test --parallel
```

### Coverage Report

```bash
# Generate HTML coverage report
sail artisan test --coverage --coverage-html=coverage-report

# Open report (macOS)
open coverage-report/index.html

# Open report (Linux)
xdg-open coverage-report/index.html
```

## 📝 Test Structure Standards

### File Organization

```
tests/
├── Feature/          # Integration tests (HTTP, workflows)
│   ├── Controllers/
│   ├── Services/     # Complex service integrations
│   └── Commands/
│
└── Unit/             # Isolated unit tests
    ├── Services/     # Business logic
    ├── Models/       # Model methods
    └── Helpers/      # Utility functions
```

### Naming Conventions

**Test Methods**: Descriptive, behavior-focused

```php
// ✅ GOOD - Clear what's being tested
#[Test]
public function it_calculates_gross_fee_in_brl_for_usd_currency()

#[Test]
public function it_throws_exception_when_gig_is_null()

#[Test]
public function it_returns_empty_array_when_no_payments_exist()

// ❌ BAD - Vague
#[Test]
public function test_calculation()

#[Test]
public function test_1()
```

## ✅ PHPUnit Attribute Usage (REQUIRED)

### Correct Pattern

```php
<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyServiceTest extends TestCase
{
    #[Test]  // ✅ REQUIRED - Use PHP 8 attribute
    public function it_does_something()
    {
        // ...
    }
}
```

### Deprecated Pattern (DO NOT USE)

```php
/** @test */  // ❌ DEPRECATED - Generates warnings in PHPUnit 11
public function it_does_something()
{
    // ...
}
```

### Alternative (Also Valid)

```php
public function test_it_does_something()  // ✅ Valid - traditional naming
{
    // ...
}
```

## 🧪 Test Patterns

### Feature Test Template

```php
<?php

namespace Tests\Feature;

use App\Models\{Gig, Artist, Booker};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GigControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_displays_gig_details()
    {
        // Arrange
        $gig = Gig::factory()
            ->for(Artist::factory())
            ->for(Booker::factory())
            ->create();

        // Act
        $response = $this->get(route('gigs.show', $gig));

        // Assert
        $response->assertOk();
        $response->assertSee($gig->artist->name);
        $response->assertSee($gig->booker->name);
    }

    #[Test]
    public function it_validates_required_fields_on_store()
    {
        // Act
        $response = $this->post(route('gigs.store'), []);

        // Assert
        $response->assertSessionHasErrors([
            'artist_id',
            'booker_id',
            'cache_value',
            'gig_date',
        ]);
    }

    #[Test]
    public function it_creates_gig_with_valid_data()
    {
        // Arrange
        $artist = Artist::factory()->create();
        $booker = Booker::factory()->create();

        $data = [
            'artist_id' => $artist->id,
            'booker_id' => $booker->id,
            'cache_value' => 10000,
            'cache_currency' => 'BRL',
            'gig_date' => now()->addDays(30)->format('Y-m-d'),
        ];

        // Act
        $response = $this->post(route('gigs.store'), $data);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('gigs', [
            'artist_id' => $artist->id,
            'cache_value' => '10000.00', // String! Decimal cast
        ]);
    }
}
```

### Unit Test Template (Services)

```php
<?php

namespace Tests\Unit\Services;

use App\Services\GigFinancialCalculatorService;
use App\Models\{Gig, Artist, Booker};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GigFinancialCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private GigFinancialCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(GigFinancialCalculatorService::class);
    }

    #[Test]
    public function it_calculates_gross_fee_for_brl_currency()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'cache_value' => 10000,
            'cache_currency' => 'BRL',
        ]);

        // Act
        $result = $this->calculator->calculateGrossFeeInBrl($gig);

        // Assert
        $this->assertIsFloat($result);  // Service returns float
        $this->assertEquals(10000.00, $result);
    }

    #[Test]
    public function it_calculates_agency_commission_with_percentage()
    {
        // Arrange
        $gig = Gig::factory()->create([
            'cache_value' => 10000,
            'cache_currency' => 'BRL',
            'agency_commission_percentage' => 20,
        ]);

        // Act
        $result = $this->calculator->calculateAgencyCommissionBrl($gig);

        // Assert
        $this->assertEquals(2000.00, $result);
    }
}
```

## 🏭 Factory Usage

### Basic Factory Pattern

```php
// Simple creation
$gig = Gig::factory()->create();

// Override attributes
$gig = Gig::factory()->create([
    'cache_value' => 15000,
    'cache_currency' => 'USD',
]);

// Create with relationships
$gig = Gig::factory()
    ->for(Artist::factory())
    ->for(Booker::factory())
    ->create();

// Create multiple
$gigs = Gig::factory()->count(5)->create();
```

### Using Factory States

```php
// Check factory for available states
$gig = Gig::factory()->past()->create();      // Past gig
$gig = Gig::factory()->upcoming()->create();  // Future gig
$gig = Gig::factory()->withUsd()->create();   // USD currency
```

### Factory with Relationships

```php
// Has many
$gig = Gig::factory()
    ->has(Payment::factory()->count(3))
    ->has(GigCost::factory()->count(2))
    ->create();

// Nested relationships
$gig = Gig::factory()
    ->has(
        GigCost::factory()
            ->for(CostCenter::factory())
            ->count(2)
    )
    ->create();
```

## 🎯 Assertion Patterns

### String vs Float (Decimal Fields)

```php
// Model attributes (decimal cast) return STRINGS
$payment = Payment::factory()->create(['due_value' => 500.00]);

$this->assertIsString($payment->due_value);        // ✅
$this->assertEquals('500.00', $payment->due_value); // ✅

// Service methods return FLOATS
$result = $this->calculator->calculateGrossFeeInBrl($gig);

$this->assertIsFloat($result);          // ✅
$this->assertEquals(10000.00, $result); // ✅
```

### Database Assertions

```php
// Record exists
$this->assertDatabaseHas('gigs', [
    'artist_id' => $artist->id,
    'cache_value' => '10000.00', // Note: String for decimal
]);

// Record doesn't exist
$this->assertDatabaseMissing('gigs', [
    'artist_id' => 999,
]);

// Count records
$this->assertDatabaseCount('gigs', 5);

// Soft deleted
$this->assertSoftDeleted('gigs', [
    'id' => $gig->id,
]);
```

### Response Assertions

```php
// Status codes
$response->assertOk();           // 200
$response->assertCreated();      // 201
$response->assertNoContent();    // 204
$response->assertNotFound();     // 404
$response->assertForbidden();    // 403

// Redirects
$response->assertRedirect(route('gigs.index'));
$response->assertRedirectToRoute('gigs.show', $gig);

// Session
$response->assertSessionHas('success');
$response->assertSessionHasErrors(['cache_value', 'gig_date']);

// View
$response->assertViewIs('gigs.show');
$response->assertViewHas('gig');
$response->assertSee('Artist Name');
$response->assertDontSee('Hidden Text');
```

## 🎭 Mocking External Services

### Mock ExchangeRateService

```php
use Mockery;
use App\Services\ExchangeRateService;

#[Test]
public function it_uses_exchange_rate_service()
{
    // Create mock
    $mockService = Mockery::mock(ExchangeRateService::class);

    // Define expectations
    $mockService->shouldReceive('convertToBRL')
        ->once()
        ->with(1000, 'USD')
        ->andReturn(5500.00);

    // Inject mock
    App::shouldReceive('make')
        ->with(ExchangeRateService::class)
        ->andReturn($mockService);

    // Test code using mocked service
    $calculator = new GigFinancialCalculatorService($mockService);
    $result = $calculator->calculateGrossFeeInBrl($gigInUsd);

    $this->assertEquals(5500.00, $result);
}
```

### Use Config for Simpler Tests

```php
use Illuminate\Support\Facades\Config;

#[Test]
public function it_uses_default_exchange_rate()
{
    // Set config value
    Config::set('exchange_rates.default_rates.USD', 5.50);

    // Test uses config fallback (no API call)
    $result = $this->calculator->calculateGrossFeeInBrl($gig);

    $this->assertEquals(5500.00, $result);
}
```

## 🧹 Test Data Management

### Use RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;  // ← REQUIRED for feature/integration tests

    // Tests now have clean database
}
```

### Database Transactions (Alternative)

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions;  // Rolls back after each test
                               // Faster but less reliable
}
```

## 🐛 Debugging Failing Tests

### Common Test Failures

#### 1. Decimal Type Mismatch

**Error**: `Failed asserting that 500.0 is of type string`

**Fix**:
```php
// ❌ Wrong
$this->assertIsFloat($payment->due_value);

// ✅ Correct
$this->assertIsString($payment->due_value);
```

#### 2. Missing RefreshDatabase

**Error**: `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`

**Fix**:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;  // Add this
}
```

#### 3. Deprecated @test Annotation

**Error**: `Using the @test docblock annotation is deprecated`

**Fix**:
```php
use PHPUnit\Framework\Attributes\Test;

#[Test]  // Use attribute instead of @test
public function it_does_something() { }
```

#### 4. Factory Relationship Missing

**Error**: `SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row`

**Fix**:
```php
// ❌ Wrong
$gig = Gig::factory()->create(['artist_id' => 999]); // Artist doesn't exist

// ✅ Correct
$gig = Gig::factory()
    ->for(Artist::factory())  // Create artist automatically
    ->create();
```

### Debug Helpers

```php
// Dump and die (remove before commit!)
dd($variable);

// Dump without stopping
dump($variable);

// Database queries
\DB::enableQueryLog();
// ... run code ...
dd(\DB::getQueryLog());

// Response content
$response = $this->get('/gigs');
dd($response->getContent());
```

## 📋 Pre-Commit Test Checklist

- [ ] All tests pass: `sail artisan test`
- [ ] No `dd()` or `dump()` left in tests
- [ ] Using `#[Test]` attribute (not `@test`)
- [ ] Using `RefreshDatabase` trait
- [ ] Using factories (not manual model creation)
- [ ] Decimal assertions correct (string vs float)
- [ ] Descriptive test method names
- [ ] Coverage ≥ 80% overall
- [ ] Code formatted: `sail bash -c "vendor/bin/pint --dirty"`

## 🚀 Advanced Patterns

### Testing Livewire Components

```php
use Livewire\Livewire;
use App\Filament\Resources\GigResource\Pages\ListGigs;

#[Test]
public function it_lists_gigs_in_table()
{
    $gigs = Gig::factory()->count(3)->create();

    Livewire::test(ListGigs::class)
        ->assertCanSeeTableRecords($gigs)
        ->assertCountTableRecords(3);
}

#[Test]
public function it_filters_gigs_by_artist()
{
    $artist = Artist::factory()->create();
    $gigsWithArtist = Gig::factory()->for($artist)->count(2)->create();
    $otherGigs = Gig::factory()->count(3)->create();

    Livewire::test(ListGigs::class)
        ->filterTable('artist', $artist->id)
        ->assertCanSeeTableRecords($gigsWithArtist)
        ->assertCanNotSeeTableRecords($otherGigs);
}
```

### Testing Jobs

```php
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessGigSettlement;

#[Test]
public function it_dispatches_settlement_job()
{
    Queue::fake();

    $gig = Gig::factory()->create();

    // Trigger job dispatch
    $this->post(route('gigs.settle', $gig));

    Queue::assertPushed(ProcessGigSettlement::class, function ($job) use ($gig) {
        return $job->gig->id === $gig->id;
    });
}
```

### Testing Events

```php
use Illuminate\Support\Facades\Event;
use App\Events\GigCreated;

#[Test]
public function it_fires_gig_created_event()
{
    Event::fake();

    $gig = Gig::factory()->create();

    Event::assertDispatched(GigCreated::class, function ($event) use ($gig) {
        return $event->gig->id === $gig->id;
    });
}
```

## 📚 References

- Full Testing Guide: `docs/TESTING.md`
- Testing Best Practices: `docs/TESTING_BEST_PRACTICES.md`
- Common Pitfalls: `.claude/guides/07-common-pitfalls.md`
- Factory Patterns: Check `database/factories/` for examples

---

**Last Updated**: 2025-10-27
