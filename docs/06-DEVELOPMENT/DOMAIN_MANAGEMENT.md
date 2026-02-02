# Domain Management Guide

This guide covers the modular domain system introduced in v1.3.0, allowing you to install only the domains you need.

## Overview

FinAegis uses Domain-Driven Design (DDD) with 29 bounded contexts. The platform supports modular installation where you can choose which domains to enable based on your requirements.

## Domain Types

| Type | Description | Examples |
|------|-------------|----------|
| **Core** | Always installed, required for platform operation | `shared`, `account`, `user`, `compliance` |
| **Optional** | Can be installed based on requirements | `exchange`, `lending`, `treasury`, `wallet` |

## Available Commands

### List All Domains

```bash
php artisan domain:list
```

Shows all available domains with:
- Installation status (✓ installed, ○ available, ✗ missing dependencies)
- Domain type (core/optional)
- Version
- Dependencies

### Install a Domain

```bash
# Install with dependencies (recommended)
php artisan domain:install exchange

# Preview what will be installed
php artisan domain:install lending --dry-run

# Install without auto-installing dependencies (advanced)
php artisan domain:install treasury --no-dependencies
```

### Show Domain Dependencies

```bash
php artisan domain:dependencies exchange
```

Displays the dependency tree for a domain, showing what other domains it requires.

### Verify Domain Health

```bash
php artisan domain:verify
```

Checks all installed domains for:
- Missing migrations
- Configuration issues
- Service provider registration
- Model relationships

### Remove a Domain

```bash
php artisan domain:remove newsletter
```

Safely removes a domain with dependency checking. Will refuse to remove if other installed domains depend on it.

### Create a New Domain

```bash
php artisan domain:create my-domain
```

Scaffolds a new domain with standard directory structure:
```
app/Domain/MyDomain/
├── Aggregates/
├── Commands/
├── Contracts/
├── DataObjects/
├── Enums/
├── Events/
├── Models/
├── Projectors/
├── Queries/
├── Repositories/
├── Services/
└── Workflows/
```

## Domain Registry

All 29 domains and their dependencies:

### Core Domains (Always Installed)

| Domain | Version | Dependencies |
|--------|---------|--------------|
| shared | 1.3.0 | - |
| account | 1.3.0 | shared |
| user | 1.3.0 | shared |
| compliance | 1.3.0 | shared, account, user |

### Financial Domains

| Domain | Dependencies | Description |
|--------|--------------|-------------|
| exchange | shared, account, compliance | Trading engine, order matching |
| lending | shared, account, compliance | P2P lending, credit scoring |
| treasury | shared, account | Portfolio management, yield optimization |
| stablecoin | shared, account, compliance | Token minting, burning, liquidation |
| wallet | shared, account | Multi-chain blockchain wallets |
| payment | shared, account | Payment processing |
| banking | shared, account, compliance | SEPA/SWIFT transfers |

### AI & Agent Domains

| Domain | Dependencies | Description |
|--------|--------------|-------------|
| ai | shared | MCP server, LLM integration |
| agent-protocol | shared, account, compliance | A2A messaging, escrow |

### Infrastructure Domains

| Domain | Dependencies | Description |
|--------|--------------|-------------|
| monitoring | shared | Metrics, health checks |
| fraud | shared, account, compliance | Fraud detection |
| batch | shared | Batch processing |
| webhook | shared | Webhook management |

### Other Domains

| Domain | Dependencies | Description |
|--------|--------------|-------------|
| governance | shared, account | Voting, proposals |
| cgo | shared, account, compliance | Continuous Growth Offering |
| basket | shared, account, asset | Currency baskets (GCU) |
| asset | shared, account | Asset management |
| custodian | shared, account | Custody services |
| activity | shared, user | Activity logging |
| contact | shared, user | Contact management |
| newsletter | shared | Email newsletters |
| product | shared | Product catalog |
| performance | shared | Performance tracking |
| regulatory | shared, compliance | Regulatory reporting |
| financial-institution | shared, compliance | FI management |

## Best Practices

### Starting Fresh

For a minimal installation:

```bash
# Core domains are always installed
# Add only what you need:
php artisan domain:install exchange
php artisan domain:install wallet
```

### Full Installation

For complete platform functionality:

```bash
# All domains are installed by default in standard setup
php artisan migrate --seed
```

### Domain Health Verification

After installation or updates:

```bash
php artisan domain:verify
php artisan config:cache
php artisan route:cache
```

## Troubleshooting

### Missing Dependencies

If you see "✗ Missing dependencies" in `domain:list`:

```bash
# Check what's missing
php artisan domain:dependencies <domain>

# Install missing dependencies
php artisan domain:install <dependency>
```

### Service Provider Not Registered

1. Verify domain's `ServiceProvider.php` exists
2. Check `config/app.php` for provider registration
3. Run `php artisan config:cache`

### Migration Issues

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate
```

## Related Documentation

- [Domain Dependencies](../02-ARCHITECTURE/DOMAIN_DEPENDENCIES.md) - Detailed dependency hierarchy
- [Architecture Overview](../02-ARCHITECTURE/README.md) - DDD architecture patterns
- [v1.3.0 Implementation Plan](../V1.3.0_IMPLEMENTATION_PLAN.md) - Modular system design
