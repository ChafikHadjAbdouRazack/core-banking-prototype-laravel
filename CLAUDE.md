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
php artisan queue:work --queue=events,ledger,transactions,transfers,webhooks

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
- `webhooks`: Webhook delivery processing

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
- **Enhanced Analytics**:
  - **Balance Trends**: Track total and average balance over time with configurable periods
  - **Transaction Volume**: Analyze deposits, withdrawals, and transfers with bar charts
  - **Cash Flow Analysis**: Monitor debit/credit flows with net calculations
  - **Growth Metrics**: Track new account creation and cumulative growth
  - **System Health**: Real-time monitoring of services, cache, and queues
- **Export Functionality**: Export accounts, transactions, and users to CSV/XLSX formats
- **Webhook Management**: Configure and monitor webhook endpoints for real-time event notifications

## Recent Improvements (Feature Branch: analytics-charts)

### Enhanced Analytics Dashboard
- **Chart.js Integration**: Leverages Filament's built-in Chart.js support for rich visualizations
- **Real-time Updates**: All charts support configurable polling intervals (10s-60s)
- **Interactive Filters**: Time-based filtering for different analysis periods
- **Performance Optimized**: Efficient queries with proper indexing and caching

### Analytics Widgets
- **Account Balance Chart**:
  - Dual-line chart showing total and average balance trends
  - Time filters: 24h (hourly), 7d, 30d, 90d (daily intervals)
  - Simulated historical data for demonstration
  - Green/blue color scheme for visual distinction

- **Transaction Volume Chart**:
  - Bar chart with transaction type breakdown
  - Separate bars for deposits, withdrawals, and transfers
  - Zero-fill for complete time series visualization
  - Automatic grouping based on selected period

- **Turnover Flow Chart**:
  - Combined bar and line chart for cash flow analysis
  - Red bars for debits, green for credits
  - Blue trend line showing net flow
  - Monthly aggregation with 3-24 month views

- **Account Growth Chart**:
  - Dual-axis visualization for new vs. cumulative accounts
  - Adaptive time grouping (daily/weekly/monthly)
  - Growth rate insights for business metrics
  - Historical comparison capabilities

- **System Health Widget**:
  - Real-time service monitoring (DB, Redis, Queues)
  - Transaction processing rate per minute
  - Cache hit rate with performance indicators
  - Queue status with pending job counts
  - Mini sparkline charts for trends

## Previous Improvements (Feature Branch: export-and-webhooks)

### Export Functionality
- **Account Export**: Export account data with formatted balances and status information
- **Transaction Export**: Export transaction history with proper formatting and account relationships
- **User Export**: Export user data with account counts and registration information
- **Format Support**: Both CSV and XLSX formats supported out of the box
- **Background Processing**: Large exports processed in background with notifications
- **Custom Formatting**: Balance amounts converted from cents to dollars, status fields humanized

### Webhook System
- **Event-Driven Architecture**: Automatic webhook triggers for all major banking events
- **Flexible Configuration**: Subscribe to specific events with custom headers and retry policies
- **Security**: HMAC-SHA256 signature verification for webhook payloads
- **Reliability**: Automatic retries with exponential backoff, delivery tracking
- **Admin UI**: Full webhook management interface in Filament dashboard
- **Event Types**:
  - Account events: created, updated, frozen, unfrozen, closed
  - Transaction events: created, reversed
  - Transfer events: created, completed, failed
  - Balance alerts: low balance, negative balance
- **Monitoring**: Track delivery history, success rates, and failure reasons

## Previous Improvements (Feature Branch: immediate-priority-fixes)

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

### Webhook Implementation Examples

