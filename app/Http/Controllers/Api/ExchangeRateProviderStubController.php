<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateProviderStubController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            [
            'status' => 'success',
            'data' => [
                [
                    'name' => 'ecb',
                    'enabled' => true,
                    'priority' => 1,
                    'supported_currencies' => ['EUR', 'USD', 'GBP', 'JPY'],
                    'update_frequency' => '15min',
                    'last_update' => now()->subMinutes(5)->toIso8601String(),
                ],
                [
                    'name' => 'fixer',
                    'enabled' => true,
                    'priority' => 2,
                    'supported_currencies' => ['EUR', 'USD', 'GBP', 'JPY', 'CHF'],
                    'update_frequency' => '1hour',
                    'last_update' => now()->subMinutes(30)->toIso8601String(),
                ],
            ],
            ]
        );
    }

    public function getRate(Request $request, $provider): JsonResponse
    {
        $request->merge(['provider' => $provider]);
        $validated = $request->validate(
            [
            'provider' => 'required|in:ecb,fixer,openexchange',
            'from' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'to' => 'required|in:EUR,USD,GBP,JPY,CHF',
            ]
        );

        return response()->json(
            [
            'status' => 'success',
            'data' => [
                'provider' => $provider,
                'from' => $validated['from'],
                'to' => $validated['to'],
                'rate' => 1.08,
                'inverse_rate' => 0.926,
                'timestamp' => now()->timestamp,
                'source' => 'live',
            ],
            ]
        );
    }

    public function compareRates(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
            'from' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'to' => 'required|in:EUR,USD,GBP,JPY,CHF',
            ]
        );

        return response()->json(
            [
            'status' => 'success',
            'data' => [
                'from' => $validated['from'],
                'to' => $validated['to'],
                'providers' => [
                    [
                        'name' => 'ecb',
                        'rate' => 1.08,
                        'inverse_rate' => 0.926,
                        'timestamp' => now()->timestamp,
                        'difference_from_average' => 0.0,
                    ],
                    [
                        'name' => 'fixer',
                        'rate' => 1.082,
                        'inverse_rate' => 0.924,
                        'timestamp' => now()->timestamp,
                        'difference_from_average' => 0.002,
                    ],
                ],
                'average_rate' => 1.081,
                'best_rate' => 1.082,
                'worst_rate' => 1.08,
                'spread' => 0.002,
            ],
            ]
        );
    }

    public function getAggregatedRate(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
            'from' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'to' => 'required|in:EUR,USD,GBP,JPY,CHF',
            ]
        );

        return response()->json(
            [
            'status' => 'success',
            'data' => [
                'from' => $validated['from'],
                'to' => $validated['to'],
                'rate' => 1.081,
                'inverse_rate' => 0.925,
                'method' => 'weighted_average',
                'sources_used' => 2,
                'confidence' => 0.98,
                'timestamp' => now()->timestamp,
            ],
            ]
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
            'providers' => 'sometimes|array',
            'providers.*' => 'string|in:ecb,fixer,openexchange',
            ]
        );

        return response()->json(
            [
            'status' => 'success',
            'data' => [
                'refreshed_providers' => $validated['providers'] ?? ['ecb', 'fixer'],
                'updated_rates_count' => 42,
                'failed_providers' => [],
                'timestamp' => now()->timestamp,
            ],
            ]
        );
    }

    public function historical(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
            'from' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'to' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            ]
        );

        return response()->json(
            [
            'status' => 'success',
            'data' => [
                'from' => $validated['from'],
                'to' => $validated['to'],
                'period' => [
                    'start' => $validated['start_date'],
                    'end' => $validated['end_date'],
                ],
                'rates' => [
                    [
                        'date' => '2025-01-01',
                        'rate' => 1.08,
                        'provider' => 'ecb',
                    ],
                    [
                        'date' => '2025-01-02',
                        'rate' => 1.082,
                        'provider' => 'ecb',
                    ],
                ],
                'statistics' => [
                    'average' => 1.081,
                    'min' => 1.08,
                    'max' => 1.082,
                    'volatility' => 0.002,
                ],
            ],
            ]
        );
    }

    public function validateRate(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
            'from' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'to' => 'required|in:EUR,USD,GBP,JPY,CHF',
            'rate' => 'required|numeric|min:0',
            ]
        );

        $marketRate = 1.08;
        $deviation = abs($validated['rate'] - $marketRate);
        $deviationPercentage = ($deviation / $marketRate) * 100;

        return response()->json(
            [
            'status' => 'success',
            'data' => [
                'is_valid' => $deviationPercentage < 5,
                'confidence_score' => max(0, 1 - ($deviationPercentage / 100)),
                'market_rate' => $marketRate,
                'deviation' => $deviation,
                'deviation_percentage' => $deviationPercentage,
                'warnings' => $deviationPercentage > 2 ? ['Rate deviates significantly from market'] : [],
            ],
            ]
        );
    }
}
