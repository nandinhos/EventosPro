# AGENTS.md - EventosPro Development Guidelines

## Build/Lint/Test Commands
**CRITICAL**: All commands must run through Laravel Sail:
- `sail up -d` - Start containers
- `sail artisan test` - Run all tests (80% min coverage required)
- `sail artisan test tests/Unit/Services/AuditServiceTest.php` - Run specific test file
- `sail artisan test --filter=testCalculateGigAuditData` - Run single test method
- `sail artisan test --coverage` - Tests with coverage report
- `sail bash -c "vendor/bin/pint --dirty"` - Format code (required before commits)

## Architecture & Codebase Structure
**Laravel 12 + Filament v3 application** for artist event management:
- **Core Entity**: Gig (central entity with Artist/Booker/Payment relationships)
- **Service Layer**: Always use services for business logic (GigFinancialCalculatorService, AuditService, etc.)
- **Database**: MySQL with SoftDeletes on key models
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Key Services**: Financial calculations, audit, reporting, currency conversion
- **Observers**: GigObserver, GigCostObserver for automatic recalculation

## Code Style Guidelines
- **PHP 8.2+**: Constructor property promotion, explicit return types, PHPDoc blocks
- **Service Injection**: Always use constructor injection in controllers
- **Eager Loading**: Prevent N+1 with `with(['relationships'])`
- **Financial Logic**: Use `GigFinancialCalculatorService` for ALL calculations
- **Testing**: Use factories, RefreshDatabase, 95% coverage for critical services
- **Laravel Pint**: Run before commits for PSR-12 compliance
- **Naming**: Descriptive names, TitleCase enums, consistent with siblings

## Business Rules
- Currency conversion to BRL for all reporting
- Payment validation: Artists/bookers can only be paid after event dates
- Commission exceptions require authorization keywords
- Soft deletes preserve financial history
- Use `CommissionPaymentValidationService` for payment rules

## References
- CLAUDE.md: Detailed application context and architecture
- docs/: Comprehensive documentation (SERVICES_API.md, TESTING.md, etc.)
- .github/copilot-instructions.md: Laravel Boost guidelines