```php
// Creating a webhook via admin UI or programmatically
$webhook = Webhook::create([
    'name' => 'Payment Gateway Webhook',
    'url' => 'https://api.example.com/webhooks/banking',
    'events' => ['account.created', 'transaction.created', 'transfer.completed'],
    'headers' => ['X-API-Key' => 'your-api-key'],
    'secret' => 'webhook-secret-key',
    'retry_attempts' => 3,
    'timeout_seconds' => 30,
]);

// Webhook payload example
{
    "event": "account.created",
    "timestamp": "2025-01-14T10:30:00Z",
    "account_uuid": "01234567-89ab-cdef-0123-456789abcdef",
    "name": "John Doe Savings",
    "user_uuid": "fedcba98-7654-3210-fedc-ba9876543210",
    "balance": 0
}

// Verifying webhook signature in your application
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$payload = file_get_contents('php://input');
$expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    throw new UnauthorizedException('Invalid webhook signature');
}
```

### Export Implementation Examples

```php
// Adding export to a Filament resource
use App\Filament\Exports\AccountExporter;

protected function getHeaderActions(): array
{
    return [
        Actions\ExportAction::make()
            ->exporter(AccountExporter::class)
            ->label('Export Accounts')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success'),
    ];
}

// Creating a custom exporter
class AccountExporter extends Exporter
{
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('balance')
                ->label('Balance (USD)')
                ->formatStateUsing(fn ($state) => number_format($state / 100, 2)),
        ];
    }
}
```

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

// Export functionality
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;

// Add export to table header actions
Actions\ExportAction::make()
    ->exporter(AccountExporter::class)
    ->label('Export Accounts')
    ->icon('heroicon-o-arrow-down-tray')
    ->color('success');

// Create exporter class
class AccountExporter extends Exporter
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('uuid')->label('Account ID'),
            ExportColumn::make('balance')
                ->label('Balance (USD)')
                ->formatStateUsing(fn ($state) => number_format($state / 100, 2)),
        ];
    }
}
```

## Multi-Asset Development Patterns

### Asset Management
When implementing multi-asset support:
```php
// Creating Asset entity
namespace App\Domain\Asset\Models;

class Asset extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'code',      // 'USD', 'EUR', 'BTC', 'XAU'
        'name',      // 'US Dollar', 'Euro', 'Bitcoin', 'Gold'
        'type',      // 'fiat', 'crypto', 'commodity'
        'precision', // Decimal places (2 for USD, 8 for BTC)
        'is_active',
    ];
}

// Account with multi-asset balances
class Account extends Model
{
    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'account_uuid', 'uuid');
    }
    
    public function getBalance(string $asset_code): int
    {
        return $this->balances()
            ->where('asset_code', $asset_code)
            ->value('balance') ?? 0;
    }
    
    public function addBalance(string $asset_code, int $amount): void
    {
        $balance = $this->balances()->firstOrCreate(
            ['asset_code' => $asset_code],
            ['balance' => 0]
        );
        
        $balance->increment('balance', $amount);
    }
}
```

### Multi-Asset Events
Update events to include asset information:
```php
// Before (single currency)
class MoneyAdded extends ShouldBeStored
{
    public function __construct(
        public readonly Money $money,
        public readonly Hash $hash
    ) {}
}

// After (multi-asset)
class MoneyAdded extends ShouldBeStored
{
    public function __construct(
        public readonly string $asset_code,
        public readonly int $amount,
        public readonly Hash $hash,
        public readonly ?array $metadata = []
    ) {}
}
```

### Custodian Integration
Implementing custodian connectors:
```php
// Base connector implementation
abstract class BaseCustodianConnector implements ICustodianConnector
{
    protected array $config;
    protected HttpClient $client;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = Http::baseUrl($config['base_url'])
            ->withHeaders($this->getHeaders())
            ->timeout(30);
    }
    
    abstract protected function getHeaders(): array;
}

// Mock implementation for testing
class MockBankConnector extends BaseCustodianConnector
{
    private array $balances = [];
    private array $transactions = [];
    
    public function getBalance(string $asset_code): Money
    {
        $amount = $this->balances[$asset_code] ?? 0;
        return new Money($amount, $asset_code);
    }
    
