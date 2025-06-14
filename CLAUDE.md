# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## AI-Friendly Development

**FinAegis welcomes contributions from AI coding assistants!** This project is designed to be highly compatible with AI agents including Claude Code, GitHub Copilot, Cursor, and other vibe coding tools. The domain-driven design, comprehensive documentation, and well-structured patterns make it easy for AI agents to understand and contribute meaningfully to the codebase.

### Contribution Requirements for AI-Generated Code
All contributions (human or AI-generated) must include:
- **Full test coverage**: Every new feature, workflow, or significant change must have comprehensive tests
- **Complete documentation**: Update relevant documentation files and add inline documentation for complex logic
- **Code quality**: Follow existing patterns and maintain the established architecture principles
- **Always update or create new tests and update documentation whenever you're doing something**

## Development Commands

### Testing
```bash
# Run all tests
./vendor/bin/pest

# Run all tests in parallel (faster execution)
./vendor/bin/pest --parallel

# Run specific test suites
./vendor/bin/pest tests/Domain/         # Domain layer tests
./vendor/bin/pest tests/Feature/        # Feature tests
./vendor/bin/pest --coverage --min=50  # Run with coverage report (50% minimum)

# Run tests in parallel with coverage
./vendor/bin/pest --parallel --coverage --min=50

# Run single test file
./vendor/bin/pest tests/Domain/Account/Aggregates/LedgerAggregateTest.php

# Run tests with specific number of processes
./vendor/bin/pest --parallel --processes=4
```

### CI/CD and GitHub Actions
The project includes comprehensive GitHub Actions workflows:

```bash
# Workflows are triggered on:
# - Pull requests to main branch
# - Pushes to main branch

# Test Workflow (.github/workflows/test.yml):
# - Sets up PHP 8.3, MySQL 8.0, Redis 7, Node.js 20
# - Installs Composer and NPM dependencies
# - Builds frontend assets
# - Runs database migrations and seeders
# - Executes all tests in parallel with 50% coverage requirement
# - Uploads coverage reports to Codecov

# Security Workflow (.github/workflows/security.yml):
# - Uses Gitleaks to scan for exposed secrets
# - Scans entire git history for security vulnerabilities
# - Mandatory for all PRs to prevent secret leaks
```

### Building and Assets
```bash
# Build assets for production
npm run build

# Development with hot reloading
npm run dev

# Install dependencies
composer install
npm install
```

### API Documentation
```bash
# Generate/update API documentation
php artisan l5-swagger:generate

# Access documentation at:
# http://localhost:8000/api/documentation
# http://localhost:8000/docs/api-docs.json (raw OpenAPI spec)
```

### Database Operations
```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Cache Management
```bash
# Warm up cache for all accounts
php artisan cache:warmup

# Warm up specific accounts
php artisan cache:warmup --account=uuid1 --account=uuid2

# Clear all caches
php artisan cache:clear

# Monitor cache performance (check headers)
# X-Cache-Hits: Number of cache hits
# X-Cache-Misses: Number of cache misses
# X-Cache-Hit-Rate: Percentage hit rate
```

### Queue Management
```bash
# Start queue workers for event processing
php artisan queue:work --queue=events,ledger,transactions,transfers

# Monitor queues with Horizon
php artisan horizon

# Clear failed jobs
php artisan queue:clear

# Create admin user for Filament dashboard
php artisan make:filament-user
```

### Admin Dashboard Management
```bash
# Create admin user
php artisan make:filament-user

# Access dashboard at:
# http://localhost:8000/admin

# Clear Filament cache after resource changes
php artisan filament:clear-cached-components
php artisan view:clear
```

### Event Sourcing
```bash
# Replay events to projectors
php artisan event-sourcing:replay AccountProjector
php artisan event-sourcing:replay TurnoverProjector

# Create snapshots
php artisan snapshot:create

