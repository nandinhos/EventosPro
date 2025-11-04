# Bug Fixing Context

> **When to use**: Debugging issues, fixing bugs, troubleshooting errors, resolving test failures.

## 🔍 Bug Fixing Workflow

```
1. Reproduce Bug
   ↓
2. Identify Root Cause
   ↓
3. Write Failing Test
   ↓
4. Fix Bug
   ↓
5. Verify Test Passes
   ↓
6. Check for Similar Issues
```

## 🎯 Quick Diagnosis Checklist

**Before diving deep, check these common issues:**

- [ ] Read Common Pitfalls guide (`.claude/guides/07-common-pitfalls.md`)
- [ ] Check error message carefully (line number, file)
- [ ] Verify using Sail for all commands
- [ ] Check if decimal field assertion issue
- [ ] Check if N+1 query problem
- [ ] Check if missing eager loading
- [ ] Check if using deprecated `@test` annotation
- [ ] Check if missing `RefreshDatabase` trait

## 🚨 Common Error Patterns

### 1. Type Errors (Decimal Fields)

**Error**: `Failed asserting that 500.0 is of type string`

**Cause**: Laravel `decimal` cast returns **string**, not float

**Location**: Model attributes with `decimal` cast

**Fix**:
```php
// ❌ Wrong
$this->assertIsFloat($payment->due_value);
$this->assertEquals(500.00, $payment->due_value);

// ✅ Correct
$this->assertIsString($payment->due_value);
$this->assertEquals('500.00', $payment->due_value);
```

**Test**: `sail artisan test --filter=YourFailingTest`

---

### 2. Undefined Method Errors

**Error**: `Call to undefined method calculateSomething()`

**Cause**: Typo or method doesn't exist in service

**Diagnosis**:
1. Check service file for correct method name
2. Verify service is injected correctly
3. Check if method is public (not private)

**Fix**:
```php
// Check GigFinancialCalculatorService.php for available methods
$grossFee = $this->calculator->calculateGrossFeeInBrl($gig); // ✅ Exists
$netFee = $this->calculator->calculateNetFee($gig);         // ❌ Doesn't exist
```

**Quick Reference**: See `.claude/guides/03-services.md` for all service methods

---

### 3. N+1 Query Performance Issues

**Symptom**: Page loads slowly, many database queries

**Diagnosis**:
```php
// Add to controller temporarily
\DB::enableQueryLog();
// ... your code ...
dd(\DB::getQueryLog()); // Shows all queries
```

**Cause**: Missing eager loading

**Fix**:
```php
// ❌ Wrong - Triggers N+1
$gigs = Gig::all();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // Query per gig!
}

// ✅ Correct - Single query
$gigs = Gig::with('artist')->get();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // No extra queries
}
```

---

### 4. Foreign Key Constraint Violations

**Error**: `SQLSTATE[23000]: Integrity constraint violation`

**Cause**: Trying to create/update record with invalid foreign key

**Diagnosis**:
1. Check if related record exists
2. Verify foreign key column name
3. Check factory creates relationships

**Fix (in tests)**:
```php
// ❌ Wrong
$gig = Gig::factory()->create(['artist_id' => 999]); // Artist doesn't exist

// ✅ Correct
$gig = Gig::factory()
    ->for(Artist::factory())
    ->create();
```

**Fix (in code)**:
```php
// Validate foreign key exists
$request->validate([
    'artist_id' => ['required', 'exists:artists,id'],
]);
```

---

### 5. PHPUnit Deprecation Warnings

**Error**: `Using the @test docblock annotation is deprecated`

**Cause**: Using old `@test` annotation instead of PHP 8 attribute

**Fix**:
```php
// ❌ Wrong (deprecated)
/** @test */
public function it_does_something() { }

// ✅ Correct
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function it_does_something() { }
```

**Bulk Fix**: Search and replace across test files

---

### 6. Missing RefreshDatabase

**Error**: Duplicate entry, unexpected data in tests

**Cause**: Database not reset between tests

