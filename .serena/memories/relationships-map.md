# EventosPro - Relationships Map

> **Visual guide to all model relationships in EventosPro**
> Last updated: 2025-11-13

## Complete Relationship Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                       EVENTOSPRO DATA MODEL                       │
└──────────────────────────────────────────────────────────────────┘

                           User
                            │
                            │ hasOne (booker_id)
                            ▼
                          Booker ◄─────────────┐
                            │                  │
                            │ hasMany         │ belongsTo
                            ▼                  │
┌──────────────────────────────────────────────┼──────────────────┐
│                                              │                  │
│                         Gig (CENTRAL)        │                  │
│                           │                  │                  │
│  ┌────────────────────────┼──────────────────┴─────┐            │
│  │                        │                        │            │
│  │ belongsTo              │ hasMany                │            │
│  ▼                        ▼                        ▼            │
│ Artist                 Payment                GigCost           │
│  │                        │                        │            │
│  │                        │ belongsTo              │ belongsTo  │
│  │                        ▼                        ▼            │
│  │                      User                  CostCenter        │
│  │                   (confirmer)                   │            │
│  │                                                 │            │
│  │                                                 │ hasMany    │
│  │                        ┌────────────────────────┘            │
│  │                        │                                     │
│  │                        ▼                                     │
│  │                 AgencyFixedCost                              │
│  │                                                              │
│  └──────────────────────────────────────────────────────────────┘
│                               │
│                               │ hasOne/hasMany
│                               ▼
│                          Settlement
│                               │
│                               │ belongsTo
│                               └─────────────┐
│                                             │
└─────────────────────────────────────────────┘

                    Polymorphic Relationships:

                            Tag
                             │
                    ┌────────┴────────┐
                    │ morphToMany     │ morphToMany
                    ▼                 ▼
                  Gig               Artist


                        ActivityLog
                             │
                    ┌────────┴────────┐
                    │ morphTo         │ morphTo
                    ▼                 ▼
                subject             causer
              (Any Model)           (User)
```

---

## Relationship Details by Model

### User Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `booker` | BelongsTo | Booker | `booker_id` | Optional, for booker portal access |

**Inverse Relationships**:
- Payment → `confirmer` (BelongsTo User)
- GigCost → `confirmer` (BelongsTo User)
- ActivityLog → `causer` (MorphTo User)

---

### Booker Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `gigs` | HasMany | Gig | `booker_id` | All gigs brought by this booker |
| `user` | HasOne | User | `booker_id` | Portal login account |

---

### Artist Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `gigs` | HasMany | Gig | `artist_id` | All gigs for this artist |
| `tags` | MorphToMany | Tag | Polymorphic | Categorization |

---

### Gig Relationships (CENTRAL HUB)

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `artist` | BelongsTo | Artist | `artist_id` | **Required** |
| `booker` | BelongsTo | Booker | `booker_id` | **Required** |
| `payments` | HasMany | Payment | `gig_id` | All payment installments |
| `settlement` | HasOne | Settlement | `gig_id` | Artist payment record |
| `settlements` | HasMany | Settlement | `gig_id` | Alias (backward compat) |
| `gigCosts` | HasMany | GigCost | `gig_id` | All expenses |
| `costs` | HasMany | GigCost | `gig_id` | Alias |
| `tags` | MorphToMany | Tag | Polymorphic | Categorization |

**Eager Loading Pattern**:
```php
Gig::with([
    'artist',
    'booker',
    'payments',
    'gigCosts.costCenter',
    'settlement',
    'tags'
])->get();
```

---

### Payment Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `gig` | BelongsTo | Gig | `gig_id` | **Required** |
| `confirmer` | BelongsTo | User | `confirmed_by` | Who confirmed receipt |

**Cascade Behavior**: Soft deletes with gig

---

### GigCost Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `gig` | BelongsTo | Gig | `gig_id` | **Required** |
| `costCenter` | BelongsTo | CostCenter | `cost_center_id` | **Required** |
| `confirmer` | BelongsTo | User | `confirmed_by` | Who confirmed expense |

**Cascade Behavior**: Soft deletes with gig

---

### Settlement Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `gig` | BelongsTo | Gig | `gig_id` | **Required**, **Unique** |

**Business Rule**: One settlement per gig (HasOne relationship)

---

### CostCenter Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `gigCosts` | HasMany | GigCost | `cost_center_id` | Variable gig expenses |
| `agencyFixedCosts` | HasMany | AgencyFixedCost | `cost_center_id` | Monthly fixed costs |

---

### Tag Relationships (Polymorphic)

| Relationship | Type | Related Model | Pivot Table | Note |
|--------------|------|---------------|-------------|------|
| `gigs` | MorphToMany | Gig | `taggables` | Taggable type: 'App\Models\Gig' |
| `artists` | MorphToMany | Artist | `taggables` | Taggable type: 'App\Models\Artist' |

**Taggables Table Structure**:
```sql
id, tag_id, taggable_type, taggable_id, created_at, updated_at
```

---

### ActivityLog Relationships (Polymorphic)

| Relationship | Type | Related Model | Note |
|--------------|------|---------------|------|
| `subject` | MorphTo | Any Model | The entity that was changed |
| `causer` | MorphTo | User (usually) | Who made the change |

**Subject Types** (can track changes to):
- Gig
- Payment
- GigCost
- Settlement
- Artist
- Booker
- etc.

---

### AgencyFixedCost Relationships

| Relationship | Type | Related Model | Foreign Key | Note |
|--------------|------|---------------|-------------|------|
| `costCenter` | BelongsTo | CostCenter | `cost_center_id` | **Required** |

---

## Relationship Cardinality

### One-to-One Relationships

| Parent | Child | Foreign Key Location | Note |
|--------|-------|---------------------|------|
| Gig | Settlement | Child (Settlement) | One settlement per gig |
| Booker | User | Child (User) | One portal login per booker |

### One-to-Many Relationships

| Parent | Child | Foreign Key | Cascade Delete? |
|--------|-------|-------------|----------------|
| Artist | Gig | `artist_id` | No (soft) |
| Booker | Gig | `booker_id` | No (soft) |
| Gig | Payment | `gig_id` | Yes (soft) |
| Gig | GigCost | `gig_id` | Yes (soft) |
| CostCenter | GigCost | `cost_center_id` | No |
| CostCenter | AgencyFixedCost | `cost_center_id` | No |
| User | Payment (confirmer) | `confirmed_by` | No |
| User | GigCost (confirmer) | `confirmed_by` | No |

### Many-to-Many Relationships (Polymorphic)

| Model 1 | Model 2 | Pivot Table | Sync Method Available? |
|---------|---------|-------------|----------------------|
| Tag | Gig | `taggables` | Yes |
| Tag | Artist | `taggables` | Yes |

---

## Data Flow Patterns

### Creating a New Gig

```
1. User creates Gig
   ├─ Select Artist (artist_id)
   ├─ Select Booker (booker_id)
   └─ Set cache_value, currency, commission details

