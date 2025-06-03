<?php

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\Workflows\BalanceInquiryActivity;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Mockery;

beforeEach(function () {
    $this->activity = new BalanceInquiryActivity();
    $this->accountUuid = new AccountUuid('test-account-uuid');
    $this->transactionAggregate = Mockery::mock(TransactionAggregate::class);
});

afterEach(function () {
    Mockery::close();
});

it('can perform balance inquiry for existing account', function () {
    // Create test account
    $account = Account::factory()->create([
        'uuid' => 'test-account-uuid',
        'name' => 'Test Account',
        'balance' => 5000
    ]);

    // Mock TransactionAggregate
    $mockAggregate = Mockery::mock(TransactionAggregate::class);
    $mockAggregate->balance = 5000;
    
    $this->transactionAggregate
        ->shouldReceive('retrieve')
        ->with('test-account-uuid')
        ->once()
        ->andReturn($mockAggregate);

    // Mock logging
    Log::shouldReceive('info')
        ->once()
        ->with('Balance inquiry', Mockery::type('array'));

    $result = $this->activity->execute(
        $this->accountUuid, 
        'user-123', 
        $this->transactionAggregate
    );

    expect($result)->toHaveKey('account_uuid', 'test-account-uuid');
    expect($result)->toHaveKey('balance', 5000);
    expect($result)->toHaveKey('account_name', 'Test Account');
    expect($result)->toHaveKey('inquired_by', 'user-123');
    expect($result)->toHaveKey('inquired_at');
});

it('can perform balance inquiry without requester', function () {
    // Create test account
    $account = Account::factory()->create([
        'uuid' => 'test-account-uuid',
        'name' => 'Test Account',
        'balance' => 3000
    ]);

    // Mock TransactionAggregate
    $mockAggregate = Mockery::mock(TransactionAggregate::class);
    $mockAggregate->balance = 3000;
    
    $this->transactionAggregate
        ->shouldReceive('retrieve')
        ->with('test-account-uuid')
        ->once()
        ->andReturn($mockAggregate);

    // Mock logging
    Log::shouldReceive('info')
        ->once()
        ->with('Balance inquiry', Mockery::on(function ($data) {
            return $data['requested_by'] === null;
        }));

    $result = $this->activity->execute(
        $this->accountUuid, 
        null, 
        $this->transactionAggregate
    );

    expect($result['inquired_by'])->toBeNull();
    expect($result['account_uuid'])->toBe('test-account-uuid');
    expect($result['balance'])->toBe(3000);
});

it('handles non-existent account gracefully', function () {
    // Don't create account in database
    
    // Mock TransactionAggregate
    $mockAggregate = Mockery::mock(TransactionAggregate::class);
    $mockAggregate->balance = 0;
    
    $this->transactionAggregate
        ->shouldReceive('retrieve')
        ->with('test-account-uuid')
        ->once()
        ->andReturn($mockAggregate);

    // Mock logging
    Log::shouldReceive('info')
        ->once();

    $result = $this->activity->execute(
        $this->accountUuid, 
        'user-123', 
        $this->transactionAggregate
    );

    expect($result['account_name'])->toBeNull();
    expect($result['status'])->toBe('unknown');
    expect($result['balance'])->toBe(0);
});

it('logs inquiry for audit trail', function () {
    // Create test account
    Account::factory()->create([
        'uuid' => 'test-account-uuid',
        'name' => 'Test Account'
    ]);

    // Mock TransactionAggregate
    $mockAggregate = Mockery::mock(TransactionAggregate::class);
    $mockAggregate->balance = 1000;
    
    $this->transactionAggregate
        ->shouldReceive('retrieve')
        ->once()
        ->andReturn($mockAggregate);

    // Assert logging is called with correct parameters
    Log::shouldReceive('info')
        ->once()
        ->with('Balance inquiry', Mockery::on(function ($data) {
            return $data['account_uuid'] === 'test-account-uuid' &&
                   $data['requested_by'] === 'audit-user' &&
                   isset($data['timestamp']);
        }));

    $this->activity->execute(
        $this->accountUuid, 
        'audit-user', 
        $this->transactionAggregate
    );
});

it('returns inquiry timestamp in ISO format', function () {
    // Create test account
    Account::factory()->create([
        'uuid' => 'test-account-uuid'
    ]);

    // Mock TransactionAggregate
    $mockAggregate = Mockery::mock(TransactionAggregate::class);
    $mockAggregate->balance = 1000;
    
    $this->transactionAggregate
        ->shouldReceive('retrieve')
        ->once()
        ->andReturn($mockAggregate);

    // Mock logging
    Log::shouldReceive('info')->once();

    $result = $this->activity->execute(
        $this->accountUuid, 
        'user-123', 
        $this->transactionAggregate
    );

    expect($result['inquired_at'])->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z/');
});

it('returns all required fields in response', function () {
    // Create test account
    Account::factory()->create([
        'uuid' => 'test-account-uuid',
        'name' => 'Complete Account'
    ]);

    // Mock TransactionAggregate
    $mockAggregate = Mockery::mock(TransactionAggregate::class);
    $mockAggregate->balance = 7500;
    
    $this->transactionAggregate
        ->shouldReceive('retrieve')
        ->once()
        ->andReturn($mockAggregate);

    // Mock logging
    Log::shouldReceive('info')->once();

    $result = $this->activity->execute(
        $this->accountUuid, 
        'complete-user', 
        $this->transactionAggregate
    );

    $expectedKeys = [
        'account_uuid', 
        'balance', 
        'account_name', 
        'status', 
        'inquired_at', 
        'inquired_by'
    ];
    
    foreach ($expectedKeys as $key) {
        expect($result)->toHaveKey($key);
    }
});