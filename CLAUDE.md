# CLAUDE.md - EventosPro AI Assistant Guide

> **Orchestrator File**: This is the central index for AI-assisted development in EventosPro. Read this first, then access specific guides based on your task context.

## 🎯 Quick Context Selection

**Choose your context based on the task:**

| Task Type | Read This | Why |
|-----------|-----------|-----|
| New Feature Development | [Feature Development Context](.claude/contexts/feature-development.md) | Service layer, testing requirements, architecture patterns |
| Bug Fixing | [Bug Fixing Context](.claude/contexts/bug-fixing.md) | Debugging workflow, common issues, quick fixes |
| Financial Operations | [Financial Context](.claude/contexts/financial-operations.md) | Calculation rules, currency handling, validation |
| Writing Tests | [Testing Workflow](.claude/contexts/testing-workflow.md) | Test standards, coverage requirements, PHPUnit patterns |
| Quick Reference | See sections below | Essential commands and rules |

## 🚀 Essential Information (Always Read)

### Critical Development Rules

**1. ALWAYS USE LARAVEL SAIL** (Mandatory)
```bash
# Create alias first
alias sail='./vendor/bin/sail'

# All commands MUST use Sail
sail artisan test
sail composer install
sail npm run dev
```

**2. ALWAYS USE SERVICES for Business Logic**
- Never duplicate financial calculations
- Always inject via constructor
- See: [Services Guide](.claude/guides/03-services.md)

**3. ALWAYS TEST Changes**
```bash
sail artisan test --filter=YourTest
sail bash -c "vendor/bin/pint --dirty"  # Before commit
```

**4. ALWAYS USE PHP 8 Attributes for Tests**
```php
use PHPUnit\Framework\Attributes\Test;

#[Test]  // ✅ REQUIRED
public function it_does_something() { }

/** @test */  // ❌ DEPRECATED - Never use
```

**5. NEVER Add Co-Authorship in Commits**
```bash
# ❌ NEVER include in commit messages:
Co-Authored-By: Claude <noreply@anthropic.com>
🤖 Generated with [Claude Code](https://claude.com/claude-code)

# ✅ Write clean, professional commit messages without AI attribution
```

### Project Tech Stack

- **Laravel 12** + **Filament v4** + **Livewire v3**
- **PHP 8.4.13**
- **MySQL 8.0+** (via Sail)
- **Testing**: PHPUnit (80% coverage minimum)
- **Code Quality**: Laravel Pint + PHPStan

## 📚 Detailed Guides (Read as Needed)

### Core Guides

1. **[Quick Start](.claude/guides/01-quick-start.md)**
   - Environment setup
   - Common commands
   - Development workflow

2. **[Architecture Overview](.claude/guides/02-architecture.md)**
   - Domain models and relationships
   - Service layer pattern
   - Observer pattern
   - Filament resources

3. **[Services API](.claude/guides/03-services.md)**
   - GigFinancialCalculatorService (Core)
   - AuditService
   - FinancialProjectionService
   - All other services with examples

4. **[Testing Standards](.claude/guides/04-testing.md)**
   - PHPUnit configuration
   - Test structure and naming
   - Coverage requirements
   - Factory usage

5. **[Financial Business Rules](.claude/guides/05-financial-rules.md)**
   - Calculation flow
   - Payment validation rules
   - Currency handling
   - Commission rules

6. **[Database Patterns](.claude/guides/06-database.md)**
   - Migrations
   - Eloquent relationships
   - Query optimization (N+1 prevention)
   - Soft deletes

### Optimization Guides

7. **[Common Pitfalls & Solutions](.claude/guides/07-common-pitfalls.md)**
   - Past errors to avoid
   - Quick troubleshooting
   - Known issues and fixes

8. **[Token Optimization Strategies](.claude/guides/08-token-optimization.md)**
   - Context reuse patterns
   - When to read full guides vs summaries
   - Efficient code reading strategies

## ⚡ Quick Reference

### Most Used Commands

```bash
# Testing
sail artisan test
sail artisan test --filter=TestName
sail artisan test --coverage

# Database
sail artisan migrate
sail artisan migrate:fresh --seed
sail artisan tinker

# Code Quality
sail bash -c "vendor/bin/pint --dirty"
sail bash -c "vendor/bin/phpstan analyse"

# Development
sail up -d
sail down
sail artisan pail  # Logs
```

### Core Service Usage Pattern

```php
// Always inject in constructor
public function __construct(
    private GigFinancialCalculatorService $calculator,
    private AuditService $auditService
) {}

// Always use for calculations
$grossFee = $this->calculator->calculateGrossFeeInBrl($gig);
```

### Query Optimization Pattern

```php
// ✅ ALWAYS eager load relationships
$gigs = Gig::with(['artist', 'booker', 'payments', 'gigCosts.costCenter'])->get();

// ❌ NEVER do this (N+1 problem)
$gigs = Gig::all();
```

## 🎯 Decision Tree: What Should I Read?

```
START
  │
  ├─ Creating new feature?
  │   └─> Read: Architecture + Services + Feature Development Context
  │
  ├─ Fixing bug?
  │   └─> Read: Common Pitfalls + Bug Fixing Context
  │
  ├─ Working with financial calculations?
  │   └─> Read: Financial Rules + Services + Financial Context
  │
  ├─ Writing tests?
  │   └─> Read: Testing Standards + Testing Workflow Context
  │
  ├─ Database changes?
  │   └─> Read: Database Patterns + Architecture
  │
  └─ Quick command reference?
      └─> Stay here in Quick Reference section
```

## 🔄 Context Reuse Strategy (Token Optimization)

**Before asking for help:**

1. ✅ Mention which guides you've read
2. ✅ Reference specific sections relevant to your task
3. ✅ Use file references (e.g., "following pattern from app/Services/AuditService.php:105")
4. ✅ Ask about specific unknowns, not general explanations

**Examples:**

```
❌ "How do I create a financial service?"
✅ "Following Services Guide section 3.2, I need to add commission recalculation. Should I extend GigFinancialCalculatorService or create a new service?"

❌ "Tests are failing"
✅ "Test failing at AuditServiceTest.php:45 with decimal assertion error. Per Testing Standards section 2.1, should I use assertIsString instead?"
```

## 📖 Laravel Boost Integration

This project uses **Laravel Boost MCP** with specialized tools:

- `search-docs` - Version-specific Laravel/Filament/Livewire docs
- `tinker` - Execute PHP directly for debugging
- `database-query` - Read from database
- `list-artisan-commands` - Check available Artisan commands

**Important**: Always search Laravel Boost docs before external searches for version-specific guidance.

## 🔗 External Documentation

- Full API Reference: `docs/SERVICES_API.md`
- Testing Guide: `docs/TESTING.md`
- Sail Commands: `docs/LARAVEL_SAIL_COMMANDS.md`
- Architecture Deep Dive: `docs/ai_context/2_architecture.md`
- Business Context: `docs/ai_context/1_context.md`

---

## 📝 Version History

- **v2.0** (2025-10-27): Modular structure with context-based guides
- **v1.0**: Original monolithic documentation

**Last Updated**: 2025-10-27
**Maintained by**: EventosPro Development Team
