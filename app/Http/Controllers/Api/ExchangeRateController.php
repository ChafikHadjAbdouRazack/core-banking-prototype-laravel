<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Asset\Models\ExchangeRate;
use App\Domain\Asset\Services\ExchangeRateService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Exchange Rates
 * 
 * APIs for managing exchange rates between different assets
 */
class ExchangeRateController extends Controller
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService
    ) {}
    
    /**
     * Get current exchange rate
     * 
     * Retrieve the current exchange rate between two assets.
     * 
     * @urlParam from string required The source asset code. Example: USD
     * @urlParam to string required The target asset code. Example: EUR
     * 
     * @response 200 {
     *   "data": {
     *     "from_asset": "USD",
     *     "to_asset": "EUR",
     *     "rate": "0.8500000000",
     *     "inverse_rate": "1.1764705882",
     *     "source": "api",
     *     "valid_at": "2025-06-15T10:30:00Z",
     *     "expires_at": "2025-06-15T22:30:00Z",
     *     "is_active": true,
     *     "age_minutes": 15,
     *     "metadata": {
     *       "provider": "fixer.io",
     *       "confidence": 0.95
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Exchange rate not found",
     *   "error": "No active exchange rate found for the specified asset pair"
     * }
     */
    public function show(string $from, string $to): JsonResponse
    {
        $fromAsset = strtoupper($from);
        $toAsset = strtoupper($to);
        
        $rate = $this->exchangeRateService->getRate($fromAsset, $toAsset);
        
        if (!$rate) {
            return response()->json([
                'message' => 'Exchange rate not found',
                'error' => 'No active exchange rate found for the specified asset pair',
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'from_asset' => $rate->from_asset_code,
                'to_asset' => $rate->to_asset_code,
                'rate' => $rate->rate,
                'inverse_rate' => number_format($rate->getInverseRate(), 10, '.', ''),
                'source' => $rate->source,
                'valid_at' => $rate->valid_at->toISOString(),
                'expires_at' => $rate->expires_at?->toISOString(),
                'is_active' => $rate->is_active,
                'age_minutes' => $rate->getAgeInMinutes(),
                'metadata' => $rate->metadata,
            ],
        ]);
    }
    
    /**
     * Convert amount between assets
     * 
     * Convert an amount from one asset to another using current exchange rates.
     * 
     * @urlParam from string required The source asset code. Example: USD
     * @urlParam to string required The target asset code. Example: EUR
     * @queryParam amount numeric required The amount to convert (in smallest unit). Example: 10000
     * 
     * @response 200 {
     *   "data": {
     *     "from_asset": "USD",
     *     "to_asset": "EUR",
     *     "from_amount": 10000,
     *     "to_amount": 8500,
     *     "from_formatted": "100.00 USD",
     *     "to_formatted": "85.00 EUR",
     *     "rate": "0.8500000000",
     *     "rate_age_minutes": 15
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "amount": ["The amount field is required."]
     *   }
     * }
     */
    public function convert(Request $request, string $from, string $to): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);
        
        $fromAsset = strtoupper($from);
        $toAsset = strtoupper($to);
        $amount = (int) $request->input('amount');
        
        $convertedAmount = $this->exchangeRateService->convert($amount, $fromAsset, $toAsset);
        
        if ($convertedAmount === null) {
            return response()->json([
                'message' => 'Conversion not available',
                'error' => 'No active exchange rate found for the specified asset pair',
            ], 404);
        }
        
        $rate = $this->exchangeRateService->getRate($fromAsset, $toAsset);
        
        // Get asset details for formatting
        $fromAssetModel = \App\Domain\Asset\Models\Asset::where('code', $fromAsset)->first();
        $toAssetModel = \App\Domain\Asset\Models\Asset::where('code', $toAsset)->first();
        
        $fromFormatted = $this->formatAmount($amount, $fromAssetModel);
        $toFormatted = $this->formatAmount($convertedAmount, $toAssetModel);
        
        return response()->json([
            'data' => [
                'from_asset' => $fromAsset,
                'to_asset' => $toAsset,
                'from_amount' => $amount,
                'to_amount' => $convertedAmount,
                'from_formatted' => $fromFormatted,
                'to_formatted' => $toFormatted,
                'rate' => $rate->rate,
                'rate_age_minutes' => $rate->getAgeInMinutes(),
            ],
        ]);
    }
    
    /**
     * List exchange rates
     * 
     * Get a list of available exchange rates with filtering options.
     * 
     * @queryParam from string Filter by source asset code. Example: USD
     * @queryParam to string Filter by target asset code. Example: EUR
     * @queryParam source string Filter by rate source (manual, api, oracle, market). Example: api
     * @queryParam active boolean Filter by active status. Example: true
     * @queryParam valid boolean Filter by validity (not expired). Example: true
     * @queryParam limit integer Number of results per page (max 100). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "from_asset": "USD",
     *       "to_asset": "EUR",
     *       "rate": "0.8500000000",
     *       "inverse_rate": "1.1764705882",
     *       "source": "api",
     *       "valid_at": "2025-06-15T10:30:00Z",
     *       "expires_at": "2025-06-15T22:30:00Z",
     *       "is_active": true,
     *       "age_minutes": 15
     *     }
     *   ],
     *   "meta": {
     *     "total": 25,
     *     "valid": 23,
     *     "stale": 2
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'sometimes|string|size:3',
            'to' => 'sometimes|string|size:3',
            'source' => ['sometimes', Rule::in(['manual', 'api', 'oracle', 'market'])],
            'active' => 'sometimes|boolean',
            'valid' => 'sometimes|boolean',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);
        
        $query = ExchangeRate::query();
        
        // Apply filters
        if ($request->has('from')) {
            $query->where('from_asset_code', strtoupper($request->string('from')->toString()));
        }
        
        if ($request->has('to')) {
            $query->where('to_asset_code', strtoupper($request->string('to')->toString()));
        }
        
        if ($request->has('source')) {
            $query->where('source', $request->string('source')->toString());
        }
        
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        if ($request->has('valid') && $request->boolean('valid')) {
            $query->valid();
        }
        
        $limit = $request->integer('limit', 50);
        $rates = $query->orderBy('valid_at', 'desc')->limit($limit)->get();
        
        // Calculate metadata
        $total = ExchangeRate::count();
        $valid = ExchangeRate::valid()->count();
        $stale = ExchangeRate::where('valid_at', '<=', now()->subDay())->count();
        
        return response()->json([
            'data' => $rates->map(function (ExchangeRate $rate) {
                return [
                    'id' => $rate->id,
                    'from_asset' => $rate->from_asset_code,
                    'to_asset' => $rate->to_asset_code,
                    'rate' => $rate->rate,
                    'inverse_rate' => number_format($rate->getInverseRate(), 10, '.', ''),
                    'source' => $rate->source,
                    'valid_at' => $rate->valid_at->toISOString(),
                    'expires_at' => $rate->expires_at?->toISOString(),
                    'is_active' => $rate->is_active,
                    'age_minutes' => $rate->getAgeInMinutes(),
                ];
            }),
            'meta' => [
                'total' => $total,
                'valid' => $valid,
                'stale' => $stale,
            ],
        ]);
    }
    
    private function formatAmount(int $amount, ?\App\Domain\Asset\Models\Asset $asset): string
    {
        if (!$asset) {
            return (string) $amount;
        }
        
        $formatted = number_format(
            $amount / (10 ** $asset->precision),
            $asset->precision,
            '.',
            ''
        );
        
        return "{$formatted} {$asset->code}";
    }
}