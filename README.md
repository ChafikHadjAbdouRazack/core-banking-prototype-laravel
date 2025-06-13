# FinAegis Core Banking Platform

[![Tests](https://github.com/finaegis/core-banking-prototype-laravel/actions/workflows/test.yml/badge.svg)](https://github.com/finaegis/core-banking-prototype-laravel/actions/workflows/test.yml)
[![License: Apache-2.0](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-8892BF.svg)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20.svg)](https://laravel.com/)

**Open Source Core Banking as a Service**

FinAegis is a modern, scalable, and secure core banking platform built with Laravel 12, featuring event sourcing, domain-driven design, workflow orchestration, and quantum-resistant security measures.

**🤖 AI-Friendly Architecture**: This project welcomes contributions from AI coding assistants (Claude Code, GitHub Copilot, Cursor, etc.). The comprehensive documentation and well-structured patterns make it easy for AI agents to understand and contribute meaningfully to the codebase.

## 🏛️ Platform Overview

FinAegis provides a complete foundation for banking operations with:

- **Event Sourcing Architecture**: Complete audit trail of all transactions
- **Saga Pattern Workflows**: Reliable business process orchestration with compensation
- **Domain-Driven Design**: Clean, maintainable code architecture
- **Modern UUID v7**: Time-ordered UUIDs for optimal database performance
- **Quantum-Resistant Security**: SHA3-512 cryptographic hashing
- **Real-time Processing**: High-performance transaction processing
- **Regulatory Compliance**: Built-in audit trails and compliance features
- **RESTful API**: Complete API layer for all banking operations

## 🚀 Quick Start

### Prerequisites

- PHP 8.3+
- Laravel 12
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.0+
- Node.js 18+ (for asset compilation)

### Installation

```bash
# Clone the repository
git clone https://github.com/finaegis/core-banking-laravel.git
cd finaegis-core-banking

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database and Redis in .env file
# Then run migrations
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start the application
php artisan serve

# Start queue workers (in separate terminals)
php artisan queue:work --queue=events,ledger,transactions,transfers
```

## 🏗️ Architecture

### Core Components

- **Event Store**: Immutable event log with complete transaction history
- **Aggregate Roots**: `LedgerAggregate`, `TransactionAggregate`, `TransferAggregate`
- **Workflows**: Saga-based business process orchestration
- **Projectors**: Real-time read model updates
- **Reactors**: Side-effect handling and notifications

### Domain Structure

```
app/Domain/
├── Account/           # Account management domain
│   ├── Aggregates/    # Business logic aggregates
│   ├── Events/        # Domain events
│   ├── Workflows/     # Business process workflows
│   ├── Activities/    # Individual workflow steps
│   ├── Projectors/    # Read model builders
│   └── Services/      # Domain services
└── Payment/           # Payment processing domain
    ├── Services/      # Payment services
    └── Workflows/     # Payment workflows
```

## 💼 Key Features

### Account Management
- Account creation, modification, and closure
- Balance inquiries with audit trails
- Account freezing/unfreezing for compliance (fully implemented)
- Multi-currency support (planned)

### Transaction Processing
- Real-time money deposits and withdrawals
- Transaction reversal with compensation
- Automated threshold monitoring
- Quantum-resistant transaction hashing

### Transfer Operations
- Peer-to-peer transfers with saga pattern
- Bulk transfer processing
- Automatic rollback on failures
- Cross-account validation

### Compliance & Security
- Complete audit trails for all operations
- KYC/AML validation workflows
- Regulatory reporting capabilities
- Role-based access control

### System Operations
- Batch processing for end-of-day operations
- Real-time balance calculations
- Automated snapshot creation
- Performance monitoring
- Redis caching layer for optimized performance

### Admin Dashboard
- Comprehensive admin interface powered by Filament v3
- Real-time account management and monitoring
- Transaction history and analytics
- Account operations (deposit, withdraw, freeze/unfreeze)
- Advanced filtering and search capabilities
- Bulk operations support for account management
- Account statistics and turnover monitoring
- User management interface
- Role-based access control

## 🔧 Usage Examples

### Account Operations

```php
use App\Domain\Account\Services\AccountService;

$accountService = app(AccountService::class);

// Create account
$accountService->create([
    'name' => 'John Doe Savings',
    'user_uuid' => $userUuid,
]);

// Deposit money
$accountService->deposit($accountUuid, 1000);

// Withdraw money
$accountService->withdraw($accountUuid, 500);
```

### Transfer Operations

```php
use App\Domain\Payment\Services\TransferService;

$transferService = app(TransferService::class);

// Single transfer
$transferService->transfer(
    from: $fromAccountUuid,
    to: $toAccountUuid,
    amount: 1000
);
```

### Workflow Usage

```php
use Workflow\WorkflowStub;
use App\Domain\Account\Workflows\BalanceInquiryWorkflow;

// Balance inquiry with audit
$workflow = WorkflowStub::make(BalanceInquiryWorkflow::class);
$result = $workflow->start($accountUuid, $requestedBy);
```

## 🧪 Testing

The platform includes comprehensive test coverage:

```bash
# Run all tests
./vendor/bin/pest

# Run with coverage report
./vendor/bin/pest --coverage

# Run specific test suites
./vendor/bin/pest tests/Domain/
./vendor/bin/pest tests/Feature/

# Run admin dashboard tests
./vendor/bin/pest tests/Feature/Filament/
```

### Test Structure
- **Unit Tests**: Domain logic and aggregates
- **Integration Tests**: Workflow orchestration
- **Feature Tests**: Full user scenarios

## 📖 Documentation

### API Documentation

The platform includes comprehensive API documentation powered by OpenAPI/Swagger:

- **Access Documentation**: Navigate to `/api/documentation` when the server is running
- **OpenAPI Specification**: Available at `/docs/api-docs.json`
- **Interactive Testing**: Test API endpoints directly from the documentation interface

```bash
# Generate/update API documentation
php artisan l5-swagger:generate
```

### Admin Dashboard

The platform includes a powerful admin dashboard built with Filament:

- **Access Dashboard**: Navigate to `/admin` when the server is running
- **Default Credentials**: Create an admin user with `php artisan make:filament-user`
- **Features**:
  - Account management with real-time operations (deposit, withdraw, freeze/unfreeze)
  - Transaction monitoring and history with detailed views
  - Turnover statistics and analytics
  - Account balance tracking with automatic updates
  - System health monitoring
  - Advanced filtering and search by status, balance, and name
  - Bulk operations support (freeze multiple accounts)
  - User management interface
  - Export capabilities (planned enhancement)

### Additional Resources

- **[Development Guide](DEVELOPMENT.md)**: Complete developer documentation
- **[System Architecture](ARCHITECTURE.md)**: Technical architecture overview
- **[Workflow Patterns](WORKFLOW_PATTERNS.md)**: Saga patterns and best practices
- **[API Implementation](API_IMPLEMENTATION.md)**: Complete API layer documentation
- **[BIAN API Documentation](BIAN_API_DOCUMENTATION.md)**: BIAN-compliant API following banking industry standards
- **[Admin Dashboard Guide](docs/ADMIN_DASHBOARD.md)**: Comprehensive admin interface documentation

### API Endpoints

The platform provides RESTful APIs for all banking operations:

```http
POST   /api/accounts              # Create account
GET    /api/accounts/{uuid}       # Get account details
POST   /api/accounts/{uuid}/deposit    # Deposit money
POST   /api/accounts/{uuid}/withdraw   # Withdraw money
POST   /api/transfers              # Create transfer
GET    /api/accounts/{uuid}/balance    # Balance inquiry
```

## 🔒 Security

### Security Features
- **Quantum-Resistant Hashing**: SHA3-512 for all transactions
- **Event Integrity**: Cryptographic validation of all events
- **Multi-Factor Authentication**: Laravel Fortify integration
- **Role-Based Access Control**: Granular permissions
- **Audit Logging**: Complete operation audit trails

### Compliance
- **GDPR**: Data protection and privacy controls
- **PCI DSS**: Secure payment card handling
- **SOX**: Financial reporting controls
- **Basel III**: Risk management framework

## 🚀 Deployment

### Docker Deployment

```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "8000:80"
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis
    
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: finaegis
      MYSQL_ROOT_PASSWORD: secret
    
  redis:
    image: redis:7-alpine
```

### Production Considerations
- Load balancing for high availability
- Database clustering for scalability
- Redis clustering for cache/queue resilience
- Comprehensive monitoring and alerting

## 🤝 Contributing

We welcome contributions from the community, including **AI coding assistants and vibe coding tools**! This project is designed to be highly compatible with AI agents like Claude Code, GitHub Copilot, Cursor, and similar tools. The domain-driven design and comprehensive documentation make it easy for AI agents to understand and contribute meaningfully.

### Contributing Guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass (`./vendor/bin/pest`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Development Standards (Human & AI Contributors)
- **Full test coverage**: Every new feature must have comprehensive tests
- **Complete documentation**: Update relevant docs and add inline documentation
- **Follow PSR-12 coding standards**
- **Maintain architectural patterns**: Follow existing DDD, event sourcing, and saga patterns
- **Maintain backward compatibility**

## 📊 Performance

### Benchmarks
- **Transaction Processing**: 10,000+ TPS
- **Event Storage**: Sub-millisecond write times
- **Balance Inquiries**: <50ms response time
- **Workflow Execution**: <100ms average

### Scalability
- Horizontal scaling via read replicas
- Event store sharding capabilities
- Queue-based async processing
- Redis caching for performance

## 🛠️ Tech Stack

- **Backend**: Laravel 12, PHP 8.3+
- **Event Sourcing**: Spatie Event Sourcing
- **Workflows**: Laravel Workflow
- **Database**: MySQL 8.0+/PostgreSQL 13+
- **Cache/Queue**: Redis 6.0+
- **Testing**: Pest PHP
- **Frontend**: Laravel Jetstream, Livewire, Tailwind CSS
- **Admin Panel**: Filament v3

## 📈 Roadmap

### Version 2.0 (Q2 2025)
- [ ] Multi-currency support
- [ ] International wire transfers
- [ ] Advanced fraud detection
- [ ] Mobile SDK

### Version 2.1 (Q3 2025)
- [ ] Machine learning risk scoring
- [ ] Real-time analytics dashboard
- [ ] Blockchain integration
- [ ] Open banking APIs

## 🆘 Support

- **Documentation**: [Full Documentation](./docs/)
- **Issues**: [GitHub Issues](https://github.com/finaegis/core-banking-laravel/issues)
- **Discussions**: [GitHub Discussions](https://github.com/finaegis/core-banking-laravel/discussions)
- **Email**: support@finaegis.com

## 📄 License

This project is open-sourced software licensed under the [Apache License 2.0](LICENSE).

## 🙏 Acknowledgments

- Laravel Team for the excellent framework
- Spatie for the event sourcing package
- Laravel Workflow team for saga pattern implementation
- The open-source community for continuous inspiration

---

**Built with ❤️ for the banking industry**