2. GigObserver fires
   └─ Recalculates financial values via GigFinancialCalculatorService

3. (Optional) Add Payments
   ├─ Create Payment records linked to gig_id
   └─ PaymentSaved event → UpdateGigPaymentStatus listener

4. (Optional) Add GigCosts
   ├─ Create GigCost records linked to gig_id
   ├─ Link to cost_center_id
   └─ GigCostObserver updates gig expenses

5. (When complete) Create Settlement
   └─ Create Settlement record linked to gig_id (unique)
```

### Payment Confirmation Flow

```
Payment
 ├─ User sets received_value_actual
 ├─ User sets received_date_actual
 ├─ confirmed_by = current_user_id
 ├─ confirmed_at = now()
 └─ PaymentSaved event
      └─ UpdateGigPaymentStatus listener
           └─ Updates gig.payment_status
```

### Cost Tracking Flow

```
GigCost
 ├─ User creates cost linked to gig_id
 ├─ Select cost_center_id
 ├─ Set value, currency, is_invoice flag
 └─ GigCostObserver fires
      └─ Updates gig.total_expenses
           └─ GigObserver recalculates financials
```

---

## N+1 Query Prevention

### Critical Eager Loading Patterns

**Gig List with Full Data**:
```php
Gig::with([
    'artist',
    'booker',
    'payments',
    'gigCosts.costCenter',
    'settlement',
    'tags'
])->get();
```

**Payments with Gig and Artist**:
```php
Payment::with([
    'gig.artist',
    'gig.booker',
    'confirmer'
])->get();
```

**Costs with Relations**:
```php
GigCost::with([
    'gig.artist',
    'costCenter',
    'confirmer'
])->get();
```

**Booker Performance Data**:
```php
Booker::with([
    'gigs.artist',
    'gigs.payments',
    'gigs.settlement',
    'user'
])->find($id);
```

---

## Foreign Key Constraints

### Database Level Constraints

| Child Table | Foreign Key | Parent Table | On Delete |
|-------------|-------------|--------------|-----------|
| `users` | `booker_id` | `bookers` | SET NULL |
| `gigs` | `artist_id` | `artists` | RESTRICT |
| `gigs` | `booker_id` | `bookers` | RESTRICT |
| `payments` | `gig_id` | `gigs` | CASCADE |
| `payments` | `confirmed_by` | `users` | SET NULL |
| `gig_costs` | `gig_id` | `gigs` | CASCADE |
| `gig_costs` | `cost_center_id` | `cost_centers` | RESTRICT |
| `gig_costs` | `confirmed_by` | `users` | SET NULL |
| `settlements` | `gig_id` | `gigs` | CASCADE |
| `agency_fixed_costs` | `cost_center_id` | `cost_centers` | RESTRICT |

**Note**: All models use soft deletes, so physical deletions are rare.

---

## Observer Impact on Relationships

### GigObserver

**Triggered When**: Gig saved
**Related Models Affected**:
- Reads from: Payment (for total_received)
- Reads from: GigCost (for total_expenses)
- Updates: Gig itself (recalculated fields)

### GigCostObserver

**Triggered When**: GigCost saved/deleted
**Related Models Affected**:
- Updates: Related Gig (expense totals)
- Triggers: GigObserver (cascade recalculation)

### UpdateGigPaymentStatus Listener

**Triggered When**: PaymentSaved event
**Related Models Affected**:
- Reads from: Payment (all for the gig)
- Updates: Related Gig (payment_status)

---

## Relationship Usage in Services

### GigFinancialCalculatorService

**Uses Relationships**:
```php
$gig->payments  // Calculate total_received_brl
$gig->gigCosts  // Calculate total_expenses_brl
$gig->booker    // Get default_commission_rate
```

### AuditService

**Uses Relationships**:
```php
$gig->payments  // Validate payment totals
$gig->gigCosts  // Validate expense totals
$gig->settlement  // Validate settlement amounts
```

### FinancialProjectionService

**Uses Relationships**:
```php
Gig::with(['payments', 'settlement', 'gigCosts'])
    ->whereBetween('gig_date', [$start, $end])
    ->get();
