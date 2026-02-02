# AI Team Configuration

This memory contains the detailed AI team configuration for the FinAegis project.

## AI Team Assignments

| Task Category | Primary Agent | Secondary Agent | Specialized Use Cases |
|---------------|---------------|-----------------|----------------------|
| **Laravel Backend Development** | `@laravel-backend-expert` | `@laravel-eloquent-expert` | Controllers, services, middleware, command/query handlers |
| **Data Architecture & Modeling** | `@laravel-eloquent-expert` | `@performance-optimizer` | Event sourcing schemas, migrations, projections |
| **API Design & Contracts** | `@api-architect` | `@laravel-backend-expert` | REST API design, OpenAPI specs |
| **Complex Project Coordination** | `@tech-lead-orchestrator` | `@code-archaeologist` | Multi-domain features, architecture decisions |
| **Code Quality & Security** | `@code-reviewer` | `@performance-optimizer` | Pre-merge reviews, security analysis |
| **Performance & Optimization** | `@performance-optimizer` | `@laravel-eloquent-expert` | Query tuning, caching strategies |
| **Codebase Analysis & Documentation** | `@code-archaeologist` | `@documentation-specialist` | Architecture exploration |

## Domain-Specific Routing Rules

**Event Sourcing & CQRS:**
- `@laravel-eloquent-expert` → Event store schema design, projection optimization
- `@laravel-backend-expert` → Command/query handlers, domain events
- `@performance-optimizer` → Event store performance, aggregate snapshotting

**Financial Domain:**
- `@tech-lead-orchestrator` → Multi-domain workflows (Exchange + Lending + Wallet + Treasury)
- `@code-reviewer` → Regulatory compliance (GDPR, AML, KYC)
- `@performance-optimizer` → High-frequency trading optimization

**Workflow & Saga Patterns:**
- `@laravel-backend-expert` → Laravel Workflow implementation, saga orchestration
- `@code-archaeologist` → Complex workflow state machines, compensation analysis

## Development Workflow

1. **Feature Planning**: `@tech-lead-orchestrator` for complex, `@laravel-backend-expert` for simple
2. **Architecture & Design**: `@laravel-eloquent-expert` for data, `@api-architect` for APIs
3. **Implementation**: `@laravel-backend-expert` for domain logic
4. **Quality Assurance**: `@code-reviewer` for security, `@performance-optimizer` for performance

## Agent Guidelines

**`@laravel-backend-expert`:**
- Analyze existing Laravel patterns and service providers
- Implement demo service variants for dev/testing
- Follow event sourcing patterns with proper aggregate design

**`@laravel-eloquent-expert`:**
- Design event sourcing schemas with proper indexing
- Create efficient projections for read models
- Optimize queries for high-frequency operations

**`@api-architect`:**
- Design contracts following OpenAPI 3.0
- Implement proper versioning strategies
- Create comprehensive error response structures

**`@code-reviewer`:**
- Enforce security best practices
- Validate compliance with banking regulations
- Ensure proper audit trail implementation

**`@performance-optimizer`:**
- Focus on sub-second trading operations
- Optimize event sourcing queries
- Design scaling strategies for high-throughput

## Quality Standards

**Mandatory Checks:**
1. PHP-CS-Fixer + PHPCS (PSR-12)
2. PHPStan Level 8 with zero errors
3. Pest PHP with minimum 50% coverage
4. No security vulnerabilities

**Pre-Commit:** `./bin/pre-commit-check.sh --fix`
