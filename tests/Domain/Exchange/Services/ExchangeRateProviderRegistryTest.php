<?php

declare(strict_types=1);

use App\Domain\Exchange\Exceptions\RateProviderException;
use App\Domain\Exchange\Providers\MockExchangeRateProvider;
use App\Domain\Exchange\Services\ExchangeRateProviderRegistry;

beforeEach(function () {
    $this->registry = new ExchangeRateProviderRegistry();
    $this->mockProvider = new MockExchangeRateProvider(['name' => 'Mock Provider']);
});

it('can register provider', function () {
    $this->registry->register('mock', $this->mockProvider);

    expect($this->registry->has('mock'))->toBeTrue();
    expect($this->registry->names())->toContain('mock');
});

it('can get registered provider', function () {
    $this->registry->register('mock', $this->mockProvider);

    $provider = $this->registry->get('mock');

    expect($provider)->toBe($this->mockProvider);
    expect($provider->getName())->toBe('Mock Provider');
});

it('throws exception for non-existent provider', function () {
    expect(fn () => $this->registry->get('non-existent'))
        ->toThrow(RateProviderException::class);
});

it('sets first registered provider as default', function () {
    $this->registry->register('mock', $this->mockProvider);

    $default = $this->registry->getDefault();

    expect($default)->toBe($this->mockProvider);
});

it('can change default provider', function () {
    $this->registry->register('mock1', $this->mockProvider);

    $secondProvider = new MockExchangeRateProvider(['name' => 'Second Provider']);
    $this->registry->register('mock2', $secondProvider);

    $this->registry->setDefault('mock2');

    expect($this->registry->getDefault())->toBe($secondProvider);
});

it('can get all providers', function () {
    $this->registry->register('mock1', $this->mockProvider);

    $secondProvider = new MockExchangeRateProvider(['name' => 'Second Provider']);
    $this->registry->register('mock2', $secondProvider);

    $all = $this->registry->all();

    expect($all)->toHaveCount(2);
    expect($all['mock1'])->toBe($this->mockProvider);
    expect($all['mock2'])->toBe($secondProvider);
});

it('can get available providers', function () {
    $this->registry->register('mock1', $this->mockProvider);

    $unavailableProvider = new MockExchangeRateProvider([
        'name'      => 'Unavailable Provider',
        'available' => false,
    ]);
    $this->registry->register('mock2', $unavailableProvider);

    $available = $this->registry->available();

    expect($available)->toHaveCount(1);
    expect($available['mock1'])->toBe($this->mockProvider);
});

it('can get providers by priority', function () {
    $lowPriority = new MockExchangeRateProvider(['priority' => 10]);
    $highPriority = new MockExchangeRateProvider(['priority' => 100]);
    $midPriority = new MockExchangeRateProvider(['priority' => 50]);

    $this->registry->register('low', $lowPriority);
    $this->registry->register('high', $highPriority);
    $this->registry->register('mid', $midPriority);

    $sorted = $this->registry->byPriority();

    expect($sorted->first())->toBe($highPriority);
    expect($sorted->last())->toBe($lowPriority);
});

it('can find providers by currency pair', function () {
    $this->registry->register('mock', $this->mockProvider);

    $providers = $this->registry->findByCurrencyPair('USD', 'EUR');

    expect($providers)->toHaveCount(1);
    expect($providers['mock'])->toBe($this->mockProvider);

    $unsupported = $this->registry->findByCurrencyPair('XXX', 'YYY');
    expect($unsupported)->toBeEmpty();
});

it('can get rate from first available provider', function () {
    $this->registry->register('mock', $this->mockProvider);

    $quote = $this->registry->getRate('USD', 'EUR');

    expect($quote->fromCurrency)->toBe('USD');
    expect($quote->toCurrency)->toBe('EUR');
    expect($quote->provider)->toBe('Mock Provider');
});

it('throws exception when no providers available for pair', function () {
    $this->registry->register('mock', $this->mockProvider);

    expect(fn () => $this->registry->getRate('XXX', 'YYY'))
        ->toThrow(RateProviderException::class, 'No providers available');
});

it('can get rates from all providers', function () {
    $this->registry->register('mock1', $this->mockProvider);

    $secondProvider = new MockExchangeRateProvider(['name' => 'Second Provider']);
    $this->registry->register('mock2', $secondProvider);

    $rates = $this->registry->getRatesFromAll('USD', 'EUR');

    expect($rates)->toHaveCount(2);
    expect($rates)->toHaveKeys(['mock1', 'mock2']);
    expect($rates['mock1']->provider)->toBe('Mock Provider');
    expect($rates['mock2']->provider)->toBe('Second Provider');
});

it('can get aggregated rate', function () {
    $this->registry->register('mock1', $this->mockProvider);

    $secondProvider = new MockExchangeRateProvider(['name' => 'Second Provider']);
    $this->registry->register('mock2', $secondProvider);

    $aggregated = $this->registry->getAggregatedRate('USD', 'EUR');

    expect($aggregated->provider)->toBe('aggregated');
    expect($aggregated->metadata['providers'])->toContain('mock1');
    expect($aggregated->metadata['providers'])->toContain('mock2');
    expect($aggregated->metadata['count'])->toBe(2);
});

it('can remove provider', function () {
    $this->registry->register('mock', $this->mockProvider);

    expect($this->registry->has('mock'))->toBeTrue();

    $this->registry->remove('mock');

    expect($this->registry->has('mock'))->toBeFalse();
});

it('clears default when removing default provider', function () {
    $this->registry->register('mock', $this->mockProvider);

    $this->registry->remove('mock');

    expect(fn () => $this->registry->getDefault())
        ->toThrow(RateProviderException::class);
});
