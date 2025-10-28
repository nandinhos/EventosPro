# Common Pitfalls & Solutions

> **Purpose**: Avoid repeating past mistakes. Read this before starting any task to save time and tokens.

## 🚨 Critical Errors to Avoid

### 1. Running Commands Outside Sail

**❌ WRONG:**
```bash
php artisan test
composer install
npm run dev
```

**✅ CORRECT:**
```bash
sail artisan test
sail composer install
sail npm run dev
```

**Why it fails**: Project is containerized. Host PHP/Composer may have different versions or missing extensions.

**Quick fix**: Always prefix with `sail` or create alias: `alias sail='./vendor/bin/sail'`

---

### 2. Using Deprecated Test Annotations

**❌ WRONG:**
```php
/** @test */
public function it_calculates_something()
{
    // ...
}
```

**✅ CORRECT:**
```php
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function it_calculates_something()
{
    // ...
}
```

**Why it fails**: PHPUnit 11 deprecated `@test` annotation. Will be removed in PHPUnit 12. Generates warnings.

**Quick fix**: Use `#[Test]` attribute or `test_` prefix in method name.

---

### 3. Decimal Field Type Confusion

**❌ WRONG:**
```php
$payment = Payment::factory()->create(['due_value' => 500.00]);

$this->assertIsFloat($payment->due_value);
$this->assertEquals(500.00, $payment->due_value);
```

**✅ CORRECT:**
```php
$payment = Payment::factory()->create(['due_value' => 500.00]);

$this->assertIsString($payment->due_value);  // Laravel decimal cast returns STRING
$this->assertEquals('500.00', $payment->due_value);
```

**Why it fails**: Laravel's `decimal` cast returns **strings**, not floats, for precision.

**Quick fix**: Always use `assertIsString()` and compare string values for decimal fields.

**Affected models**: Payment, GigCost, Settlement (all monetary fields)

---

### 4. Duplicating Financial Calculations

**❌ WRONG:**
```php
// In Controller
public function show(Gig $gig)
{
    $grossFee = $gig->cache_value;
    if ($gig->cache_currency !== 'BRL') {
        $rate = config("exchange_rates.default_rates.{$gig->cache_currency}");
        $grossFee = $grossFee * $rate;
    }

    $commission = $grossFee * ($gig->agency_commission_percentage / 100);
    // ...
}
```

**✅ CORRECT:**
```php
// In Controller
public function __construct(
    private GigFinancialCalculatorService $calculator
) {}

public function show(Gig $gig)
{
    $grossFee = $this->calculator->calculateGrossFeeInBrl($gig);
    $commission = $this->calculator->calculateAgencyCommissionBrl($gig);
    // ...
}
```

**Why it fails**:
- Logic duplication across codebase
- Inconsistent calculations
- Hard to maintain/test
- Missing business rules (currency warnings, cost considerations)

**Quick fix**: ALWAYS use `GigFinancialCalculatorService` for any financial calculation.

---

### 5. N+1 Query Problem

**❌ WRONG:**
```php
$gigs = Gig::all();

foreach ($gigs as $gig) {
    echo $gig->artist->name;  // N+1 query!
    echo $gig->booker->name;  // Another N+1!

    foreach ($gig->payments as $payment) {  // Yet another N+1!
        echo $payment->due_value;
    }
}
```

**✅ CORRECT:**
```php
$gigs = Gig::with(['artist', 'booker', 'payments'])->get();

foreach ($gigs as $gig) {
    echo $gig->artist->name;  // No extra query
    echo $gig->booker->name;  // No extra query

    foreach ($gig->payments as $payment) {  // No extra query
        echo $payment->due_value;
    }
}
```

**Why it fails**: Each relationship access triggers a separate database query.

**Quick fix**: Always eager load relationships with `with()`.

**Common relationships to eager load:**
- Gig: `['artist', 'booker', 'payments', 'gigCosts.costCenter', 'settlement']`
- Payment: `['gig.artist', 'gig.booker']`
- Settlement: `['gig.artist', 'gig.booker']`

---

### 6. Forgetting RefreshDatabase in Tests

**❌ WRONG:**
```php
class MyFeatureTest extends TestCase
{
    #[Test]
    public function it_creates_a_gig()
    {
        $gig = Gig::factory()->create();

        $this->assertDatabaseHas('gigs', ['id' => $gig->id]);
    }
}
```

**✅ CORRECT:**
```php
class MyFeatureTest extends TestCase
{
    use RefreshDatabase;  // ← ADD THIS

    #[Test]
    public function it_creates_a_gig()
    {
        $gig = Gig::factory()->create();

        $this->assertDatabaseHas('gigs', ['id' => $gig->id]);
    }
}
```

**Why it fails**: Tests pollute database. Future tests fail due to unexpected data.

**Quick fix**: Always add `use RefreshDatabase;` trait in feature/integration tests.

---

### 7. Hardcoding Configuration Values

**❌ WRONG:**
```php
$appName = env('APP_NAME');  // ❌ Outside config file
$exchangeRate = 5.50;  // ❌ Hardcoded
```

**✅ CORRECT:**
```php
// In config/exchange_rates.php
return [
    'default_rates' => [
        'USD' => env('EXCHANGE_RATE_USD', 5.50),
    ],
];

// In your code
$appName = config('app.name');
$exchangeRate = config('exchange_rates.default_rates.USD');
```

**Why it fails**:
- `env()` is only available in config files in production (cached)
- Hardcoded values can't be changed without code deployment
- Not testable

**Quick fix**: Use `config()` helper. Create dedicated config files for domain-specific settings.

---

### 8. Not Running Pint Before Committing

**❌ WRONG:**
```bash
git add .
git commit -m "Add feature"
# CI fails on code style
```