# Verify transaction hashes
php artisan verify:transaction-hashes
```

## Architecture Overview

### Domain-Driven Design Structure
- **Account Domain** (`app/Domain/Account/`): Core banking account management
- **Payment Domain** (`app/Domain/Payment/`): Transfer and payment processing
- Each domain has Aggregates, Events, Workflows, Activities, Projectors, Reactors, and Services

### Caching Architecture
- **Redis-based caching** for high-performance data access
- **Cache Services**: `AccountCacheService`, `TransactionCacheService`, `TurnoverCacheService`
- **Cache Manager**: Centralized cache coordination with automatic invalidation
- **TTL Strategy**: Different cache durations based on data volatility
  - Accounts: 1 hour
  - Balances: 5 minutes
  - Transactions: 30 minutes
  - Turnovers: 2 hours
- **Schema Updates**: Turnover model now supports separate `debit` and `credit` fields for proper accounting

### Event Sourcing Architecture
- **Aggregates**: `LedgerAggregate`, `TransactionAggregate`, `TransferAggregate`
- **Events**: `AccountCreated`, `MoneyAdded`, `MoneySubtracted`, `MoneyTransferred`
- **Projectors**: Build read models from events (`AccountProjector`, `TurnoverProjector`)
- **Reactors**: Handle side effects (`SnapshotTransactionsReactor`, `SnapshotTransfersReactor`)

### Workflow Orchestration (Saga Pattern)
- **Account Management**: `CreateAccountWorkflow`, `FreezeAccountWorkflow`, `DestroyAccountWorkflow`
- **Transaction Processing**: `DepositAccountWorkflow`, `WithdrawAccountWorkflow`, `TransactionReversalWorkflow`
- **Transfer Operations**: `TransferWorkflow`, `BulkTransferWorkflow` (with compensation)
- **System Operations**: `BalanceInquiryWorkflow`, `AccountValidationWorkflow`, `BatchProcessingWorkflow`
- **Enhanced Validation**: Comprehensive KYC, address verification, identity checks, and compliance screening
- **Batch Processing**: Daily turnover calculation, statement generation, interest processing, compliance checks, regulatory reporting

### Queue Configuration
Events are processed through separate queues:
- `events`: General domain events
- `ledger`: Account lifecycle events
- `transactions`: Money movement events
- `transfers`: Transfer-specific events

### Security Features
- **Quantum-resistant hashing**: SHA3-512 for all transactions
- **Event integrity**: Cryptographic validation using `Hash` value objects
- **Audit trails**: Complete event history for all operations
- **Enhanced error logging**: Comprehensive error tracking with context for hash validation failures
- **Compliance monitoring**: Automated detection of suspicious patterns, sanctions screening, and regulatory compliance

### Admin Dashboard (Filament)
- **Account Management**: Full CRUD operations with real-time balance updates
- **Transaction Monitoring**: View transaction history with advanced filtering
- **Bulk Operations**: Freeze/unfreeze multiple accounts simultaneously
- **Real-time Statistics**: Account overview widgets with key metrics
- **Security**: Role-based access control for admin operations

## Recent Improvements (Feature Branch: immediate-priority-fixes)

### Schema Enhancements
- **Turnover Model Refactoring**: Upgraded from simple `count`/`amount` fields to proper accounting with separate `debit` and `credit` fields
- **Database Migration**: Added backward-compatible migration that preserves existing data while adding new fields
- **Factory Updates**: Enhanced `TurnoverFactory` to generate realistic test data with proper debit/credit relationships

### Enhanced Security & Monitoring
- **Hash Validation Logging**: Implemented comprehensive error logging for transaction hash validation failures in `VerifyTransactionHashes` command
- **Contextual Error Tracking**: Added structured logging with aggregate UUIDs, event details, and cryptographic context for security incidents

### Advanced Workflow Activities
- **Account Validation**: Replaced placeholder implementations with production-ready validation logic including:
  - KYC document verification with field validation and email format checking
  - Address verification with domain validation and temporary email detection
  - Identity verification with name validation, email uniqueness checks, and fraud detection
  - Compliance screening with sanctions list matching, domain risk assessment, and transaction pattern analysis

- **Batch Processing**: Enhanced batch operations with realistic banking functionality:
  - Daily turnover calculation with proper debit/credit accounting
  - Account statement generation with transaction history and balance calculations
  - Interest processing with daily compounding for savings accounts
  - Compliance monitoring with suspicious activity detection and regulatory flagging
  - Regulatory reporting including CTR, SAR candidates, and monthly summaries
  - Archive management for transaction data retention

### Testing Infrastructure
- **Comprehensive Test Coverage**: Added full test suites for enhanced validation and batch processing activities
- **Schema Migration Testing**: Verified backward compatibility and data integrity through turnover cache tests
- **Reflection-based Testing**: Implemented thorough class structure validation following framework patterns

### Documentation & Code Quality
- **Enhanced Documentation**: Updated CLAUDE.md with detailed implementation notes and architectural improvements
- **Code Comments**: Added comprehensive inline documentation for complex validation and compliance logic
- **Type Safety**: Ensured proper type hints and return types throughout enhanced activities

## Key Development Patterns

### Creating Workflows
Workflows follow the saga pattern with compensation logic:
```php
// Simple workflow
class DepositAccountWorkflow extends Workflow
{
    public function execute(AccountUuid $uuid, Money $money): \Generator
    {
        return yield ActivityStub::make(DepositAccountActivity::class, $uuid, $money);
    }
}