    public function initiateTransfer(
        string $from,
        string $to,
        string $asset_code,
        int $amount,
        array $metadata = []
    ): TransactionReceipt {
        // Simulate transfer with delay
        sleep(1);
        
        $this->transactions[] = [
            'id' => Str::uuid(),
            'from' => $from,
            'to' => $to,
            'asset' => $asset_code,
            'amount' => $amount,
            'status' => 'completed',
            'timestamp' => now(),
        ];
        
        return new TransactionReceipt(
            id: end($this->transactions)['id'],
            status: 'completed'
        );
    }
}
```

### Exchange Rate Service
Working with exchange rates:
```php
// Usage in workflows
class CrossAssetTransferWorkflow extends Workflow
{
    public function execute(
        AccountUuid $from,
        AccountUuid $to,
        string $from_asset,
        string $to_asset,
        int $amount
    ): \Generator {
        // Get exchange rate
        $rate = yield ActivityStub::make(
            GetExchangeRateActivity::class,
            $from_asset,
            $to_asset
        );
        
        // Calculate converted amount
        $converted_amount = (int) round($amount * $rate);
        
        // Perform transfers
        yield from $this->executeTransfers(
            $from,
            $to,
            $from_asset,
            $to_asset,
            $amount,
            $converted_amount
        );
    }
}

// Caching exchange rates
class ExchangeRateService
{
    public function getRate(string $from, string $to): float
    {
        return Cache::remember(
            "rate:{$from}:{$to}",
            300, // 5 minutes
            fn() => $this->fetchRateFromProvider($from, $to)
        );
    }
}
```

### Governance Implementation
Building polling system:
```php
// Creating a poll
$poll = Poll::create([
    'title' => 'Add support for Japanese Yen?',
    'type' => 'single_choice',
    'options' => [
        ['id' => 'yes', 'label' => 'Yes, add JPY support'],
        ['id' => 'no', 'label' => 'No, not needed'],
    ],
    'start_date' => now(),
    'end_date' => now()->addDays(7),
    'voting_power_strategy' => OneUserOneVoteStrategy::class,
    'execution_workflow' => AddAssetWorkflow::class,
]);

// Voting
class VoteController extends Controller
{
    public function store(Poll $poll, Request $request)
    {
        $validated = $request->validate([
            'option_id' => 'required|string',
        ]);
        
        $votingPower = app($poll->voting_power_strategy)
            ->calculatePower($request->user(), $poll);
        
        Vote::create([
            'poll_id' => $poll->id,
            'user_uuid' => $request->user()->uuid,
            'selected_options' => [$validated['option_id']],
            'voting_power' => $votingPower,
        ]);
        
        return response()->json(['message' => 'Vote recorded']);
    }
}
```

### Testing Multi-Asset Features
```php
// Test multi-asset account
test('account can hold multiple asset balances', function () {
    $account = Account::factory()->create();
    
    $account->addBalance('USD', 10000);    // $100.00
    $account->addBalance('EUR', 5000);     // €50.00
    $account->addBalance('BTC', 100000000); // 1 BTC
    
    expect($account->getBalance('USD'))->toBe(10000);
    expect($account->getBalance('EUR'))->toBe(5000);
    expect($account->getBalance('BTC'))->toBe(100000000);
    expect($account->balances)->toHaveCount(3);
});

// Test custodian integration
test('can transfer through custodian', function () {
    $custodian = new MockBankConnector(['base_url' => 'https://mock.bank']);
    app(CustodianRegistry::class)->register('mock', $custodian);
    
    $workflow = WorkflowStub::make(CustodianTransferWorkflow::class);
    $result = $workflow->start('from_account', 'to_account', 'USD', 10000, 'mock');
    
    expect($result)->toBeInstanceOf(TransactionReceipt::class);
    expect($result->status)->toBe('completed');
});
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

### New Multi-Asset Locations
- Asset domain: `app/Domain/Asset/`
- Custodian connectors: `app/Domain/Custodian/Connectors/`
- Exchange rate providers: `app/Domain/Exchange/Providers/`
- Governance domain: `app/Domain/Governance/`
- Multi-asset migrations: `database/migrations/multi_asset/`
```