**✅ CORRECT:**
```bash
sail bash -c "vendor/bin/pint --dirty"
git add .
git commit -m "Add feature"
```

**Why it fails**: CI pipeline enforces PSR-12 standards via Pint.

**Quick fix**: Always run Pint before committing. Add to pre-commit hook if possible.

---

### 9. Forgetting to Test Service Mocking

**❌ WRONG:**
```php
#[Test]
public function it_converts_currency()
{
    // Uses real ExchangeRateService
    // Calls real Banco Central API
    // Test fails if API is down
    $service = app(ExchangeRateService::class);
    $result = $service->convertToBRL(100, 'USD');

    $this->assertGreaterThan(0, $result);
}
```

**✅ CORRECT:**
```php
#[Test]
public function it_converts_currency()
{
    // Mock the service
    $mockService = Mockery::mock(ExchangeRateService::class);
    $mockService->shouldReceive('convertToBRL')
        ->with(100, 'USD')
        ->andReturn(550.00);

    App::shouldReceive('make')
        ->with(ExchangeRateService::class)
        ->andReturn($mockService);

    $result = $mockService->convertToBRL(100, 'USD');

    $this->assertEquals(550.00, $result);
}
```

**Why it fails**: External API calls make tests slow, flaky, and dependent on network.

**Quick fix**: Always mock external services. Use `Config::set()` for simple rate overrides in tests.

---

### 10. Creating Models Manually in Tests

**❌ WRONG:**
```php
#[Test]
public function it_calculates_gross_fee()
{
    $artist = new Artist();
    $artist->name = 'Test Artist';
    $artist->save();

    $booker = new Booker();
    $booker->name = 'Test Booker';
    $booker->save();

    $gig = new Gig();
    $gig->artist_id = $artist->id;
    $gig->booker_id = $booker->id;
    $gig->cache_value = 10000;
    $gig->cache_currency = 'BRL';
    $gig->gig_date = now();
    $gig->save();

    // Test code...
}
```

**✅ CORRECT:**
```php
#[Test]
public function it_calculates_gross_fee()
{
    $gig = Gig::factory()
        ->for(Artist::factory())
        ->for(Booker::factory())
        ->create([
            'cache_value' => 10000,
            'cache_currency' => 'BRL',
        ]);

    // Test code...
}
```

**Why it fails**:
- Verbose, hard to read
- Missing required fields cause errors
- Doesn't follow project patterns
- Factories already have sensible defaults

**Quick fix**: ALWAYS use factories. Check factory for available states/methods.

---

### 11. Adding AI Attribution to Commits

**❌ WRONG:**
```bash
git commit -m "feat: Add new feature

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```

**✅ CORRECT:**
```bash
git commit -m "feat: Add new feature

Implements user authentication with email verification.
Includes comprehensive test coverage."
```

**Why it fails**:
- Unprofessional in git history
- Not required by project standards
- Clutters commit messages
- Git authorship already shows who committed

**Quick fix**: Write clean, descriptive commit messages without AI attribution.

**Commit Message Format**:
```
type(scope): Brief description

Detailed explanation of what changed and why.
Include relevant context or breaking changes.

# Types: feat, fix, docs, style, refactor, test, chore
# No emojis, no AI attribution, no co-authorship
```

---

## 🔧 Quick Troubleshooting

### Tests Failing Locally

```bash
# 1. Clear caches
sail artisan config:clear
sail artisan cache:clear

# 2. Recreate test database
sail artisan migrate:fresh --env=testing

# 3. Run specific failing test
sail artisan test --filter=FailingTestName
```

### Sail Container Issues

```bash
# Stop all containers
sail down

# Remove volumes (⚠️ deletes data)
sail down -v

# Rebuild containers
sail build --no-cache

# Start fresh
sail up -d
```

### Coverage Not Updating

```bash
# Generate fresh coverage report
sail artisan test --coverage --coverage-html=coverage-report

# View report
open coverage-report/index.html  # macOS
xdg-open coverage-report/index.html  # Linux
```

### Filament Resource Not Showing

```bash
# Clear Filament cache
sail artisan filament:cache-clear

# Clear view cache
sail artisan view:clear

# Rebuild assets
sail npm run build
```

---

## 📋 Pre-Commit Checklist

Before every commit, verify:

- [ ] `sail artisan test` passes ✅
- [ ] `sail bash -c "vendor/bin/pint --dirty"` applied ✅
- [ ] No `dd()`, `dump()`, or debug code left ✅
- [ ] New code has tests (80% coverage minimum) ✅
- [ ] Used factories for model creation in tests ✅
- [ ] Used services for business logic ✅
- [ ] Eager loaded relationships ✅
- [ ] No hardcoded configuration values ✅
- [ ] PHP 8 attributes for tests (`#[Test]`) ✅
- [ ] Decimal assertions use `assertIsString()` ✅
- [ ] **No AI co-authorship in commit message** ✅

---

## 🎯 Most Common Token-Wasting Questions

### "Why is my test failing?"

**Before asking**:
1. Check if using `RefreshDatabase` trait
2. Check decimal field assertions (string vs float)
3. Check if using `#[Test]` attribute
4. Check if using factories correctly
5. Read error message carefully

### "How do I calculate [financial metric]?"

**Before asking**:
1. Check `GigFinancialCalculatorService` - method probably exists
2. Check `docs/SERVICES_API.md`
3. Never duplicate calculation logic

### "How do I run [command]?"

**Before asking**:
1. All commands use Sail prefix
2. Check Quick Reference section in main CLAUDE.md
3. Check `docs/LARAVEL_SAIL_COMMANDS.md`

---

**Last Updated**: 2025-10-27
