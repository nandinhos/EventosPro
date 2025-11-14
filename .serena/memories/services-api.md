# EventosPro - Services API Reference

> **Complete API reference for all business logic services in EventosPro**
> Last updated: 2025-11-13

## Service Categories

1. [Core Financial Services](#core-financial-services) - Financial calculations
2. [Reporting Services](#reporting-services) - Report generation
3. [Projection Services](#projection-services) - Cash flow & DRE
4. [Validation Services](#validation-services) - Business rule validation
5. [Artist/Booker Services](#artistbooker-services) - Performance metrics
6. [Audit Services](#audit-services) - Data integrity
7. [Infrastructure Services](#infrastructure-services) - Exchange rates, cache, etc.
8. [User Management](#user-management) - User operations

---

## Core Financial Services

### GigFinancialCalculatorService

**File**: `app/Services/GigFinancialCalculatorService.php` (237 lines)
**Purpose**: **CORE SERVICE** - Single source of truth for all gig financial calculations

**Dependency**: None (stateless)

#### Public API

```php
// Calculate gross cash (cachê) in BRL
calculateGrossCashBrl(Gig $gig): ?float

// Calculate agency gross commission (before booker cut)
calculateAgencyGrossCommissionBrl(Gig $gig): ?float

// Calculate net payment to artist (gross - expenses + reimbursables)
calculateArtistNetPayoutBrl(Gig $gig): ?float

// Calculate booker commission
calculateBookerCommissionBrl(Gig $gig): ?float

// Calculate agency net commission (after booker cut)
calculateAgencyNetCommissionBrl(Gig $gig): ?float

// Calculate total confirmed expenses
calculateTotalConfirmedExpensesBrl(Gig $gig): float

// Calculate total reimbursable expenses
calculateTotalReimbursableExpensesBrl(Gig $gig): float

// Calculate artist invoice value (net payout + reimbursables)
calculateArtistInvoiceValueBrl(Gig $gig): ?float

// Calculate total received in original currency
calculateTotalReceivedInOriginalCurrency(Gig $gig): float

// Calculate total receivable in original currency
calculateTotalReceivableInOriginalCurrency(Gig $gig): float

// Calculate pending balance in original currency
calculatePendingBalanceInOriginalCurrency(Gig $gig): float
```

**Critical Business Rules**:
- All calculations return values in BRL
- Handles multi-currency conversion automatically
- Uses `GigCost` with `is_confirmed=true` for expenses
- Uses `GigCost` with `is_invoice=true` for reimbursables
- Commission calculation respects `commission_type` (percentage|fixed)

**Usage Example**:
```php
$calculator = new GigFinancialCalculatorService();
$grossCash = $calculator->calculateGrossCashBrl($gig);
$netCommission = $calculator->calculateAgencyNetCommissionBrl($gig);
```

**Dependencies**: Always inject via constructor in other services

---

## Reporting Services

### FinancialReportService

**File**: `app/Services/FinancialReportService.php` (1034 lines)
**Purpose**: Generate comprehensive financial reports with multiple views

**Dependency**: `GigFinancialCalculatorService`

#### Configuration

```php
__construct(GigFinancialCalculatorService $calculator)
setFilters(Carbon $startDate, Carbon $endDate, array $filters = []): self
setDefaultPeriod(): void  // Sets current year period
```

#### Public API - Overview Reports

```php
// Summary metrics for period
getOverviewSummary(): array
// Returns: [
//   'total_revenue' => float,
//   'total_expenses' => float,
//   'total_commission' => float,
//   'total_gigs' => int,
//   'net_profit' => float,
//   'profit_margin' => float
// ]

// Overview table data with gig details
getOverviewTableData(): Collection
```

#### Public API - Profitability Reports

```php
// Profitability summary
getProfitabilitySummary(): array
// Returns profitability metrics and margins

// Profitability table data
getProfitabilityTableData(): Collection

// Profitability analysis (detailed)
getProfitabilityAnalysisData(): array
// Returns:
//   - monthly_profitability
//   - artist_profitability
//   - booker_profitability
//   - cost_center_analysis

// Sales profitability data
getSalesProfitabilityData(): array
```

#### Public API - Cash Flow Reports

```php
// Cash flow summary
getCashflowSummary(): array
// Returns: receivable, payable, balance

// Cash flow table data with payment details
getCashflowTableData(): array
```

#### Public API - Commissions Reports

```php
// Commission summary (agency + booker)
getCommissionsSummary(): array

// Commission table data
getCommissionsTableData(): Collection

// Grouped commissions by booker
getGroupedCommissionsData(): array

// Grouped artist commissions
getGroupedArtistCommissionsData(): array
```

#### Public API - Expenses Reports

```php
// Expenses table data
getExpensesTableData(): Collection

// Detailed expenses analysis
getDetailedExpenses(): Collection

// Grouped expenses by cost center
getGroupedExpensesData(): array
```

#### Public API - Combined Reports

```php
// Complete financial report (all sections)
getFinancialReportData(): array
// Returns comprehensive data for all report sections

// Detailed performance data
getDetailedPerformanceData(): array

// Overview data (consolidated)
getOverviewData(): array
```

**Filters Supported**:
- `artist_id`: Filter by artist
- `booker_id`: Filter by booker
- `contract_status`: Filter by status
- Date range via `setFilters()`

---

### AuditReportService

**File**: `app/Services/AuditReportService.php` (419 lines)
**Purpose**: Generate audit reports and track data quality

**Dependency**: None

#### Public API

```php
// Get list of available audit types
getAvailableAudits(): array
// Returns: [
//   ['key' => 'currency', 'name' => 'Currency Audit', ...],
//   ['key' => 'payments', 'name' => 'Payments Audit', ...],
//   ...
// ]

// Get latest audit report
getLatestReport(string $auditType): ?array

// Get all historical reports
getAllReports(string $auditType): Collection

// Calculate data health score (0-100)
calculateHealthScore(array $report): array
// Returns: ['score' => int, 'grade' => string, 'status' => string]

// Generate consolidated report (all audits)
generateConsolidatedReport(): array
// Returns comprehensive health status

// Clean old audit reports (retention management)
cleanOldReports(int $daysToKeep = 90): int

// Find specific issues by type
findIssuesByType(string $auditType, string $issueType): Collection

// Get audit trends over time
getTrends(string $auditType, int $days = 30): array
```

**Available Audit Types**:
- `currency` - Currency validation
- `payments` - Payment integrity
- `costs` - Cost validation
- `settlements` - Settlement verification
- `business_rules` - Business rule enforcement
- `duplicates` - Duplicate detection

---

## Projection Services

### FinancialProjectionService

**File**: `app/Services/FinancialProjectionService.php` (407 lines)
**Purpose**: Cash flow projections and strategic financial planning

**Dependencies**: 
- `GigFinancialCalculatorService`
- `ProjectionQueryBuilder`
- `ProjectionMetricsService`

#### Configuration

```php
__construct(
    GigFinancialCalculatorService $calculatorService,
    ProjectionQueryBuilder $queryBuilder,
    ProjectionMetricsService $metricsService
)

setPeriod(Carbon $startDate, Carbon $endDate): self
```

#### Public API - Accounts Receivable

```php
// Get total accounts receivable
getAccountsReceivable(): float

// Get upcoming client payments
getUpcomingClientPayments(): Collection
```

#### Public API - Accounts Payable

```php
// Get accounts payable to artists
getAccountsPayableArtists(): float

// Get accounts payable to bookers
getAccountsPayableBookers(): float

// Get accounts payable for expenses
getAccountsPayableExpenses(): float

// Get total accounts payable
getTotalAccountsPayable(): float

// Get upcoming internal payments
getUpcomingInternalPayments(): array
```

#### Public API - Expense Projections

```php
// Get projected expenses by cost center
getProjectedExpensesByCostCenter(): array
// Returns breakdown by cost center with:
//   - gig_expenses (variable)
//   - fixed_costs (monthly)
//   - total_projected
```

#### Public API - Cash Flow

```php
// Get projected cash flow
getProjectedCashFlow(): array
// Returns: [
//   'accounts_receivable' => float,
//   'accounts_payable' => float,
//   'projected_balance' => float
// ]
```

#### Public API - Analyses

```php
// Get executive summary
getExecutiveSummary(): array

// Get overdue analysis
getOverdueAnalysis(): array

// Get future events analysis
getFutureEventsAnalysis(): array

// Get comparative period analysis
getComparativePeriodAnalysis(Carbon $compareStartDate, Carbon $compareEndDate): array

// Get global metrics
getGlobalMetrics(): array

// Get upcoming payments
getUpcomingPayments(): Collection
```

#### Public API - Cache Management

```php
// Clear all projection caches
clearCache(): void
```

---

### CashFlowProjectionService

**File**: `app/Services/CashFlowProjectionService.php` (548 lines)
**Purpose**: Detailed monthly cash flow projections

**Dependencies**:
- `DreProjectionService`
- `GigFinancialCalculatorService`

#### Configuration

```php
__construct(
    DreProjectionService $dreService,
    GigFinancialCalculatorService $gigCalculator
)

setPeriod(Carbon $startDate, Carbon $endDate): self
getGigCalculator(): GigFinancialCalculatorService
```

#### Public API - Monthly Calculations

```php
// Calculate monthly cash inflows
calculateMonthlyInflows(): array
// Returns monthly breakdown of incoming cash

// Calculate monthly cash outflows
calculateMonthlyOutflows(): array
// Returns monthly breakdown of outgoing cash

// Calculate net monthly cash flow
calculateMonthlyCashFlow(): array
// Returns: inflows - outflows per month
```

#### Public API - Total Calculations

```php
// Calculate total cash flow for period
calculateTotalCashFlow(): array
// Returns: [
//   'total_inflows' => float,
//   'total_outflows' => float,
//   'net_cash_flow' => float
// ]

// Get executive summary
getExecutiveSummary(): array
```

#### Public API - Accounts Receivable

```php
// Calculate detailed accounts receivable
calculateAccountsReceivable(): array
// Returns breakdown by status and age
```

#### Public API - DRE Comparison

```php
// Compare cash flow with DRE (accrual vs cash basis)
compareWithDre(): array
```

#### Public API - Payment Details

```php
// Calculate artist payment details
calculateArtistPaymentDetails(): array
// Returns: pending, scheduled, overdue

// Calculate booker commission details
calculateBookerCommissionDetails(): array
// Returns: pending, paid, scheduled

// Calculate projected expenses
calculateProjectedExpenses(): array
// Returns expense breakdown by type
```

---

### DreProjectionService

**File**: `app/Services/DreProjectionService.php` (275 lines)
**Purpose**: DRE (Income Statement) projections on accrual basis

**Dependency**: `GigFinancialCalculatorService`

#### Configuration

```php
__construct(GigFinancialCalculatorService $gigCalculator)
setPeriod(Carbon $startDate, Carbon $endDate): self
```

#### Public API - Revenue Calculations

```php
// Calculate net agency revenue (accrual basis)
calculateReceitaLiquidaRealAgencia(): float
```

#### Public API - Event Metrics

```php
// Get event metrics (realized vs future)
getEventMetrics(): array
// Returns: [
//   'total_events' => int,
//   'realized_events' => int,
//   'future_events' => int
// ]

// Get events grouped by month
getEventsGroupedByMonth(): Collection
```

#### Public API - DRE Calculations

```php
// Calculate monthly DRE (by competence month)
calculateMonthlyDre(): array
// Returns monthly breakdown of:
//   - receita_bruta
//   - comissao_bruta_agencia
//   - comissao_booker
//   - comissao_liquida_agencia
//   - custos_fixos
//   - lucro_liquido
//   - margem_liquida

// Calculate total DRE for period
calculateTotalDre(): array
// Returns aggregated DRE metrics
```

#### Public API - Analysis

```php
// Calculate average ticket (gross fee per gig)
calculateTicketMedio(): float

// Calculate break-even point
calculateBreakEvenPoint(): array
// Returns: [
//   'break_even_revenue' => float,
//   'events_needed' => int,
//   'current_margin' => float
// ]

// Get executive summary
getExecutiveSummary(): array
```

#### Private Helpers

```php
// Get fixed costs for specific month
getFixedCostsForMonth(Carbon $month): float
```

---

## Validation Services

### CommissionPaymentValidationService

**File**: `app/Services/CommissionPaymentValidationService.php` (167 lines)
**Purpose**: Validate commission and artist payment eligibility

**Dependency**: None

#### Public API

```php
// Validate booker commission payment eligibility
validateBookerCommissionPayment(Gig $gig): array
// Returns: [
//   'can_pay' => bool,
//   'reasons' => array,
//   'missing_amount' => float,
//   'total_due' => float
// ]

// Validate artist payment eligibility
validateArtistPayment(Gig $gig): array
// Similar return structure

// Validate batch booker commission payment
validateBatchPayment(array $gigIds, Booker $booker): array
// Returns: [
//   'can_pay_all' => bool,
//   'valid_gigs' => array,
//   'invalid_gigs' => array,
//   'total_commission' => float,
//   'exceptions' => array
// ]

// Validate batch artist payment
validateBatchArtistPayment(array $gigIds): array
```

#### Public API - Exceptions

```php
// Check if gig has payment exception
hasPaymentException(Gig $gig): bool

// Create payment exception for partial payments
createPaymentException(Gig $gig, string $reason, ?float $allowedAmount = null): array
```

**Validation Rules**:
- Booker commission: Requires gig payments received >= cache_value
- Artist payment: Checks payment status and confirmed costs
- Supports payment exceptions for special cases

---

### AuditService

**File**: `app/Services/AuditService.php` (299 lines)
**Purpose**: Data validation and integrity checks

**Dependency**: `GigFinancialCalculatorService`

#### Configuration

```php
__construct(GigFinancialCalculatorService $financialCalculator)
```

#### Public API

```php
// Calculate bulk audit data for multiple gigs
calculateBulkAuditData(Collection $gigs): Collection

// Calculate audit data for single gig
calculateGigAuditData(Gig $gig): array
// Returns: [
//   'expected' => [...],  // Expected values
//   'actual' => [...],    // Actual DB values
//   'divergences' => [...], // Differences found
//   'severity' => string,  // 'none', 'low', 'medium', 'high', 'critical'
//   'observations' => array
// ]

// Calculate total paid
calculateTotalPaid(Gig $gig): float

// Calculate total pending
calculateTotalPending(Gig $gig): float

// Perform detailed analysis
performDetailedAnalysis(array $expected, array $actual): array

// Generate automatic observations
generateAutomaticObservations(array $divergences, array $expected, array $actual): array

// Classify divergence severity
classifyDivergence(array $divergences): string

// Get audit data for errors
getErrorAuditData(Gig $gig, \Exception $e): array

// Generate consolidated report
generateConsolidatedReport(Collection $auditResults): array

// Validate gig integrity
validateGigIntegrity(Gig $gig): array
// Returns: [
//   'is_valid' => bool,
//   'errors' => array,
//   'warnings' => array
// ]
```

**Validation Checks**:
- Financial calculation accuracy
- Payment totals vs expected
- Currency conversion accuracy
- Commission calculations
- Expense totals

---

## Artist/Booker Services

### ArtistFinancialsService

**File**: `app/Services/ArtistFinancialsService.php` (58 lines)
**Purpose**: Calculate artist financial metrics

**Dependency**: `GigFinancialCalculatorService`

#### Public API

```php
__construct(GigFinancialCalculatorService $gigCalculator)

// Get complete financial metrics for artist
getFinancialMetrics(
    int $artistId,
    ?Carbon $startDate = null,
    ?Carbon $endDate = null
): array
// Returns: [
//   'total_gigs' => int,
//   'total_gross_revenue_brl' => float,
//   'total_net_payout_brl' => float,
//   'total_expenses_brl' => float,
//   'total_reimbursables_brl' => float,
//   'average_gross_per_gig' => float,
//   'average_net_per_gig' => float
// ]
```

---

### BookerFinancialsService

**File**: `app/Services/BookerFinancialsService.php` (253 lines)
**Purpose**: Calculate booker performance metrics and commissions

**Dependency**: `GigFinancialCalculatorService`

#### Public API

```php
__construct(GigFinancialCalculatorService $gigCalculator)

// Get sales KPIs
getSalesKpis(int $bookerId, Carbon $startDate, Carbon $endDate): array
// Returns: [
//   'total_gigs' => int,
//   'total_sales_brl' => float,
//   'average_ticket_brl' => float
// ]

// Get commission KPIs
getCommissionKpis(int $bookerId, Carbon $startDate, Carbon $endDate): array
// Returns: [
//   'total_commission_brl' => float,
//   'paid_commission_brl' => float,
//   'pending_commission_brl' => float
// ]

// Get commission chart data (monthly breakdown)
getCommissionChartData(int $bookerId, Carbon $startDate, Carbon $endDate): array

// Get top artists for booker
getTopArtists(int $bookerId, Carbon $startDate, Carbon $endDate, int $limit = 5): Collection

// Get recent gigs
getRecentGigs(int $bookerId, int $limit = 10): Collection

// Get gigs for period
getGigsForPeriod(int $bookerId, Carbon $startDate, Carbon $endDate): Collection

// Get realized events (past gigs)
getRealizedEvents(int $bookerId, Carbon $startDate, Carbon $endDate): array

// Get future events (upcoming gigs)
getFutureEvents(int $bookerId, Carbon $startDate, Carbon $endDate): array
```

#### Private Helpers

```php
// Check if commission can be paid
canPayCommission(Gig $gig): bool

// Check if gig has payment exception
isPaymentException(Gig $gig): bool
```

---

## Infrastructure Services

### ExchangeRateService

**File**: `app/Services/ExchangeRateService.php` (212 lines)
**Purpose**: Currency conversion and exchange rate management

**Dependency**: None (uses external API + cache)

#### Constants

```php
SUPPORTED_CURRENCIES = ['BRL', 'USD', 'EUR']
CACHE_TTL = 3600  // 1 hour
```

#### Public API

```php
// Get exchange rate for currency to BRL
getExchangeRate(string $currency, ?Carbon $date = null): float
// Returns rate, uses cache, falls back to external API

// Get multiple rates at once
getMultipleRates(array $currencies, ?Carbon $date = null): array

// Convert amount to BRL
convertToBRL(float $amount, string $currency, ?Carbon $date = null): float

// Clear exchange rate cache
clearCache(?string $currency = null, ?Carbon $date = null): void

// Check if currency is supported
isSupportedCurrency(string $currency): bool

// Get list of supported currencies
getSupportedCurrencies(): array
```

#### Private Methods

```php
// Fetch from external API (awesomeapi.com.br)
fetchFromExternalAPI(string $currency, Carbon $date): float

// Get default/fallback rate
getDefaultRate(string $currency): float

// Get cache key
getCacheKey(string $currency, string $date): string
```

**External API**: Uses `https://economia.awesomeapi.com.br/json/daily/{CURRENCY}-BRL`

**Fallback Rates** (if API fails):
- USD: 5.00
- EUR: 5.50
- BRL: 1.00

---

### ProjectionQueryBuilder

**File**: `app/Services/ProjectionQueryBuilder.php` (254 lines)
**Purpose**: Optimized queries for projection calculations

**Dependency**: None (static methods)

#### Public API

```php
// Query pending payments within period
static pendingPaymentsQuery(Carbon $startDate, Carbon $endDate): Builder

// Query pending gigs (for artist/booker payables)
static pendingGigsQuery(Carbon $startDate, Carbon $endDate): Builder

// Query pending expenses
static pendingExpensesQuery(Carbon $startDate, Carbon $endDate): Builder

// Query overdue payments
static overduePaymentsQuery(?Carbon $asOf = null): Builder

// Query future events
static futureEventsQuery(Carbon $fromDate): Builder

// Fetch global projection data (all time)
static fetchGlobalProjectionData(): array

// Fetch period projection data
static fetchPeriodProjectionData(Carbon $startDate, Carbon $endDate): array
```

**Query Optimizations**:
- Eager loads all necessary relationships
- Includes soft-deleted check
- Pre-filters by date ranges
- Uses indexes on status columns

---

### ProjectionCacheService

**File**: `app/Services/ProjectionCacheService.php` (199 lines)
**Purpose**: Cache management for projection data

**Dependency**: None (uses Laravel Cache)

#### Cache TTLs

```php
TTL_STRATEGIC = 21600    // 6 hours
TTL_OPERATIONAL = 3600   // 1 hour
TTL_VOLATILE = 300       // 5 minutes
CACHE_PREFIX = 'projection:'
```

#### Public API - Remember (Get or Set)

```php
// Cache strategic balance (6h TTL)
static rememberStrategicBalance(Closure $callback): mixed

// Cache accounts receivable (1h TTL)
static rememberAccountsReceivable(string $key, Closure $callback): mixed

// Cache gig expenses (1h TTL)
static rememberGigExpenses(int $gigId, Closure $callback): mixed

// Cache dashboard data (5min TTL)
static rememberDashboardData(string $dashboardKey, Closure $callback): mixed

// Generic remember with custom TTL
static remember(string $key, int $ttl, Closure $callback): mixed
```

#### Public API - Clear Cache

```php
// Clear all projection caches
static clearAll(): void

// Clear strategic balance cache
static clearStrategicBalance(): void

// Clear accounts receivable cache
static clearAccountsReceivable(string $key): void

// Clear gig expenses cache
static clearGigExpenses(int $gigId): void

// Clear dashboard data cache
static clearDashboardData(string $dashboardKey): void

// Clear all dashboard caches
static clearAllDashboards(): void
```

#### Public API - Introspection

```php
// Get list of known cache keys
static getKnownCacheKeys(): array

// Get TTL configuration
static getTTLConfig(): array
```

---

### ProjectionMetricsService

**File**: `app/Services/ProjectionMetricsService.php` (265 lines)
**Purpose**: Calculate strategic financial metrics

**Dependency**: None (static methods)

#### Public API

```php
// Calculate liquidity index
static calculateLiquidityIndex(float $receivable, float $payable): array
// Returns: ['index' => float, 'status' => string, 'interpretation' => string]

// Calculate operational margin
static calculateOperationalMargin(float $revenue, float $costs): array
// Returns: ['margin' => float, 'status' => string]

// Calculate commitment rate (expenses vs revenue)
static calculateCommitmentRate(float $expenses, float $revenue): array
// Returns: ['rate' => float, 'status' => string]

// Assess overall risk level
static assessRiskLevel(array $metrics): array
// Returns: ['level' => string, 'score' => int, 'recommendations' => array]

// Calculate overdue analysis
static calculateOverdueAnalysis(Collection $overduePayments): array

// Calculate future events analysis
static calculateFutureEventsAnalysis(Collection $futureGigs): array

// Calculate comparative analysis between periods
static calculateComparativeAnalysis(array $currentData, array $previousData): array

// Build executive summary
static buildExecutiveSummary(array $data): array
```

**Risk Levels**: 'low', 'medium', 'high', 'critical'

---

### DashboardService

**File**: `app/Services/DashboardService.php` (220 lines)
**Purpose**: Aggregate dashboard data with KPIs

**Dependency**: `GigFinancialCalculatorService`

#### Configuration

```php
__construct(GigFinancialCalculatorService $gigCalculator)

setFilters(array $filters): self
// Filters: start_date, end_date, artist_id, booker_id

setDefaultPeriod(): void
// Sets to current year

getFirstAndLastMonth(): array
// Returns available date range from data
```

#### Public API

```php
// Get complete dashboard data
getDashboardData(): array
// Returns: [
//   'summary' => [...],
//   'charts' => [...],
//   'tables' => [...],
//   'kpis' => [...]
// ]

// Prepare monthly revenue chart data
prepareMonthlyRevenueChartData(Collection $gigs): array
```

---

## User Management

### UserManagementService

**File**: `app/Services/UserManagementService.php` (179 lines)
**Purpose**: User creation, update, deletion with role management

**Dependency**: None (uses Spatie Permission package)

#### Public API

```php
// Create new user with role assignment
createUser(array $data): User
// $data: [
//   'name' => string,
//   'email' => string,
//   'password' => string,
//   'role' => string,  // 'admin' or 'booker'
//   'booker_id' => ?int  // Required if role is 'booker'
// ]
// Returns: User with assigned role

// Update existing user
updateUser(User $user, array $data): User
// $data: same as createUser
// Handles role changes and booker linking

// Delete user (soft delete)
deleteUser(User $user): bool
// Validates deletion safety (no critical dependencies)
// Removes role assignments
```

**Validation Rules**:
- Booker role requires valid `booker_id`
- Email must be unique
- Password is hashed automatically
- Role validation (only 'admin' or 'booker' allowed)

---

## Service Dependency Graph

```
GigFinancialCalculatorService (CORE - No dependencies)
    ├─ AuditService
    ├─ FinancialReportService
    ├─ DashboardService
    ├─ ArtistFinancialsService
    ├─ BookerFinancialsService
    ├─ CashFlowProjectionService
    ├─ DreProjectionService
    └─ FinancialProjectionService

DreProjectionService
    └─ CashFlowProjectionService

ProjectionQueryBuilder (Static - No dependencies)
    └─ FinancialProjectionService

ProjectionMetricsService (Static - No dependencies)
    └─ FinancialProjectionService

ExchangeRateService (Independent)

ProjectionCacheService (Static - No dependencies)

CommissionPaymentValidationService (Independent)

UserManagementService (Independent)

AuditReportService (Independent)
```

---

## Service Usage Patterns

### Always Inject via Constructor

```php
class GigController extends Controller
{
    public function __construct(
        private GigFinancialCalculatorService $calculator,
        private AuditService $auditService
    ) {}
}
```

### Use Facades for Static Services

```php
use App\Services\ProjectionCacheService;

$data = ProjectionCacheService::rememberAccountsReceivable(
    $key,
    fn() => $this->expensiveCalculation()
);
```

### Chain Configuration Methods

```php
$data = $reportService
    ->setFilters($startDate, $endDate, ['artist_id' => 1])
    ->getProfitabilityAnalysisData();
```

---

## Testing Services

### Mock Dependencies

```php
$mockCalculator = Mockery::mock(GigFinancialCalculatorService::class);
$mockCalculator->shouldReceive('calculateGrossCashBrl')
    ->with($gig)
    ->andReturn(10000.00);

$auditService = new AuditService($mockCalculator);
```

### Test with Real Data

```php
$gig = Gig::factory()
    ->has(Payment::factory()->count(3))
    ->create();

$calculator = new GigFinancialCalculatorService();
$gross = $calculator->calculateGrossCashBrl($gig);

$this->assertEquals($expected, $gross);
```

---

## Common Service Combinations

### Financial Dashboard

```php
$dashboardService = new DashboardService($calculator);
$projectionService = new FinancialProjectionService($calculator, $queryBuilder, $metricsService);

$dashboardData = $dashboardService->setFilters($filters)->getDashboardData();
$cashFlow = $projectionService->setPeriod($start, $end)->getProjectedCashFlow();
```

### Commission Payment Workflow

```php
$validationService = new CommissionPaymentValidationService();
$bookerService = new BookerFinancialsService($calculator);

// Validate payment eligibility
$validation = $validationService->validateBookerCommissionPayment($gig);

if ($validation['can_pay']) {
    // Get commission details
    $kpis = $bookerService->getCommissionKpis($bookerId, $start, $end);
    // Process payment...
}
```

### Audit & Report Generation

```php
$auditService = new AuditService($calculator);
$auditReportService = new AuditReportService();

// Audit gigs
$auditResults = $auditService->calculateBulkAuditData($gigs);

// Generate report
$consolidatedReport = $auditService->generateConsolidatedReport($auditResults);

// Save to storage
// ... audit report logic
```

---

## Performance Considerations

### Use Cache for Expensive Operations

```php
use App\Services\ProjectionCacheService;

$strategicBalance = ProjectionCacheService::rememberStrategicBalance(
    fn() => $this->financialProjectionService->getProjectedCashFlow()
);
```

### Batch Process Gigs

```php
// ✅ Good - Bulk audit
$auditResults = $auditService->calculateBulkAuditData($gigs);

// ❌ Bad - Loop
foreach ($gigs as $gig) {
    $result = $auditService->calculateGigAuditData($gig);
}
```

### Eager Load Relations

```php
// Services expect relationships loaded
$gigs = Gig::with([
    'artist',
    'booker',
    'payments',
    'gigCosts.costCenter',
    'settlement'
])->get();

$reportData = $reportService->getFinancialReportData();
```

---

## Summary

| Service | LOC | Dependencies | Purpose |
|---------|-----|--------------|---------|
| **GigFinancialCalculatorService** | 237 | None | **CORE** - All financial calculations |
| **AuditService** | 299 | Calculator | Data validation & integrity |
| **FinancialReportService** | 1034 | Calculator | Report generation |
| **FinancialProjectionService** | 407 | Calculator, QueryBuilder, Metrics | Cash flow projections |
| **CashFlowProjectionService** | 548 | DRE, Calculator | Monthly cash flow |
| **DreProjectionService** | 275 | Calculator | Income statement |
| **ExchangeRateService** | 212 | None | Currency conversion |
| **DashboardService** | 220 | Calculator | Dashboard aggregation |
| **ArtistFinancialsService** | 58 | Calculator | Artist metrics |
| **BookerFinancialsService** | 253 | Calculator | Booker metrics |
| **CommissionPaymentValidationService** | 167 | None | Payment validation |
| **UserManagementService** | 179 | None | User operations |
| **AuditReportService** | 419 | None | Audit reporting |
| **ProjectionQueryBuilder** | 254 | None (static) | Optimized queries |
| **ProjectionCacheService** | 199 | None (static) | Cache management |
| **ProjectionMetricsService** | 265 | None (static) | Strategic metrics |

**Total**: 16 services, 4,870 lines of business logic

**Key Principle**: `GigFinancialCalculatorService` is the single source of truth for all financial calculations. All other services depend on or complement it.

---

**End of Services API Reference**