// Compensatable workflow
class TransferWorkflow extends Workflow
{
    public function execute(AccountUuid $from, AccountUuid $to, Money $money): \Generator
    {
        try {
            yield ChildWorkflowStub::make(WithdrawAccountWorkflow::class, $from, $money);
            $this->addCompensation(fn() => ChildWorkflowStub::make(DepositAccountWorkflow::class, $from, $money));
            
            yield ChildWorkflowStub::make(DepositAccountWorkflow::class, $to, $money);
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }
}
```

### Event Handling
Events are recorded in aggregates and processed by projectors/reactors:
```php
// Recording events in aggregates
$aggregate->recordThat(new MoneyAdded($money, $hash));

// Processing in projectors
class AccountProjector extends Projector
{
    public function onMoneyAdded(MoneyAdded $event): void
    {
        app(CreditAccount::class)($event);
    }
}
```

### Security Implementation
All financial events include quantum-resistant hashes:
```php
class MoneyAdded extends ShouldBeStored implements HasHash, HasMoney
{
    public function __construct(
        public readonly Money $money,
        public readonly Hash $hash
    ) {}
}
```

## Testing Patterns

### Workflow Testing
```php
it('can execute transfer workflow', function () {
    WorkflowStub::fake();
    
    $workflow = WorkflowStub::make(TransferWorkflow::class);
    $workflow->start($fromAccount, $toAccount, $money);
    
    WorkflowStub::assertDispatched(WithdrawAccountActivity::class);
    WorkflowStub::assertDispatched(DepositAccountActivity::class);
});
```

### Aggregate Testing
```php
it('can record money added event', function () {
    $aggregate = TransactionAggregate::retrieve($uuid);
    $aggregate->credit($money);
    
    expect($aggregate->getStoredEvents())->toHaveCount(1);
    expect($aggregate->getStoredEvents()[0])->toBeInstanceOf(MoneyAdded::class);
});
```

### Model Factory Testing
```php
// AccountFactory provides realistic test data
it('can create account with factory', function () {
    $account = Account::factory()->create();
    
    expect($account->uuid)->toBeString();
    expect($account->user_uuid)->toBeString();
    expect($account->balance)->toBeInt();
});

// Factory states for specific scenarios
$zeroAccount = Account::factory()->zeroBalance()->create();
$richAccount = Account::factory()->withBalance(100000)->create();
$userAccount = Account::factory()->forUser($user)->create();
```

### Filament Resource Testing
```php
// Test admin dashboard resources
it('can list accounts in admin panel', function () {
    $accounts = Account::factory()->count(5)->create();
    
    livewire(AccountResource\Pages\ListAccounts::class)
        ->assertCanSeeTableRecords($accounts);
});

// Test account operations
it('can deposit money through admin panel', function () {
    $account = Account::factory()->create();
    
    livewire(AccountResource\Pages\ListAccounts::class)
        ->callTableAction('deposit', $account, data: [
            'amount' => 50.00,
        ])
        ->assertHasNoTableActionErrors();
});
```

### Admin Dashboard Development
When working with Filament resources:
```php
// Creating custom actions
Tables\Actions\Action::make('custom_action')
    ->label('Custom Action')
    ->icon('heroicon-o-star')
    ->action(function (Model $record): void {
        // Action logic here
    })
    ->visible(fn (Model $record): bool => $record->canPerformAction());

// Adding bulk operations
Tables\Actions\BulkAction::make('bulk_process')
    ->action(function (Collection $records): void {
        foreach ($records as $record) {
            // Process each record
        }
    })
    ->requiresConfirmation();

// Custom widgets
class CustomWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Label', 'Value')
                ->description('Description')
                ->color('success'),
        ];
    }
}
```

## Important Files and Locations
- Event configuration: `config/event-sourcing.php`
- Workflow configuration: `config/workflows.php`
- Queue configuration: `config/queue.php`
- Cache configuration: `config/domain-cache.php`
- Domain models: `app/Models/`
- Domain events: `app/Domain/*/Events/`
- Aggregates: `app/Domain/*/Aggregates/`
- Workflows: `app/Domain/*/Workflows/`
- Activities: `app/Domain/*/Workflows/*Activity.php`
- Cache services: `app/Domain/*/Services/Cache/`
- Filament resources: `app/Filament/Admin/Resources/`
- Filament tests: `tests/Feature/Filament/`
```