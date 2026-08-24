# Laravel 11 Performance & Quality Standards

## Core Principles
All PHP/Laravel code in `retribusi-api` (M-PAD API) must adhere to these standards.

### Modern PHP Formatting
- **Strict Types**: Every `.php` file MUST start with `declare(strict_types=1);`.
- **Typed Properties**: Properties in Classes, Models, and DTOs MUST be explicitly typed.
- **Return Types**: All methods and functions MUST include return type hints.
- **Pint Enforcement**: We use **Laravel Pint** (PSR-12) for all code formatting.

### Component-Based Design
- **Action Pattern**: Business logic resides in `app/Actions/`. Components are small, single-purpose, and `final`.
- **Service Layer**: Complex orchestration resides in `app/Services/`.
- **Thin Controllers**: Controllers handle only Request routing and Response formatting.
- **DTOs**: Data transfer objects for passing validated data between layers.

### Performance First
- **Eloquent Optimization**: Index columns used in `where` and `orderBy`.
- **Eager Loading**: Prevent N+1 queries using `with()`.
- **Resource Constraints**: Use `JsonResource` for consistent API envelopes.
- **Strict Migrations**: All schema changes MUST be through migrations.

### Testing Excellence
- **Methodology**: TDD is preferred. Write tests in `tests/` and use `testing/` shells for E2E validation.
- **Pest & PHPUnit**: We use a mixed environment; favor Pest for new tests.
- **Refactoring Requirement**: Major implementation changes MUST refresh the corresponding tests.

### Regulatory & Security Compliance
- **IDOR Prevention**: Always use `Auth::id()` or Model Policies to verify ownership.
- **Secret Management**: **NEVER** commit plain secrets. Use `env()` and config files.
- **Zonasi Integrity**: Logic for regional calculations must be immutable in `Actions`.

## Vision
To maintain the M-PAD API as a world-class, regional revenue infrastructure that is robust, performant, and easily maintained.
