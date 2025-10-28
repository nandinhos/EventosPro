# Feature Development Context

> **When to use**: Implementing new features, adding functionality, extending existing systems.

## 🎯 Development Workflow

```
1. Understand Requirements
   ↓
2. Check Existing Patterns
   ↓
3. Plan Architecture (Service/Controller/View)
   ↓
4. Implement with Tests
   ↓
5. Code Quality Check
   ↓
6. Commit
```

## 📋 Pre-Development Checklist

Before writing code:

- [ ] Read relevant guide sections (Architecture, Services, Database)
- [ ] Check if similar feature exists (reuse patterns)
- [ ] Identify which services to use/extend
- [ ] Plan data model changes (migrations needed?)
- [ ] Determine test strategy (unit/feature tests)
- [ ] Check Filament resources if admin UI needed

## 🏗️ Architecture Decision Tree

### Should I create a new Service?

```
Feature requires business logic?
  │
  ├─ YES → Complex multi-step calculations?
  │   │
  │   ├─ YES → Create new Service
  │   │   Examples: ReportGeneratorService, BookingWorkflowService
  │   │
  │   └─ NO → Add to existing Service
  │       Examples: Add method to GigFinancialCalculatorService
  │
  └─ NO → Put logic in Controller/Model
      Examples: Simple CRUD, basic validation
```

### Service Creation Pattern

**Location**: `app/Services/`

```php
<?php

namespace App\Services;

use App\Models\Gig;

class MyNewService
{
    // Inject dependencies
    public function __construct(
        private GigFinancialCalculatorService $calculator,
        private AnotherService $anotherService
    ) {}

    /**
     * Clear docblock explaining what this does
     */
    public function doSomething(Gig $gig): array
    {
        // Implementation
        return [
            'result' => $value,
        ];
    }
}
```

**Register in AppServiceProvider** (if needed):

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(MyNewService::class, function ($app) {
        return new MyNewService(
            $app->make(GigFinancialCalculatorService::class),
            $app->make(AnotherService::class)
        );
    });
}
```

## 🗄️ Database Changes

### Creating Migrations

```bash
# New table
sail artisan make:migration create_table_name

# Modify existing table
sail artisan make:migration add_field_to_table_name
```

### Migration Best Practices

```php
public function up(): void
{
    Schema::table('gigs', function (Blueprint $table) {
        // Add column with all attributes
        $table->decimal('new_field', 10, 2)
            ->nullable()
            ->after('existing_field')
            ->comment('Description of field');

        // Add index if needed
        $table->index('new_field');
    });
}

public function down(): void
{
    Schema::table('gigs', function (Blueprint $table) {
        $table->dropColumn('new_field');
    });
}
```

### Important: Modifying Existing Columns

**⚠️ Must include ALL previous attributes**

```php
// ❌ WRONG - Will lose other attributes
$table->decimal('cache_value', 12, 2)->change();

// ✅ CORRECT - Preserves nullability, default, etc.
$table->decimal('cache_value', 12, 2)
    ->nullable()
    ->default(0)
    ->change();
```

## 📝 Model Updates

### Adding New Fields

```php
// In Model
class Gig extends Model
{
    // 1. Add to fillable
    protected $fillable = [
        // ... existing fields
        'new_field',
    ];

    // 2. Add cast (if needed)
    protected function casts(): array
    {
        return [
            // ... existing casts
            'new_field' => 'decimal:2',
        ];
    }

    // 3. Add relationship (if needed)
    public function newRelation(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }
}
```

### Factory Updates

**⚠️ Always update factories when adding required fields**

```php
// database/factories/GigFactory.php
public function definition(): array
{
    return [
        // ... existing fields
        'new_field' => $this->faker->randomFloat(2, 100, 10000),
    ];
}

// Add states for specific scenarios
public function withHighValue(): static
{
    return $this->state(fn (array $attributes) => [
        'new_field' => 50000.00,
    ]);
}
```

## 🎨 Filament Resources

### When to Create/Update Filament Resource

✅ Create for:
- New admin-manageable entity (CRUD)
- Complex forms with relationships
- Tables with filters/actions

✅ Update existing for:
- Adding fields to existing entity
- New actions on existing resource
- Additional filters

### Creating New Filament Resource

```bash
sail artisan make:filament-resource ModelName --generate
# --generate creates form/table from model
```

### Form Pattern

```php
// In Resource class
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('artist_id')
                        ->relationship('artist', 'name')
                        ->required()
                        ->searchable(),

                    Forms\Components\DatePicker::make('gig_date')
                        ->required()
                        ->native(false),
                ]),

            Forms\Components\Section::make('Financial Details')
                ->schema([
                    Forms\Components\TextInput::make('cache_value')
                        ->numeric()
                        ->prefix('R$')
                        ->required(),

                    Forms\Components\Select::make('cache_currency')
                        ->options([
                            'BRL' => 'BRL',
                            'USD' => 'USD',
                            'EUR' => 'EUR',
                        ])
                        ->default('BRL')
                        ->required(),
                ]),
        ]);
}
```

### Table Pattern

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('artist.name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('gig_date')
                ->date('d/m/Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('cache_value')
                ->money('BRL')
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('artist')
                ->relationship('artist', 'name'),

            Tables\Filters\Filter::make('upcoming')
                ->query(fn (Builder $query) => $query->where('gig_date', '>=', now())),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
```

## 🧪 Testing Strategy

### Test Pyramid

```
        ╱╲
       ╱  ╲      Unit Tests (60%)
      ╱────╲     - Services
     ╱      ╲    - Calculations
    ╱────────╲
   ╱   Feature  ╲  Feature Tests (30%)
  ╱   Tests     ╲ - Controllers
 ╱───────────────╲ - Workflows
╱   Integration   ╲ Integration (10%)
────────────────── - Full flows
```

