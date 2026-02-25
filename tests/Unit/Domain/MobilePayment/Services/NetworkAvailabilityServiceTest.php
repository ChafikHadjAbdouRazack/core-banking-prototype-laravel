<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

use App\Domain\MobilePayment\Enums\PaymentNetwork;
use App\Domain\MobilePayment\Services\NetworkAvailabilityService;

describe('NetworkAvailabilityService', function (): void {
    it('can be instantiated', function (): void {
        $service = new NetworkAvailabilityService();

        expect($service)->toBeInstanceOf(NetworkAvailabilityService::class);
    });

    it('has getNetworkStatuses method', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);

        expect($reflection->hasMethod('getNetworkStatuses'))->toBeTrue();
        expect($reflection->getMethod('getNetworkStatuses')->isPublic())->toBeTrue();
    });

    it('has getNetworkStatus method accepting PaymentNetwork', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getNetworkStatus');

        expect($method->isPublic())->toBeTrue();
        $params = $method->getParameters();
        expect($params[0]->getType()->getName())->toBe(PaymentNetwork::class);
    });

    it('returns correct average fee for Solana via reflection', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getAverageFeeUsd');
        $method->setAccessible(true);

        expect($method->invoke($service, PaymentNetwork::SOLANA))->toBe('0.001');
    });

    it('returns correct average fee for Tron via reflection', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getAverageFeeUsd');
        $method->setAccessible(true);

        expect($method->invoke($service, PaymentNetwork::TRON))->toBe('0.50');
    });

    it('returns correct average confirmation time for Solana', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getAvgConfirmationSeconds');
        $method->setAccessible(true);

        expect($method->invoke($service, PaymentNetwork::SOLANA))->toBe(5);
    });

    it('returns correct average confirmation time for Tron', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getAvgConfirmationSeconds');
        $method->setAccessible(true);

        expect($method->invoke($service, PaymentNetwork::TRON))->toBe(3);
    });

    it('returns low congestion level by default', function (): void {
        $service = new NetworkAvailabilityService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getCongestionLevel');
        $method->setAccessible(true);

        expect($method->invoke($service, PaymentNetwork::SOLANA))->toBe('low');
        expect($method->invoke($service, PaymentNetwork::TRON))->toBe('low');
    });
});
