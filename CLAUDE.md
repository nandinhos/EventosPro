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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.17
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== sail rules ===

## Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `vendor/bin/sail artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `vendor/bin/sail artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 3, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire; don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="Livewire Init Hook Example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/sail bin pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test`, simply run `vendor/bin/sail bin pint` to fix any formatting issues.

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `vendor/bin/sail artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `vendor/bin/sail artisan test --compact`.
- To run all tests in a file: `vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `vendor/bin/sail artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v3 rules ===

## Tailwind CSS 3

- Always use Tailwind CSS v3; verify you're using only classes supported by this version.
</laravel-boost-guidelines>