### Creating Tests

```bash
# Feature test (most common)
sail artisan make:test Feature/MyFeatureTest

# Unit test (for services)
sail artisan make:test Unit/Services/MyServiceTest --unit
```

### Feature Test Pattern

```php
<?php

namespace Tests\Feature;

use App\Models\Gig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_does_something()
    {
        // Arrange
        $gig = Gig::factory()->create();

        // Act
        $response = $this->get(route('gigs.show', $gig));

        // Assert
        $response->assertOk();
        $response->assertSee($gig->artist->name);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        // Act
        $response = $this->post(route('gigs.store'), []);

        // Assert
        $response->assertSessionHasErrors(['cache_value', 'gig_date']);
    }
}
```

### Unit Test Pattern (Services)

```php
<?php

namespace Tests\Unit\Services;

use App\Services\MyNewService;
use App\Models\Gig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MyNewServiceTest extends TestCase
{
    use RefreshDatabase;

    private MyNewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MyNewService::class);
    }

    #[Test]
    public function it_calculates_something()
    {
        // Arrange
        $gig = Gig::factory()->create(['cache_value' => 10000]);

        // Act
        $result = $this->service->doSomething($gig);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertEquals(10000, $result['result']);
    }
}
```

## 🎯 Controller Pattern

### Resource Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use App\Http\Requests\StoreGigRequest;
use Illuminate\Http\Request;

class GigController extends Controller
{
    public function __construct(
        private GigFinancialCalculatorService $calculator
    ) {}

    public function index()
    {
        $gigs = Gig::with(['artist', 'booker'])
            ->latest('gig_date')
            ->paginate(20);

        return view('gigs.index', compact('gigs'));
    }

    public function show(Gig $gig)
    {
        $gig->load(['payments', 'gigCosts.costCenter', 'settlement']);

        $financials = [
            'gross_fee' => $this->calculator->calculateGrossFeeInBrl($gig),
            'net_to_artist' => $this->calculator->calculateArtistNetPayout($gig),
        ];

        return view('gigs.show', compact('gig', 'financials'));
    }

    public function store(StoreGigRequest $request)
    {
        $gig = Gig::create($request->validated());

        return redirect()
            ->route('gigs.show', $gig)
            ->with('success', 'Gig created successfully');
    }
}
```

### Form Request Validation

```bash
sail artisan make:request StoreGigRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or implement authorization logic
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'exists:artists,id'],
            'booker_id' => ['required', 'exists:bookers,id'],
            'cache_value' => ['required', 'numeric', 'min:0'],
            'cache_currency' => ['required', 'in:BRL,USD,EUR,GBP'],
            'gig_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'artist_id.required' => 'Please select an artist',
            'cache_value.min' => 'Cachê value must be positive',
            'gig_date.after' => 'Gig date must be in the future',
        ];
    }
}
```

## 🔄 Query Optimization

### Always Eager Load

```php
// ❌ BAD - N+1 problem
$gigs = Gig::all();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // Query per gig
}

// ✅ GOOD - Single query
$gigs = Gig::with('artist')->get();
foreach ($gigs as $gig) {
    echo $gig->artist->name; // No extra query
}
```

### Nested Eager Loading

```php
// Load multiple levels
$gigs = Gig::with([
    'artist',
    'booker',
    'payments',
    'gigCosts.costCenter', // Nested relationship
])->get();
```

### Conditional Eager Loading

```php
$gigs = Gig::with([
    'artist',
    'payments' => function ($query) {
        $query->where('payment_confirmed', true);
    },
])->get();
```

## 📦 Feature Checklist

Before considering feature complete:

### Code
- [ ] Service layer used for business logic
- [ ] Controllers thin, delegate to services
- [ ] Proper dependency injection
- [ ] Query optimization (eager loading)
- [ ] Form Request validation
- [ ] Proper error handling

### Database
- [ ] Migrations created and tested
- [ ] Factories updated for new fields
- [ ] Seeders updated if needed
- [ ] Indexes added for queried fields

### Testing
- [ ] Feature tests cover main workflows
- [ ] Unit tests for service methods
- [ ] Tests use factories (not manual creation)
- [ ] Tests use `RefreshDatabase` trait
- [ ] Coverage ≥ 80% overall
- [ ] Coverage ≥ 95% for critical services

### Code Quality
- [ ] `sail bash -c "vendor/bin/pint --dirty"` passes
- [ ] `sail artisan test` passes
- [ ] No `dd()` or `dump()` left in code
- [ ] Proper PHPDoc blocks
- [ ] No code duplication

### Documentation
- [ ] Service API documented (if new service)
- [ ] Complex logic has inline comments
- [ ] README updated (if needed)

## 🚀 Deployment Considerations

### Pre-Deploy Checklist

- [ ] All tests passing
- [ ] Migration tested locally
- [ ] Database backup plan for production
- [ ] Rollback plan documented
- [ ] Feature flag (if large change)

### Migration Safety

```php
// ✅ Safe - Add nullable column
$table->string('new_field')->nullable();

// ⚠️ Risky - Add required column (existing rows fail)
$table->string('new_field'); // Need default or backfill

// ✅ Better - Add with default
$table->string('new_field')->default('value');
```

## 📚 References

- Architecture: `.claude/guides/02-architecture.md`
- Services: `.claude/guides/03-services.md`
- Testing: `.claude/guides/04-testing.md`
- Database: `.claude/guides/06-database.md`
- Common Pitfalls: `.claude/guides/07-common-pitfalls.md`

---

**Last Updated**: 2025-10-27
