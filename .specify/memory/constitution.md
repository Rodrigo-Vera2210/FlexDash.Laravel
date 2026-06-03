# FlexDash POS System Constitution

## Core Principles

### I. Test-Driven Development (NON-NEGOTIABLE)

TDD is mandatory across all layers. All new features MUST follow the Red-Green-Refactor cycle: write failing tests first, get user approval of test intent, watch tests fail, then implement functionality. Unit tests required for all business logic; integration tests required for inter-module communication and critical workflows.

### II. Layered Architecture

All code MUST respect strict layered architecture: Presentation Layer (Controllers, Views), Application Layer (Services, Use Cases), Domain Layer (Entities, Business Rules), and Data Access Layer (Repositories, Migrations). Cross-layer communication MUST flow through defined interfaces only. No business logic in controllers; no direct database queries in services.

### III. Module-Based Backend Architecture

Backend functionality organized by feature/module (e.g., `app/Modules/Sales`, `app/Modules/Inventory`, `app/Modules/Users`). Each module is self-contained with its own Controllers, Services, Models, Repositories, and Tests. Modules communicate via Services and Contracts—NOT direct database access. Shared code centralized in `app/Shared` or `app/Core`.

### IV. Clean Code & Best Practices

All code MUST follow SOLID principles. Class and method names self-documenting; maximum method length 30 lines; cyclomatic complexity <10. Single Responsibility enforced; DRY (Don't Repeat Yourself) mandatory; no hardcoded values outside configuration. Code reviews required before merge; SonarQube or linting enforced.

### V. Technology Stack Constraints

- **Framework**: Laravel (latest stable version)
- **Frontend Styling**: Tailwind CSS (utility-first, no custom CSS unless justified)
- **Database**: SQLite (primary; external DBMS as needed)
- **Testing**: PHPUnit + Pest (BDD + unit tests)
- **Version Control**: Git with feature branches
- **Documentation**: Markdown, inline comments for complex logic

### VI. JWT-Based Authentication (NON-NEGOTIABLE)

All service endpoints MUST use JWT (JSON Web Token) for user validation and authentication. JWT tokens issued upon login contain user identity and permissions. Token validation MUST occur at the Controller/Middleware level before request reaches Services. Tokens MUST be validated on every request; refresh token rotation required for security. No session-based authentication in services. JWT payload MUST include: `user_id`, `role`, `permissions`, `exp` (expiration), `iat` (issued at). Tokens stored in HTTP-only cookies on frontend; transmission via Authorization Bearer header required.

## Architecture Guidelines

### Backend Module Structure

```
app/Modules/[ModuleName]/
├── Controllers/
├── Services/
├── Models/
├── Repositories/
├── Contracts/Interfaces/
├── Migrations/
├── Requests/
├── Resources/
└── Tests/
```

Each module owns its data model and business logic. Modules expose functionality via Services that implement Contracts. Inter-module communication happens through Service Injection, not direct database access.

### Frontend Conventions

- **CSS Framework**: Tailwind CSS exclusively for styling
- **Component Structure**: Vue/Livewire components follow single-responsibility rule
- **State Management**: Keep state local when possible; use Service Container for shared state
- **Responsive Design**: Mobile-first approach; test on 3 breakpoints minimum

### Database Schema

- SQLite as development default; schema versioning via Laravel Migrations
- All tables namespaced by module (e.g., `sales_transactions`, `inventory_items`)
- Foreign key constraints enforced; cascade delete policies explicit
- Timestamps (`created_at`, `updated_at`) on all tables; soft deletes where applicable

## Development Workflow

### Feature Development Process

1. **Specification**: Feature must be specified in `.specify/` before code begins
2. **Tests First**: Write failing tests covering acceptance criteria and edge cases
3. **Implementation**: Build functionality to pass tests; refactor for clarity
4. **Code Review**: PR requires passing tests, linting, and at least one approval
5. **Merge**: Squash/rebase merge to `develop` branch; auto-tag `main` on release

### Testing Requirements

- **Unit Tests**: ≥80% coverage for Services and Models
- **Feature Tests**: All user workflows (login, transaction, reports) covered
- **Integration Tests**: Cross-module interactions verified
- **Acceptance Tests**: Feature test coverage from user perspective
- **CI/CD Gate**: All tests MUST pass before merge; coverage reports generated

### Code Quality Gates

- PHPUnit/Pest tests all passing (0 failures)
- Laravel code standards (PSR-12) enforced via PHP-CS-Fixer
- Linting with PHP_CodeSniffer (PHPCS)
- Static analysis with PHPStan (level 5+)
- No commented-out code; clear commit messages (50-char subject, wrapped body at 72 chars)

## Governance

This Constitution supersedes all other development guides and practices. All contributors MUST comply with these principles—no exceptions without documented amendment.

**Amendment Process**:

1. Propose amendment with clear rationale and impact analysis
2. Affected team members review and vote (majority approval required)
3. Document amendment in this file with date and version bump
4. Regenerate all dependent templates (plan-template.md, spec-template.md, tasks-template.md)
5. Commit constitutional change separately with message: `docs: amend constitution to vX.Y.Z (reason)`

**Versioning Policy**:

- MAJOR: Principle removal or redefinition (breaking change to workflow)
- MINOR: New principle or significant guidance expansion
- PATCH: Clarifications, wording refinements, example updates

**Compliance Review**: Constitution review occurs at project milestones or when 3+ new features have been completed. Non-compliance issues logged as technical debt and tracked in sprint planning.

**Version**: 1.1.0 | **Ratified**: 2026-06-02 | **Last Amended**: 2026-06-02