**Fix**:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // ← Add this
}
```

---

### 7. Currency Conversion Errors

**Error**: Wrong calculated values, missing exchange rates

**Cause**: ExchangeRateService not mocked or misconfigured

**Fix (in tests)**:
```php
use Illuminate\Support\Facades\Config;

#[Test]
public function it_converts_usd_to_brl()
{
    // Set predictable rate
    Config::set('exchange_rates.default_rates.USD', 5.50);

    $gig = Gig::factory()->create([
        'cache_value' => 1000,
        'cache_currency' => 'USD',
    ]);

    $result = $this->calculator->calculateGrossFeeInBrl($gig);

    $this->assertEquals(5500.00, $result);
}
```

---

### 8. Sail Container Issues

**Error**: Command not found, connection refused

**Cause**: Containers not running or misconfigured

**Fix**:
```bash
# Check container status
sail ps

# Restart containers
sail down
sail up -d

# Rebuild if needed
sail build --no-cache
sail up -d
```

---

## 🔧 Debugging Tools

### 1. Laravel Debugbar (Development)

**Install** (if not installed):
```bash
sail composer require barryvdh/laravel-debugbar --dev
```

**Use**: Automatically shows queries, timeline, views

---

### 2. Tinker (Interactive Shell)

```bash
sail artisan tinker
```

**Examples**:
```php
>>> $gig = App\Models\Gig::first()
>>> $gig->artist->name
>>> App\Services\GigFinancialCalculatorService::class
>>> $service = app(App\Services\GigFinancialCalculatorService::class)
>>> $service->calculateGrossFeeInBrl($gig)
```

---

### 3. Query Logging

```php
// In controller/service temporarily
\DB::enableQueryLog();

// ... your code ...

dd(\DB::getQueryLog());
// Shows all executed queries
```

---

### 4. Log Files

```bash
# Watch logs in real-time
sail artisan pail

# Or view log file
sail bash -c "tail -f storage/logs/laravel.log"
```

---

### 5. Database Query Tool (Laravel Boost)

```bash
# Use Laravel Boost MCP tool
database-query "SELECT * FROM gigs WHERE id = 1"
```

---

## 🐛 Systematic Bug Investigation

### Step 1: Reproduce Consistently

```php
// Write minimal reproduction test
#[Test]
public function it_reproduces_the_bug()
{
    // Arrange - Minimal setup
    $gig = Gig::factory()->create(['cache_value' => 10000]);

    // Act - Trigger bug
    $result = $this->calculator->calculateGrossFeeInBrl($gig);

    // Assert - Expected vs Actual
    $this->assertEquals(10000.00, $result); // Fails? Good! Bug reproduced.
}
```

### Step 2: Isolate Root Cause

**Add debug statements**:
```php
// In service method
public function calculateGrossFeeInBrl(Gig $gig): float
{
    \Log::info('Cache value: ' . $gig->cache_value);
    \Log::info('Currency: ' . $gig->cache_currency);

    $result = // ... calculation

    \Log::info('Result: ' . $result);
    return $result;
}
```

**Run test with logs**:
```bash
sail artisan test --filter=it_reproduces_the_bug
sail artisan pail  # Check logs
```

### Step 3: Fix and Verify

```php
// Fix the bug

// Verify test passes
sail artisan test --filter=it_reproduces_the_bug

// Run all tests
sail artisan test
```

### Step 4: Check for Similar Issues

```bash
# Search for similar patterns
sail bash -c "grep -r 'problematic_pattern' app/"

