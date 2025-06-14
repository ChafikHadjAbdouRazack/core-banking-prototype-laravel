<?php

use App\Console\Commands\VerifyTransactionHashes;
use App\Domain\Account\Repositories\AccountRepository;
use App\Domain\Account\Repositories\TransactionRepository;

beforeEach(function () {
    $this->transactionRepository = app(TransactionRepository::class);
    $this->accountRepository = app(AccountRepository::class);
    
    $this->command = new VerifyTransactionHashes(
        $this->transactionRepository,
        $this->accountRepository
    );
});

it('can be instantiated', function () {
    expect($this->command)->toBeInstanceOf(VerifyTransactionHashes::class);
});

it('has correct command signature', function () {
    expect($this->command->getName())->toBe('app:verify-transaction-hashes');
});

it('has correct command description', function () {
    expect($this->command->getDescription())->toBe('Verify the hashes of all transaction events to ensure data integrity');
});

it('has handle method', function () {
    expect(method_exists($this->command, 'handle'))->toBeTrue();
});

it('handle method returns integer', function () {
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('handle');
    
    expect($method->getReturnType()->getName())->toBe('int');
});

it('has verifyAggregateHashes method', function () {
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('verifyAggregateHashes');
    
    expect($method->isProtected())->toBeTrue();
});

it('initializes with empty erroneous arrays', function () {
    $reflection = new ReflectionClass($this->command);
    
    $erroneousAccounts = $reflection->getProperty('erroneous_accounts');
    $erroneousAccounts->setAccessible(true);
    
    $erroneousTransactions = $reflection->getProperty('erroneous_transactions');
    $erroneousTransactions->setAccessible(true);
    
    expect($erroneousAccounts->getValue($this->command))->toBe([]);
    expect($erroneousTransactions->getValue($this->command))->toBe([]);
});

it('can be constructed with repository dependencies', function () {
    expect($this->command)->toBeInstanceOf(VerifyTransactionHashes::class);
    
    $reflection = new ReflectionClass($this->command);
    
    $transactionRepo = $reflection->getProperty('transactionRepository');
    $transactionRepo->setAccessible(true);
    
    $accountRepo = $reflection->getProperty('accountRepository');
    $accountRepo->setAccessible(true);
    
    expect($transactionRepo->getValue($this->command))->toBeInstanceOf(TransactionRepository::class);
    expect($accountRepo->getValue($this->command))->toBeInstanceOf(AccountRepository::class);
});

it('extends Command class', function () {
    $reflection = new ReflectionClass(VerifyTransactionHashes::class);
    expect($reflection->getParentClass()->getName())->toBe('Illuminate\Console\Command');
});

it('has protected property types', function () {
    $reflection = new ReflectionClass(VerifyTransactionHashes::class);
    
    expect($reflection->hasProperty('signature'))->toBeTrue();
    expect($reflection->hasProperty('description'))->toBeTrue();
    expect($reflection->hasProperty('erroneous_accounts'))->toBeTrue();
    expect($reflection->hasProperty('erroneous_transactions'))->toBeTrue();
});

it('has required private methods', function () {
    $reflection = new ReflectionClass(VerifyTransactionHashes::class);
    
    expect($reflection->hasMethod('verifyAggregateHashes'))->toBeTrue();
    expect($reflection->hasMethod('handle'))->toBeTrue();
    
    $handleMethod = $reflection->getMethod('handle');
    expect($handleMethod->isPublic())->toBeTrue();
    expect($handleMethod->getReturnType()?->getName())->toBe('int');
});