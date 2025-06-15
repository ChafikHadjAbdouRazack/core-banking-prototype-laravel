<?php

declare(strict_types=1);

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use App\Domain\Asset\Services\ExchangeRateService;

beforeEach(function () {
    // Assets are already seeded in migrations, no need to create duplicates
});

it('can get existing exchange rate', function () {
    $service = app(ExchangeRateService::class);
    
    ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->valid()
        ->create(['rate' => 0.85]);
    
    $rate = $service->getRate('USD', 'EUR');
    
    expect($rate)->not->toBeNull();
    expect($rate->rate)->toBe('0.8500000000');
});

it('returns identity rate for same asset', function () {
    $service = app(ExchangeRateService::class);
    
    $rate = $service->getRate('USD', 'USD');
    
    expect($rate)->not->toBeNull();
    expect($rate->rate)->toBe(1.0);
    expect($rate->from_asset_code)->toBe('USD');
    expect($rate->to_asset_code)->toBe('USD');
});

it('can convert amounts between assets', function () {
    $service = app(ExchangeRateService::class);
    
    ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->valid()
        ->create(['rate' => 0.85]);
    
    $convertedAmount = $service->convert(10000, 'USD', 'EUR'); // $100.00
    
    expect($convertedAmount)->toBe(8500); // €85.00
});

it('returns null for unavailable rates', function () {
    $service = app(ExchangeRateService::class);
    
    $rate = $service->getRate('USD', 'UNKNOWN');
    
    expect($rate)->toBeNull();
});

it('can store new exchange rate', function () {
    $service = app(ExchangeRateService::class);
    
    $rate = $service->storeRate(
        'USD',
        'EUR',
        0.85,
        ExchangeRate::SOURCE_MANUAL,
        ['test' => true]
    );
    
    expect($rate)->toBeInstanceOf(ExchangeRate::class);
    expect($rate->from_asset_code)->toBe('USD');
    expect($rate->to_asset_code)->toBe('EUR');
    expect($rate->rate)->toBe('0.8500000000');
    expect($rate->source)->toBe(ExchangeRate::SOURCE_MANUAL);
    expect($rate->metadata['test'])->toBeTrue();
});

it('can get available rates for an asset', function () {
    $service = app(ExchangeRateService::class);
    
    ExchangeRate::factory()->between('USD', 'EUR')->valid()->create();
    ExchangeRate::factory()->between('USD', 'BTC')->valid()->create();
    ExchangeRate::factory()->between('EUR', 'USD')->valid()->create();
    
    $rates = $service->getAvailableRatesFor('USD');
    
    expect($rates)->toHaveCount(3);
});

it('can get rate history', function () {
    $service = app(ExchangeRateService::class);
    
    // Create historical rates
    ExchangeRate::factory()
        ->between('USD', 'EUR')
        ->count(5)
        ->create([
            'valid_at' => now()->subDays(rand(1, 10))
        ]);
    
    $history = $service->getRateHistory('USD', 'EUR', 30);
    
    expect($history)->toHaveCount(5);
    expect($history->first()->valid_at->isAfter($history->last()->valid_at))->toBeTrue();
});