# Check if other services have same issue
sail bash -c "grep -r 'calculateGrossFee' app/Services/"
```

---

## 🎯 Error Message Guide

### Common Laravel Errors

#### `Class not found`
- **Cause**: Namespace or use statement incorrect
- **Fix**: Check `use` statements, run `sail composer dump-autoload`

#### `Method does not exist`
- **Cause**: Typo, wrong class, or method is private
- **Fix**: Check class file, verify method is public

#### `Trying to get property of non-object`
- **Cause**: Relationship not loaded or null
- **Fix**: Eager load relationship or add null check

#### `SQLSTATE[42S02]: Base table or view not found`
- **Cause**: Migration not run
- **Fix**: `sail artisan migrate`

#### `SQLSTATE[23000]: Integrity constraint violation`
- **Cause**: Foreign key doesn't exist or duplicate entry
- **Fix**: Verify related records exist, use factories

---

## 🧪 Test-Driven Bug Fixing

### The Red-Green-Refactor Cycle

**1. RED**: Write failing test that reproduces bug
```php
#[Test]
public function it_handles_null_booker_commission()
{
    $gig = Gig::factory()->create(['booker_id' => null]);

    $result = $this->calculator->calculateBookerCommissionBrl($gig);

    $this->assertEquals(0.00, $result); // Currently fails
}
```

**2. GREEN**: Fix bug with minimal change
```php
public function calculateBookerCommissionBrl(Gig $gig): float
{
    if (!$gig->booker_id) {
        return 0.00; // ← Fix
    }

    // ... existing logic
}
```

**3. REFACTOR**: Improve code while keeping tests green
```php
public function calculateBookerCommissionBrl(Gig $gig): float
{
    // More elegant null handling
    if (is_null($gig->booker_id)) {
        return 0.00;
    }

    $grossFee = $this->calculateGrossFeeInBrl($gig);
    return $grossFee * ($gig->booker?->commission_percentage ?? 0) / 100;
}
```

---

## 🔍 Common Bug Locations

### Controllers
- Missing validation
- Not using services
- Missing eager loading
- Incorrect redirect logic

### Services
- Currency conversion issues
- Missing null checks
- Calculation logic errors
- Not handling edge cases

### Models
- Incorrect casts
- Missing fillable fields
- Wrong relationship definitions
- Observer side effects

### Tests
- Decimal type assertions
- Missing RefreshDatabase
- Not using factories
- Deprecated annotations

---

## 📋 Bug Fix Checklist

- [ ] Bug reproduced in test
- [ ] Test fails before fix
- [ ] Fix implemented
- [ ] Test passes after fix
- [ ] All tests still pass
- [ ] Similar issues checked
- [ ] Code formatted with Pint
- [ ] No debug code left (dd, dump, Log::info)
- [ ] Comments added for complex fixes
- [ ] Documentation updated if needed

---

## 🚀 Performance Debugging

### Slow Query Detection

**Enable Query Log**:
```php
\DB::enableQueryLog();

// Your code

$queries = \DB::getQueryLog();
foreach ($queries as $query) {
    if ($query['time'] > 100) { // Queries > 100ms
        \Log::warning('Slow query: ' . $query['query']);
    }
}
```

### Memory Issues

```bash
# Check memory usage
sail bash -c "php -i | grep memory_limit"

# Increase for specific command
sail bash -c "php -d memory_limit=512M artisan test"
```

---

## 📚 When to Escalate

**Ask for help if**:
1. Issue persists after checking Common Pitfalls guide
2. Error message unclear and Google doesn't help
3. Bug only occurs in specific environment (production)
4. Suspected framework/package bug
5. Data corruption or security concern

**Include when asking**:
- Exact error message
- File and line number
- Minimal reproduction steps
- What you've already tried
- Related code snippet

---

## 🎓 Learning from Bugs

**After fixing a bug**:
1. Document in Common Pitfalls if recurring
2. Add to test suite
3. Check if documentation needs update
4. Consider if architecture change needed

**Bug Prevention**:
- Write tests first (TDD)
- Use type hints
- Validate all inputs
- Use services for business logic
- Review Common Pitfalls regularly

---

## 📚 References

- Common Pitfalls: `.claude/guides/07-common-pitfalls.md`
- Testing: `.claude/contexts/testing-workflow.md`
- Services: `.claude/guides/03-services.md`
- Laravel Debugging: https://laravel.com/docs/12.x/errors

---

**Last Updated**: 2025-10-27
