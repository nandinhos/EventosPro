# EventosPro - Models Overview

> **Complete reference for all Eloquent models in the EventosPro system**
> Last updated: 2025-11-13

## Models Index

1. [Gig](#gig) - Core business entity
2. [Artist](#artist) - Artist/Performer
3. [Booker](#booker) - Booking agent
4. [Payment](#payment) - Payment tracking
5. [GigCost](#gigcost) - Gig expenses
6. [Settlement](#settlement) - Artist payment settlement
7. [CostCenter](#costcenter) - Cost categorization
8. [User](#user) - System users
9. [Tag](#tag) - Polymorphic tagging
10. [ActivityLog](#activitylog) - Audit trail
11. [AgencyFixedCost](#agencyfixedcost) - Fixed operational costs

---

## Gig

**File**: `app/Models/Gig.php` (428 lines)
**Description**: Central entity representing a booking/event

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties

**IDs & Foreign Keys**:
- `id` (int)
- `artist_id` (int) - FK to artists
- `booker_id` (int) - FK to bookers

**Contract Details**:
- `contract_number` (string)
- `contract_date` (date)
- `gig_date` (date) - **Indexed**
- `location_event_details` (text)

**Financial Fields**:
- `cache_value` (decimal:2) - Gross fee (cachê)
- `currency` (string, 3) - BRL, USD, EUR
- `agency_commission_type` (enum: 'percentage', 'fixed')
- `agency_commission_rate` (decimal:2)
- `agency_commission_value` (decimal:2)
- `booker_commission_type` (enum: 'percentage', 'fixed')
- `booker_commission_rate` (decimal:2)
- `booker_commission_value` (decimal:2)
- `liquid_commission_value` (decimal:2)

**Status Fields** (**All Indexed**):
- `contract_status` (enum: 'pending', 'signed', 'cancelled')
- `payment_status` (enum: 'pending', 'partial', 'paid')
- `artist_payment_status` (enum: 'pending', 'partial', 'paid')
- `booker_payment_status` (enum: 'pending', 'partial', 'paid')

**Metadata**:
- `notes` (text)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
artist(): BelongsTo<Artist>
booker(): BelongsTo<Booker>
payments(): HasMany<Payment>
settlement(): HasOne<Settlement>
settlements(): HasMany<Settlement>  // Alias for backward compatibility
tags(): MorphToMany<Tag>
gigCosts(): HasMany<GigCost>
costs(): HasMany<GigCost>  // Alias
```

### Key Accessors (Virtual Attributes)

**Financial Calculations** (all return `?float`):
- `gross_cash_brl` - Cache value in BRL
- `total_confirmed_expenses_brl` - Sum of confirmed gig costs
- `total_reimbursable_expenses_brl` - Sum of reimbursable expenses
- `calculated_agency_gross_commission_brl` - Agency commission before booker cut
- `calculated_booker_commission_brl` - Booker commission
- `calculated_agency_net_commission_brl` - Net agency commission
- `calculated_artist_net_payout_brl` - Net payment to artist
- `calculated_artist_invoice_value_brl` - Artist invoice value
- `total_received_brl` - Total payments received in BRL
- `cache_value_brl` - Cache value converted to BRL

**Other Accessors**:
- `are_all_costs_confirmed` (bool) - Check if all costs confirmed

### Key Methods

```php
getFinancialCalculator(): GigFinancialCalculatorService
getExchangeRateForCurrency(string $currency): float
getExchangeRateDetails(): array  // Detailed exchange rate info
cacheValueBrlDetails(): array  // Detailed cache value breakdown
```

### Observers
- **GigObserver** - Handles financial recalculations on save

### Fillable Fields
All fields except `id`, timestamps, relationship fields are fillable.

### Casts
```php
'gig_date' => 'date',
'contract_date' => 'date',
'cache_value' => 'decimal:2',
'agency_commission_rate' => 'decimal:2',
'agency_commission_value' => 'decimal:2',
'booker_commission_rate' => 'decimal:2',
'booker_commission_value' => 'decimal:2',
'liquid_commission_value' => 'decimal:2',
```

---

## Artist

**File**: `app/Models/Artist.php` (36 lines)
**Description**: Represents performers/artists

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties
- `id` (int)
- `name` (string)
- `contact_info` (text)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
gigs(): HasMany<Gig>
tags(): MorphToMany<Tag>
```

### Fillable
- `name`, `contact_info`

---

## Booker

**File**: `app/Models/Booker.php` (37 lines)
**Description**: Booking agents who bring in gigs

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties
- `id` (int)
- `name` (string)
- `default_commission_rate` (decimal:2)
- `contact_info` (text)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
gigs(): HasMany<Gig>
user(): HasOne<User>  // Portal login
```

### Fillable
- `name`, `default_commission_rate`, `contact_info`

### Casts
```php
'default_commission_rate' => 'decimal:2',
```

---

## Payment

**File**: `app/Models/Payment.php` (161 lines)
**Description**: Tracks incoming payments for gigs

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties

**Core Fields**:
- `id` (int)
- `gig_id` (int) - FK to gigs
- `description` (string)
- `due_value` (decimal:2) - Expected amount
- `due_date` (date)
- `currency` (string, 3)
- `exchange_rate` (decimal:6)

**Received Details**:
- `received_value_actual` (decimal:2) - Actually received
- `received_date_actual` (date)

**Confirmation**:
- `confirmed_at` (datetime)
- `confirmed_by` (int) - FK to users

**Metadata**:
- `notes` (text)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
gig(): BelongsTo<Gig>
confirmer(): BelongsTo<User>
```

### Key Accessors

```php
inferred_status: string  // 'pending', 'overdue', 'paid', 'partial'
status_color: string  // 'gray', 'red', 'green', 'yellow'
due_value_brl: ?float  // Due value in BRL
received_value_actual_brl: ?float  // Received in BRL
is_paid: bool  // Fully paid check
```

### Fillable
All fields except `id`, timestamps, relationship fields.

### Casts
```php
'due_date' => 'date',
'received_date_actual' => 'date',
'confirmed_at' => 'datetime',
'due_value' => 'decimal:2',
'received_value_actual' => 'decimal:2',
'exchange_rate' => 'decimal:6',
```

### Events
- **PaymentSaved** event fired on save
- Triggers **UpdateGigPaymentStatus** listener

---

## GigCost

**File**: `app/Models/GigCost.php` (84 lines)
**Description**: Expenses related to a specific gig

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties

**Core Fields**:
- `id` (int)
- `gig_id` (int) - FK to gigs
- `cost_center_id` (int) - FK to cost_centers
- `description` (string)
- `value` (decimal:2)
- `currency` (string, 3)
- `expense_date` (date)
- `is_confirmed` (boolean) - Expense confirmed
- `is_invoice` (boolean) - Reimbursable to artist

**Confirmation**:
- `confirmed_at` (datetime)
- `confirmed_by` (int) - FK to users

**Metadata**:
- `notes` (text)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
gig(): BelongsTo<Gig>
costCenter(): BelongsTo<CostCenter>
confirmer(): BelongsTo<User>
```

### Key Accessors

```php
value_brl: ?float  // Value in BRL using exchange rate
```

### Observers
- **GigCostObserver** - Updates gig expenses on save/delete

### Fillable
All fields except `id`, timestamps, relationship fields.

### Casts
```php
'value' => 'decimal:2',
'expense_date' => 'date',
'is_confirmed' => 'boolean',
'is_invoice' => 'boolean',
```

---

## Settlement

**File**: `app/Models/Settlement.php` (53 lines)
**Description**: Records of payments made to artists

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties

**Core Fields**:
- `id` (int)
- `gig_id` (int) - FK to gigs
- `settlement_date` (date)
- `artist_payment_value` (decimal:2)
- `booker_commission_value_paid` (decimal:2)

**Proof of Payment**:
- `artist_payment_proof` (string) - File path
- `booker_payment_proof` (string) - File path

**Metadata**:
- `notes` (text)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
gig(): BelongsTo<Gig>
```

### Fillable
All fields except `id`, timestamps.

### Casts
```php
'settlement_date' => 'date',
'artist_payment_value' => 'decimal:2',
'booker_commission_value_paid' => 'decimal:2',
```

---

## CostCenter

**File**: `app/Models/CostCenter.php` (47 lines)
**Description**: Cost categorization (e.g., "Travel", "Equipment")

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties
- `id` (int)
- `name` (string)
- `description` (text)
- `is_active` (boolean)
- `color` (string) - Hex color for UI
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
gigCosts(): HasMany<GigCost>
```

### Scopes

```php
scopeActive(Builder $query): Builder  // Only active cost centers
scopeInactive(Builder $query): Builder  // Only inactive
```

### Fillable
- `name`, `description`, `is_active`, `color`

### Casts
```php
'is_active' => 'boolean',
```

---

## User

**File**: `app/Models/User.php` (70 lines)
**Description**: System users (admins, bookers)

### Traits
- `HasFactory`
- `Notifiable`
- `SoftDeletes`
- `HasRoles` (Spatie Permission)

### Properties
- `id` (int)
- `name` (string)
- `email` (string) - Unique
- `password` (string) - Hashed
- `booker_id` (int, nullable) - FK to bookers
- `email_verified_at` (datetime)
- `remember_token` (string)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
booker(): BelongsTo<Booker>
```

### Fillable
- `name`, `email`, `password`, `booker_id`

### Hidden
- `password`, `remember_token`

### Casts
```php
'email_verified_at' => 'datetime',
'password' => 'hashed',
```

---

## Tag

**File**: `app/Models/Tag.php` (47 lines)
**Description**: Polymorphic tags for categorization

### Traits
- `HasFactory`

### Properties
- `id` (int)
- `name` (string)
- `slug` (string)
- `color` (string) - Hex color
- `created_at`, `updated_at`

### Relationships (Polymorphic)

```php
gigs(): MorphToMany<Gig>
artists(): MorphToMany<Artist>
```

### Fillable
- `name`, `slug`, `color`

---

## ActivityLog

**File**: `app/Models/ActivityLog.php` (75 lines)
**Description**: Audit trail for data changes

### Traits
- None (custom timestamps handling)

### Properties
- `id` (int)
- `log_name` (string) - Category
- `description` (string) - Action description
- `subject_type` (string) - Polymorphic type
- `subject_id` (int) - Polymorphic ID
- `causer_type` (string) - Who caused it
- `causer_id` (int)
- `properties` (json) - Additional data
- `created_at` (only)

### Special Configuration
```php
public $timestamps = false;
const UPDATED_AT = null;  // Only tracks creation
```

### Relationships (Polymorphic)

```php
subject(): MorphTo  // The changed entity
causer(): MorphTo  // Who made the change
```

### Fillable
All fields except `id`, `created_at`.

### Casts
```php
'properties' => 'array',
'created_at' => 'datetime',
```

---

## AgencyFixedCost

**File**: `app/Models/AgencyFixedCost.php` (56 lines)
**Description**: Monthly fixed operational costs

### Traits
- `HasFactory`
- `SoftDeletes`

### Properties
- `id` (int)
- `cost_center_id` (int) - FK to cost_centers
- `description` (string)
- `monthly_value` (decimal:2)
- `month_year` (date) - First day of month
- `is_active` (boolean)
- `created_at`, `updated_at`, `deleted_at`

### Relationships

```php
costCenter(): BelongsTo<CostCenter>
```

### Scopes

```php
scopeActive(Builder $query): Builder
scopeForMonth(Builder $query, Carbon $date): Builder
scopeByCategory(Builder $query, int $costCenterId): Builder
```

### Fillable
- `cost_center_id`, `description`, `monthly_value`, `month_year`, `is_active`

### Casts
```php
'monthly_value' => 'decimal:2',
'month_year' => 'date',
'is_active' => 'boolean',
```

---

## Database Conventions

### Universal Patterns

1. **Soft Deletes**: All models use `deleted_at`
2. **Timestamps**: All models have `created_at`, `updated_at`
3. **Money Fields**: `decimal:2` precision
4. **Exchange Rates**: `decimal:6` precision
5. **Dates**: Cast to Carbon instances
6. **Foreign Keys**: Named `{model}_id`
7. **Indexing**: Status fields and dates are indexed

### Naming Conventions

- **Tables**: Plural snake_case (e.g., `gig_costs`)
- **Models**: Singular PascalCase (e.g., `GigCost`)
- **Relationships**: camelCase methods
- **Accessors**: `get{Attribute}Attribute()`
- **Scopes**: `scope{Name}()`

### Multi-Currency Support

Models with currency fields:
- **Gig**: `cache_value` + `currency`
- **Payment**: `due_value` + `currency` + `exchange_rate`
- **GigCost**: `value` + `currency`

All have `*_brl` accessors for BRL conversion.

---

## Key Relationships Map

```
User
 └─ booker (hasOne)
      └─ Booker
           ├─ gigs (hasMany)
           └─ user (belongsTo)

Artist
 ├─ gigs (hasMany)
 └─ tags (morphToMany)

Gig (CENTRAL ENTITY)
 ├─ artist (belongsTo)
 ├─ booker (belongsTo)
 ├─ payments (hasMany)
 │    └─ Payment
 │         ├─ gig (belongsTo)
 │         └─ confirmer (belongsTo User)
 ├─ settlement (hasOne)
 │    └─ Settlement
 │         └─ gig (belongsTo)
 ├─ gigCosts (hasMany)
 │    └─ GigCost
 │         ├─ gig (belongsTo)
 │         ├─ costCenter (belongsTo)
 │         └─ confirmer (belongsTo User)
 └─ tags (morphToMany)

CostCenter
 ├─ gigCosts (hasMany)
 └─ agencyFixedCosts (hasMany)

Tag (Polymorphic)
 ├─ gigs (morphToMany)
 └─ artists (morphToMany)

ActivityLog (Audit)
 ├─ subject (morphTo) - Any model
 └─ causer (morphTo) - User
```

---

## Observer Pattern Usage

### GigObserver
- **When**: Gig model saved
- **Action**: Recalculate financial values using GigFinancialCalculatorService
- **File**: `app/Observers/GigObserver.php`

### GigCostObserver
- **When**: GigCost saved/deleted
- **Action**: Update related Gig expenses totals
- **File**: `app/Observers/GigCostObserver.php`

---

## Factory Pattern

All models have corresponding factories in `database/factories/`:
- `GigFactory` - Complex with states
- `ArtistFactory`
- `BookerFactory`
- `PaymentFactory`
- `GigCostFactory`
- `SettlementFactory`
- `CostCenterFactory`
- `UserFactory`
- `TagFactory`
- `ActivityLogFactory`
- `AgencyFixedCostFactory`

---

## Testing Usage

When testing, always use factories:

```php
$gig = Gig::factory()->create([
    'cache_value' => 10000,
    'currency' => 'BRL',
]);

$artist = Artist::factory()->create();
$payment = Payment::factory()->for($gig)->create();
```

---

## Common Queries

### Gigs with Complete Data
```php
Gig::with(['artist', 'booker', 'payments', 'gigCosts.costCenter', 'settlement'])
    ->whereDate('gig_date', '>=', now())
    ->get();
```

### Active Cost Centers
```php
CostCenter::active()->get();
```

### Overdue Payments
```php
Payment::where('due_date', '<', now())
    ->whereNull('confirmed_at')
    ->with('gig.artist')
    ->get();
```

### Monthly Fixed Costs
```php
AgencyFixedCost::active()
    ->forMonth(now())
    ->with('costCenter')
    ->get();
```

---

**End of Models Overview**