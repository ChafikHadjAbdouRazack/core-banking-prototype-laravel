<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Exchange\Services\EnhancedExchangeRateService;
use App\Domain\Exchange\Services\ExchangeRateProviderRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateProviderController extends Controller
{
    public function __construct(
        private readonly ExchangeRateProviderRegistry $registry,
        private readonly EnhancedExchangeRateService $service
    ) {}

    /**
     * List available exchange rate providers
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $providers = [];
        
        foreach ($this->registry->all() as $name => $provider) {
            $providers[] = [
                'name' => $name,
                'display_name' => $provider->getName(),
                'available' => $provider->isAvailable(),
                'priority' => $provider->getPriority(),
                'capabilities' => $provider->getCapabilities()->toArray(),
                'supported_currencies' => $provider->getSupportedCurrencies(),
            ];
        }
        
        return response()->json([
            'data' => $providers,
            'default' => $this->registry->names()[0] ?? null,
        ]);
    }

    /**
     * Get exchange rate from a specific provider
     * 
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     */
    public function getRate(Request $request, string $provider): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);
        
        try {
            $providerInstance = $this->registry->get($provider);
            
            if (!$providerInstance->isAvailable()) {
                return response()->json([
                    'error' => 'Provider is not available',
                ], 503);
            }
            
            $quote = $providerInstance->getRate($validated['from'], $validated['to']);
            
            return response()->json([
                'data' => $quote->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get exchange rate',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Compare rates from all available providers
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function compareRates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);
        
        try {
            $comparison = $this->service->compareRates($validated['from'], $validated['to']);
            
            return response()->json([
                'data' => $comparison,
                'pair' => "{$validated['from']}/{$validated['to']}",
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to compare rates',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get aggregated rate from multiple providers
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAggregatedRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);
        
        try {
            $quote = $this->registry->getAggregatedRate($validated['from'], $validated['to']);
            
            return response()->json([
                'data' => $quote->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get aggregated rate',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refresh exchange rates
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pairs' => 'nullable|array',
            'pairs.*' => 'string|regex:/^[A-Z]{3}\/[A-Z]{3}$/',
        ]);
        
        try {
            if (isset($validated['pairs'])) {
                // Refresh specific pairs
                $results = ['refreshed' => [], 'failed' => []];
                
                foreach ($validated['pairs'] as $pair) {
                    [$from, $to] = explode('/', $pair);
                    try {
                        $this->service->fetchAndStoreRate($from, $to);
                        $results['refreshed'][] = $pair;
                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'pair' => $pair,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            } else {
                // Refresh all active rates
                $results = $this->service->refreshAllRates();
            }
            
            return response()->json([
                'message' => 'Exchange rates refreshed',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to refresh rates',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get historical rates
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function historical(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        try {
            $rates = $this->service->getHistoricalRates(
                $validated['from'],
                $validated['to'],
                new \DateTime($validated['start_date']),
                new \DateTime($validated['end_date'])
            );
            
            return response()->json([
                'data' => $rates,
                'pair' => "{$validated['from']}/{$validated['to']}",
                'period' => [
                    'start' => $validated['start_date'],
                    'end' => $validated['end_date'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get historical rates',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate a specific exchange rate
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0',
            'provider' => 'nullable|string',
        ]);
        
        try {
            // Create a quote from the provided data
            $quote = new \App\Domain\Exchange\ValueObjects\ExchangeRateQuote(
                fromCurrency: $validated['from'],
                toCurrency: $validated['to'],
                rate: (float) $validated['rate'],
                bid: (float) $validated['rate'] * 0.999,
                ask: (float) $validated['rate'] * 1.001,
                provider: $validated['provider'] ?? 'manual',
                timestamp: now()
            );
            
            $validation = $this->service->validateQuote($quote);
            
            return response()->json([
                'data' => $validation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to validate rate',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}