# FinAegis Core Banking Prototype

[![CI Pipeline](https://github.com/finaegis/core-banking-prototype-laravel/actions/workflows/ci-pipeline.yml/badge.svg)](https://github.com/finaegis/core-banking-prototype-laravel/actions/workflows/ci-pipeline.yml)
[![License: Apache-2.0](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-8892BF.svg)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg)](https://laravel.com/)

**An open-source core banking prototype built with event sourcing, domain-driven design, and modern banking patterns.**

FinAegis demonstrates how a modern core banking platform could be built. It's not production-ready, but it's a great learning resource and starting point for building financial applications.

[Live Demo](https://finaegis.org) | [Documentation](docs/README.md) | [Quick Start](#quick-start) | [Contributing](#contributing)

---

## What is FinAegis?

FinAegis is a prototype that showcases:

- **Event-Sourced Architecture** - Every state change is captured as an immutable event, enabling complete audit trails
- **Domain-Driven Design** - 25+ bounded contexts covering accounts, trading, lending, compliance, and more
- **Modern Banking Patterns** - CQRS, sagas, workflow orchestration, and multi-asset support
- **AI Agent Framework** - Production-ready MCP server with 20+ banking tools for AI integration
- **Global Currency Unit (GCU)** - A conceptual democratic digital currency backed by a basket of assets

## Quick Start

### Try Demo Mode (Recommended)

No external dependencies needed - everything runs locally:

```bash
git clone https://github.com/finaegis/core-banking-prototype-laravel.git
cd core-banking-prototype-laravel
composer install
cp .env.demo .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Visit `http://localhost:8000` and log in with any demo account:
- `demo.user@gcu.global` / `demo123`
- `demo.business@gcu.global` / `demo123`
- `demo.investor@gcu.global` / `demo123`

### Full Installation

For development with all features:

```bash
# Clone and install
git clone https://github.com/finaegis/core-banking-prototype-laravel.git
cd core-banking-prototype-laravel
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your MySQL/PostgreSQL and Redis settings

# Setup database
php artisan migrate --seed
npm run build

# Start services
php artisan serve
php artisan queue:work --queue=events,ledger,transactions,transfers,webhooks
```

**Requirements**: PHP 8.4+, MySQL 8.0+ or PostgreSQL 13+, Redis 6.0+, Node.js 18+

## Key Features

| Domain | What It Does |
|--------|--------------|
| **Account Management** | Multi-asset accounts, deposits, withdrawals, transfers |
| **Exchange & Trading** | Order book, liquidity pools, external exchange integration |
| **Stablecoin** | Collateralized token issuance with liquidation mechanisms |
| **P2P Lending** | Loan lifecycle, credit scoring, risk assessment |
| **Blockchain Wallets** | Multi-chain support (BTC, ETH, Polygon, BSC) |
| **Governance** | Democratic voting, poll management |
| **Compliance** | KYC/AML workflows, regulatory reporting |
| **AI Framework** | MCP server, 20+ banking tools, event-sourced interactions |

## Architecture

```
app/Domain/
├── Account/        # Account management
├── Exchange/       # Trading engine
├── Lending/        # P2P lending
├── Stablecoin/     # Token management
├── Wallet/         # Blockchain integration
├── Governance/     # Voting system
├── Compliance/     # KYC/AML
├── AI/             # AI Agent Framework
└── ... (25+ domains)
```

Each domain follows event sourcing patterns with:
- **Aggregates** - Business logic containers
- **Events** - Immutable state changes
- **Projectors** - Read model builders
- **Workflows** - Saga-based orchestration

See [Architecture Documentation](docs/02-ARCHITECTURE/) for details.

## Documentation

| Topic | Description |
|-------|-------------|
| [Getting Started](docs/05-USER-GUIDES/GETTING-STARTED.md) | First steps with the platform |
| [User Guides](docs/05-USER-GUIDES/) | Feature walkthroughs |
| [API Reference](docs/04-API/REST_API_REFERENCE.md) | REST API documentation |
| [AI Framework](docs/13-AI-FRAMEWORK/) | AI agent integration |
| [Development](docs/06-DEVELOPMENT/) | Developer guides |
| [Architecture](docs/02-ARCHITECTURE/) | Technical deep-dives |

API documentation is also available at `/api/documentation` when running locally.

## Testing

```bash
# Run all tests
./vendor/bin/pest --parallel

# Run with coverage
./vendor/bin/pest --coverage --min=50

# Code quality checks
./bin/pre-commit-check.sh --fix
```

## Contributing

We welcome contributions! Whether you're fixing bugs, adding features, or improving docs, your help is appreciated.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes with tests
4. Run quality checks: `./bin/pre-commit-check.sh --fix`
5. Submit a pull request

**Standards**: PSR-12 coding style, PHPStan level 5, 50%+ test coverage

This project also supports AI coding assistants (Claude Code, GitHub Copilot, Cursor). Look for `AGENTS.md` files throughout the codebase for context-aware guidance.

## Project Status

This is a **demonstration prototype**. It showcases modern banking architecture but is not production-ready. Use it for:
- Learning event sourcing and DDD patterns
- Understanding core banking concepts
- Building proof-of-concepts
- Contributing to open-source fintech

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4+
- **Event Sourcing**: Spatie Event Sourcing
- **Workflows**: Laravel Workflow
- **Database**: MySQL/PostgreSQL
- **Cache/Queue**: Redis
- **Testing**: Pest PHP
- **Admin Panel**: Filament v3
- **Frontend**: Livewire, Tailwind CSS

## Support

- [Documentation](docs/README.md)
- [GitHub Issues](https://github.com/finaegis/core-banking-prototype-laravel/issues)
- [GitHub Discussions](https://github.com/finaegis/core-banking-prototype-laravel/discussions)

## License

[Apache License 2.0](LICENSE)

---

**Built with care for the open-source community**
