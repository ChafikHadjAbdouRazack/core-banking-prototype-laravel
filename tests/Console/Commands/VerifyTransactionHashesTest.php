<?php

use App\Console\Commands\VerifyTransactionHashes;
use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Exceptions\InvalidHashException;
use App\Domain\Account\Repositories\AccountRepository;
use App\Domain\Account\Repositories\TransactionRepository;
use App\Models\Account;
use Illuminate\Support\Collection;
use Mockery;

beforeEach(function () {
    $this->transactionRepository = Mockery::mock(TransactionRepository::class);
    $this->accountRepository = Mockery::mock(AccountRepository::class);
    
    $this->command = new VerifyTransactionHashes(
        $this->transactionRepository,
        $this->accountRepository
    );
    
    // Create test accounts
    $this->accounts = collect([
        Account::factory()->make(['uuid' => 'account-1']),
        Account::factory()->make(['uuid' => 'account-2']),
    ]);
});

afterEach(function () {
    Mockery::close();
});

it('returns success when all hashes are valid', function () {
    // Mock repository to return test accounts
    $this->accountRepository
        ->shouldReceive('getAllByCursor')
        ->once()
        ->andReturn($this->accounts);

    // Mock TransactionAggregate with valid events
    $aggregate = Mockery::mock(TransactionAggregate::class);
    $aggregate->shouldReceive('getAppliedEvents')
        ->andReturn([]);
    
    TransactionAggregate::shouldReceive('retrieve')
        ->with('account-1')
        ->once()
        ->andReturn($aggregate);
    
    TransactionAggregate::shouldReceive('retrieve')
        ->with('account-2')
        ->once()
        ->andReturn($aggregate);

    $exitCode = $this->command->handle();

    expect($exitCode)->toBe(0);
});

it('returns failure when invalid hashes are found', function () {
    // Mock repository to return test accounts
    $this->accountRepository
        ->shouldReceive('getAllByCursor')
        ->once()
        ->andReturn($this->accounts->take(1)); // Only test one account

    // Create a mock event with hash
    $invalidEvent = Mockery::mock(MoneyAdded::class);
    $invalidEvent->hash = new Hash('invalid-hash');
    $invalidEvent->money = new Money(1000);

    // Mock TransactionAggregate with invalid hash event
    $aggregate = Mockery::mock(TransactionAggregate::class);
    $aggregate->shouldReceive('getAppliedEvents')
        ->once()
        ->andReturn([$invalidEvent]);
    
    $aggregate->shouldReceive('validateHash')
        ->with($invalidEvent->hash, $invalidEvent->money)
        ->once()
        ->andThrow(new InvalidHashException('Invalid hash'));
    
    TransactionAggregate::shouldReceive('retrieve')
        ->with('account-1')
        ->once()
        ->andReturn($aggregate);

    $exitCode = $this->command->handle();

    expect($exitCode)->toBe(1);
});

it('tracks erroneous accounts when hash validation fails', function () {
    // Mock repository to return test accounts
    $this->accountRepository
        ->shouldReceive('getAllByCursor')
        ->once()
        ->andReturn($this->accounts->take(1));

    // Create a mock event with hash
    $invalidEvent = Mockery::mock(MoneyAdded::class);
    $invalidEvent->hash = new Hash('invalid-hash');
    $invalidEvent->money = new Money(1000);

    // Mock TransactionAggregate with invalid hash event
    $aggregate = Mockery::mock(TransactionAggregate::class);
    $aggregate->shouldReceive('getAppliedEvents')
        ->once()
        ->andReturn([$invalidEvent]);
    
    $aggregate->shouldReceive('validateHash')
        ->once()
        ->andThrow(new InvalidHashException('Invalid hash'));
    
    TransactionAggregate::shouldReceive('retrieve')
        ->once()
        ->andReturn($aggregate);

    $this->command->handle();

    // Use reflection to check protected property
    $reflection = new ReflectionClass($this->command);
    $erroneousAccounts = $reflection->getProperty('erroneous_accounts');
    $erroneousAccounts->setAccessible(true);
    
    expect($erroneousAccounts->getValue($this->command))->toContain('account-1');
});

it('continues processing other accounts when one fails', function () {
    // Mock repository to return both test accounts
    $this->accountRepository
        ->shouldReceive('getAllByCursor')
        ->once()
        ->andReturn($this->accounts);

    // Create invalid event for first account
    $invalidEvent = Mockery::mock(MoneyAdded::class);
    $invalidEvent->hash = new Hash('invalid-hash');
    $invalidEvent->money = new Money(1000);

    // Mock first aggregate with invalid hash
    $invalidAggregate = Mockery::mock(TransactionAggregate::class);
    $invalidAggregate->shouldReceive('getAppliedEvents')
        ->once()
        ->andReturn([$invalidEvent]);
    
    $invalidAggregate->shouldReceive('validateHash')
        ->once()
        ->andThrow(new InvalidHashException('Invalid hash'));

    // Mock second aggregate with no events (valid)
    $validAggregate = Mockery::mock(TransactionAggregate::class);
    $validAggregate->shouldReceive('getAppliedEvents')
        ->once()
        ->andReturn([]);
    
    TransactionAggregate::shouldReceive('retrieve')
        ->with('account-1')
        ->once()
        ->andReturn($invalidAggregate);
    
    TransactionAggregate::shouldReceive('retrieve')
        ->with('account-2')
        ->once()
        ->andReturn($validAggregate);

    $exitCode = $this->command->handle();

    expect($exitCode)->toBe(1); // Should still fail due to invalid account
});

it('processes events that implement HasHash interface', function () {
    // Mock repository to return test accounts
    $this->accountRepository
        ->shouldReceive('getAllByCursor')
        ->once()
        ->andReturn($this->accounts->take(1));

    // Create valid hash events
    $validEvent1 = Mockery::mock(MoneyAdded::class);
    $validEvent1->hash = new Hash('valid-hash-1');
    $validEvent1->money = new Money(1000);

    $validEvent2 = Mockery::mock(MoneySubtracted::class);
    $validEvent2->hash = new Hash('valid-hash-2');
    $validEvent2->money = new Money(500);

    // Mock TransactionAggregate with valid hash events
    $aggregate = Mockery::mock(TransactionAggregate::class);
    $aggregate->shouldReceive('getAppliedEvents')
        ->once()
        ->andReturn([$validEvent1, $validEvent2]);
    
    $aggregate->shouldReceive('validateHash')
        ->with($validEvent1->hash, $validEvent1->money)
        ->once();
    
    $aggregate->shouldReceive('validateHash')
        ->with($validEvent2->hash, $validEvent2->money)
        ->once();
    
    TransactionAggregate::shouldReceive('retrieve')
        ->once()
        ->andReturn($aggregate);

    $exitCode = $this->command->handle();

    expect($exitCode)->toBe(0);
});

it('has correct command signature and description', function () {
    expect($this->command->getName())->toBe('app:verify-transaction-hashes');
    expect($this->command->getDescription())->toBe('Verify the hashes of all transaction events to ensure data integrity');
});