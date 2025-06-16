<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;

describe('Asset Model', function () {
    it('uses correct table', function () {
        $asset = new Asset();
        expect($asset->getTable())->toBe('assets');
    });

    it('has correct primary key', function () {
        $asset = new Asset();
        expect($asset->getKeyName())->toBe('code');
    });

    it('does not use incrementing primary key', function () {
        $asset = new Asset();
        expect($asset->getIncrementing())->toBeFalse();
    });

    it('uses string key type', function () {
        $asset = new Asset();
        expect($asset->getKeyType())->toBe('string');
    });

    it('has correct fillable attributes', function () {
        $asset = new Asset();
        expect($asset->getFillable())->toBe([
            'code',
            'name',
            'type',
            'symbol',
            'precision',
            'is_active',
            'metadata',
        ]);
    });

    it('has correct casts', function () {
        $asset = new Asset();
        $casts = $asset->getCasts();
        
        expect($casts)->toHaveKey('is_active');
        expect($casts)->toHaveKey('metadata');
        expect($casts)->toHaveKey('precision');
    });

    it('has correct attributes', function () {
        $asset = new Asset();
        $attributes = $asset->getAttributes();
        
        expect($attributes)->toHaveKey('metadata');
        expect($attributes['metadata'])->toBe('[]');
    });

    it('can create asset with all attributes', function () {
        $asset = Asset::factory()->create([
            'code' => 'TEST',
            'name' => 'Test Currency',
            'type' => 'fiat',
            'symbol' => 'T',
            'precision' => 2,
            'is_active' => true,
            'metadata' => ['country' => 'Test'],
        ]);

        expect($asset->code)->toBe('TEST');
        expect($asset->name)->toBe('Test Currency');
        expect($asset->type)->toBe('fiat');
        expect($asset->symbol)->toBe('T');
        expect($asset->precision)->toBe(2);
        expect($asset->is_active)->toBeTrue();
        expect($asset->metadata)->toBe(['country' => 'Test']);
    });

    it('has account balances relationship defined', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'accountBalances'))->toBeTrue();
    });

    it('has exchange rates from relationship defined', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'exchangeRatesFrom'))->toBeTrue();
    });

    it('has exchange rates to relationship defined', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'exchangeRatesTo'))->toBeTrue();
    });

    it('has active scope', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'scopeActive'))->toBeTrue();
    });

    it('has of type scope', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'scopeOfType'))->toBeTrue();
    });

    it('can get total balance', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'getTotalBalance'))->toBeTrue();
    });

    it('can get active exchange rates', function () {
        $asset = new Asset();
        expect(method_exists($asset, 'getActiveExchangeRates'))->toBeTrue();
    });

    it('can format value', function () {
        $asset = Asset::factory()->create(['precision' => 2]);
        expect($asset->formatValue(10000))->toBe('100.00');
        
        $asset = Asset::factory()->create(['precision' => 8]);
        expect($asset->formatValue(100000000))->toBe('1.00000000');
    });

    it('can parse value', function () {
        $asset = Asset::factory()->create(['precision' => 2]);
        expect($asset->parseValue('100.00'))->toBe(10000);
        
        $asset = Asset::factory()->create(['precision' => 8]);
        expect($asset->parseValue('1.00000000'))->toBe(100000000);
    });
});