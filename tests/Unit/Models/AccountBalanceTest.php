<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\AccountBalance;
use App\Domain\Asset\Models\Asset;

describe('AccountBalance Model', function () {
    it('belongs to an account', function () {
        $account = Account::factory()->create();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
        ]);
        
        expect($balance->account)->toBeInstanceOf(Account::class);
        expect($balance->account->uuid)->toBe($account->uuid);
    });
    
    it('belongs to an asset', function () {
        $asset = Asset::factory()->create();
        $balance = AccountBalance::factory()->create([
            'asset_code' => $asset->code,
        ]);
        
        expect($balance->asset)->toBeInstanceOf(Asset::class);
        expect($balance->asset->code)->toBe($asset->code);
    });
    
    it('can format balance for display', function () {
        $balance = AccountBalance::factory()->create([
            'balance' => 150000,
        ]);
        
        expect($balance->getFormattedBalanceAttribute())->toBe('1500.00');
    });
    
    it('can calculate balance in dollars', function () {
        $balance = AccountBalance::factory()->create([
            'balance' => 250000,
        ]);
        
        expect($balance->getBalanceInDollarsAttribute())->toBe(2500.00);
    });
    
    it('can increment balance', function () {
        $balance = AccountBalance::factory()->create([
            'balance' => 100000,
        ]);
        
        $balance->incrementBalance(50000);
        
        expect($balance->fresh()->balance)->toBe(150000);
    });
    
    it('can decrement balance', function () {
        $balance = AccountBalance::factory()->create([
            'balance' => 100000,
        ]);
        
        $balance->decrementBalance(30000);
        
        expect($balance->fresh()->balance)->toBe(70000);
    });
    
    it('cannot decrement below zero', function () {
        $balance = AccountBalance::factory()->create([
            'balance' => 100000,
        ]);
        
        expect(fn() => $balance->decrementBalance(150000))
            ->toThrow(InvalidArgumentException::class, 'Insufficient balance');
    });
    
    it('can check if balance is sufficient', function () {
        $balance = AccountBalance::factory()->create([
            'balance' => 100000,
        ]);
        
        expect($balance->hasSufficientBalance(50000))->toBeTrue();
        expect($balance->hasSufficientBalance(150000))->toBeFalse();
    });
    
    it('can get zero balance for new account balance', function () {
        $balance = new AccountBalance([
            'balance' => 0,
        ]);
        
        expect($balance->balance)->toBe(0);
        expect($balance->getFormattedBalanceAttribute())->toBe('0.00');
    });
});