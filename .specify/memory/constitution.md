# Laravel Super Starter Constitution

## Core Principles

### I. Feature-First Development
Every feature starts as a complete specification before implementation; Features must be self-contained, independently testable, documented with clear user value; Clear purpose required - no organizational-only features; All features must have corresponding tests that validate the business requirements

### II. Laravel Convention Over Configuration
Folllow Laravel conventions and patterns where they exist; Use Artisan commands for setup, testing, and maintenance; Leverage Laravel's built-in features (Eloquent, Blade, Migrations, Queues, etc.); Extend framework capabilities rather than replacing them; Support JSON + human-readable output formats in API responses

### III. Test-Driven Development (NON-NEGOTIABLE)
TDD mandatory: Feature tests written → User approved → Tests fail → Then implement; Red-Green-Refactor cycle strictly enforced; Test order: Feature → Integration → Unit; Use Laravel's testing suite (PHPUnit with Laravel helpers); Database refresh and seeding for consistent test environments

### IV. Integration Testing Focus
Focus areas requiring integration tests: New API endpoints, Database model relationships, External service integrations, Queue job processing, Authentication and authorization flows; Use real database connections in tests; Test email sending, file uploads, and third-party service integrations

### V. Laravel Observability & Performance
Use Laravel's built-in logging (Monolog); Implement structured logging with context; Use Laravel Telescope for local debugging; Monitor query performance with database query logging; Implement caching strategies using Laravel Cache; Use Laravel Horizon for queue monitoring; Track application performance metrics

### VI. Laravel Versioning & API Management
API versioning follows Laravel API Resource patterns; Use MAJOR.MINOR.PATCH semantic versioning; Database migrations for schema changes; Feature flags for gradual rollouts; Breaking changes require migration guides; Use Laravel's API documentation standards

### VII. Simplicity & Laravel Best Practices
Start simple, follow YAGNI principles; Use Laravel's built-in features before custom solutions; Prefer Eloquent ORM over raw SQL; Use Laravel's validation system; Implement proper error handling with Laravel exception handling; Follow PSR coding standards with Laravel Pint

## Laravel-Specific Constraints

### Technology Stack Requirements
- **Framework**: Laravel 11.x with PHP 8.2+
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis for sessions and cache
- **Queue**: Redis or database driver for job queues
- **Frontend**: Inertia.js with Vue.js 3 or Laravel Livewire
- **Testing**: PHPUnit with Laravel testing utilities
- **Code Quality**: Laravel Pint for formatting, PHPStan for static analysis

### Security Standards
- Use Laravel Sanctum for API authentication
- Implement CSRF protection on web routes
- Validate all user input using Laravel Form Requests
- Use Laravel's authorization policies and gates
- Encrypt sensitive data using Laravel's encryption
- Follow OWASP guidelines for web security

### Performance Standards
- API response time <200ms for simple queries
- Database queries optimized with eager loading
- Use Laravel Octane for high-performance applications
- Implement caching for expensive operations
- Use Laravel Horizon for queue job monitoring

## Development Workflow

### Spec-Driven Feature Development
1. **Feature Specification**: Use `/specify` command to create feature specs
2. **Implementation Planning**: Use `/plan` command for technical architecture
3. **Task Generation**: Use `/tasks` command for development tasks
4. **Test-First Implementation**: Write tests before code
5. **Laravel Standards Compliance**: Follow Laravel conventions

### Code Review Process
- All features require specification review before implementation
- Code must pass Laravel Pint formatting
- PHPStan analysis must pass at level 8
- Feature tests must pass and cover happy path + edge cases
- Integration tests required for API endpoints
- Security review for authentication and authorization changes

### Quality Gates
- Feature specification approved by stakeholders
- All tests passing (Feature → Integration → Unit)
- Code quality checks (Pint + PHPStan) passing
- Performance benchmarks met
- Security scan passed (if applicable)
- Documentation updated (API docs, README, etc.)

## Governance

This Constitution supersedes all other development practices; Amendments require documentation, stakeholder approval, and migration plan for existing code; All pull requests must verify compliance with these principles; Complexity and deviations must be justified and documented; Use Laravel's built-in features and patterns as the foundation for all development

**Version**: 1.0.0 | **Ratified**: 2025-01-14 | **Last Amended**: 2025-01-14