```

---

## Testing Relationship Creation

### Factory Relationships

```php
// Create Gig with full relationships
$gig = Gig::factory()
    ->for(Artist::factory())
    ->for(Booker::factory())
    ->has(Payment::factory()->count(3))
    ->has(GigCost::factory()->count(2))
    ->has(Settlement::factory())
    ->create();

// Create Payment for existing Gig
$payment = Payment::factory()
    ->for($gig)
    ->for(User::factory(), 'confirmer')
    ->create();

// Create GigCost with CostCenter
$cost = GigCost::factory()
    ->for($gig)
    ->for(CostCenter::factory())
    ->create();
```

---

## Common Relationship Queries

### Get All Overdue Payments with Gig Details

```php
Payment::with(['gig.artist', 'gig.booker'])
    ->whereNull('confirmed_at')
    ->where('due_date', '<', now())
    ->get();
```

### Get Gigs Pending Artist Payment

```php
Gig::with(['artist', 'settlement', 'gigCosts'])
    ->where('artist_payment_status', 'pending')
    ->whereNotNull('settlement_id')
    ->get();
```

### Get Booker Performance Stats

```php
$booker = Booker::with([
    'gigs' => fn($q) => $q->whereBetween('gig_date', [$start, $end]),
    'gigs.payments',
    'gigs.settlement'
])->find($id);
```

### Get Cost Center Usage Report

```php
$costCenter = CostCenter::with([
    'gigCosts' => fn($q) => $q->where('expense_date', '>=', $monthStart),
    'gigCosts.gig.artist',
    'agencyFixedCosts' => fn($q) => $q->active()
])->find($id);
```

---

## Relationship Integrity Rules

### Business Rules Enforced via Relationships

1. **Gig must have Artist and Booker** (required FKs)
2. **Payment must belong to Gig** (required FK)
3. **GigCost must have Gig and CostCenter** (required FKs)
4. **Settlement is unique per Gig** (hasOne relationship)
5. **User can optionally be linked to one Booker** (for portal)
6. **Soft deletes preserve relationship history**

### Orphan Prevention

- Payments cannot exist without a Gig (FK constraint)
- GigCosts cannot exist without a Gig (FK constraint)
- Settlement cannot exist without a Gig (FK constraint)
- Cannot delete CostCenter with active GigCosts (FK RESTRICT)
- Cannot delete Artist/Booker with active Gigs (FK RESTRICT)

---

## Polymorphic Relationship Details

### Taggables

**Table**: `taggables`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | PK |
| `tag_id` | bigint | FK to tags |
| `taggable_type` | string | Model class |
| `taggable_id` | bigint | Model ID |

**Supported Types**:
- `App\Models\Gig`
- `App\Models\Artist`

**Usage**:
```php
$gig->tags()->attach($tagId);
$artist->tags()->sync([$tag1, $tag2]);
```

### ActivityLog

**Polymorphic Fields**:
- `subject_type` + `subject_id` → Any model that changed
- `causer_type` + `causer_id` → User who changed it

**Usage**:
```php
ActivityLog::where('subject_type', 'App\Models\Gig')
    ->where('subject_id', $gigId)
    ->with('causer')
    ->latest()
    ->get();
```

---

## Summary

- **Total Relationships**: 25+
- **Central Hub**: Gig model (8 relationships)
- **Polymorphic**: Tag, ActivityLog
- **One-to-One**: Gig ↔ Settlement, Booker ↔ User
- **One-to-Many**: 9 relationships
- **Many-to-Many**: Tags (polymorphic)
- **Observers**: 2 active (GigObserver, GigCostObserver)
- **Events**: PaymentSaved → UpdateGigPaymentStatus

**Best Practice**: Always use eager loading for related data to prevent N+1 queries.

---

**End of Relationships Map**