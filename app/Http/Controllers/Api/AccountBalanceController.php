<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Domain\Asset\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group Account Balances
 * 
 * APIs for managing multi-asset account balances
 */
class AccountBalanceController extends Controller
{
    /**
     * Get account balances
     * 
     * Retrieve all asset balances for a specific account.
     * 
     * @urlParam uuid string required The account UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @queryParam asset string Filter by specific asset code. Example: USD
     * @queryParam positive boolean Only show positive balances. Example: true
     * 
     * @response 200 {
     *   "data": {
     *     "account_uuid": "550e8400-e29b-41d4-a716-446655440000",
     *     "balances": [
     *       {
     *         "asset_code": "USD",
     *         "balance": 150000,
     *         "formatted": "1500.00 USD",
     *         "asset": {
     *           "code": "USD",
     *           "name": "US Dollar",
     *           "type": "fiat",
     *           "symbol": "$",
     *           "precision": 2
     *         }
     *       },
     *       {
     *         "asset_code": "BTC",
     *         "balance": 5000000,
     *         "formatted": "0.05000000 BTC",
     *         "asset": {
     *           "code": "BTC",
     *           "name": "Bitcoin",
     *           "type": "crypto",
     *           "symbol": "₿",
     *           "precision": 8
     *         }
     *       }
     *     ],
     *     "summary": {
     *       "total_assets": 2,
     *       "total_usd_equivalent": "3250.00"
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Account not found",
     *   "error": "The specified account UUID was not found"
     * }
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $account = Account::where('uuid', $uuid)->first();
        
        if (!$account) {
            return response()->json([
                'message' => 'Account not found',
                'error' => 'The specified account UUID was not found',
            ], 404);
        }
        
        $query = $account->balances()->with('asset');
        
        // Filter by specific asset
        if ($request->has('asset')) {
            $query->where('asset_code', strtoupper($request->string('asset')->toString()));
        }
        
        // Filter positive balances only
        if ($request->boolean('positive')) {
            $query->where('balance', '>', 0);
        }
        
        $balances = $query->get();
        
        // Calculate USD equivalent for summary
        $totalUsdEquivalent = $this->calculateUsdEquivalent($balances);
        
        return response()->json([
            'data' => [
                'account_uuid' => $account->uuid,
                'balances' => $balances->map(function ($balance) {
                    $asset = $balance->asset;
                    $formatted = $this->formatAmount($balance->balance, $asset);
                    
                    return [
                        'asset_code' => $balance->asset_code,
                        'balance' => $balance->balance,
                        'formatted' => $formatted,
                        'asset' => [
                            'code' => $asset->code,
                            'name' => $asset->name,
                            'type' => $asset->type,
                            'symbol' => $asset->symbol,
                            'precision' => $asset->precision,
                        ],
                    ];
                }),
                'summary' => [
                    'total_assets' => $balances->where('balance', '>', 0)->count(),
                    'total_usd_equivalent' => number_format($totalUsdEquivalent, 2),
                ],
            ],
        ]);
    }
    
    /**
     * List all account balances
     * 
     * Get balances across all accounts with filtering and aggregation options.
     * 
     * @queryParam asset string Filter by specific asset code. Example: USD
     * @queryParam min_balance integer Minimum balance filter (in smallest unit). Example: 1000
     * @queryParam user_uuid string Filter by account owner. Example: 550e8400-e29b-41d4-a716-446655440000
     * @queryParam limit integer Number of results per page (max 100). Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "account_uuid": "550e8400-e29b-41d4-a716-446655440000",
     *       "asset_code": "USD",
     *       "balance": 150000,
     *       "formatted": "1500.00 USD",
     *       "account": {
     *         "uuid": "550e8400-e29b-41d4-a716-446655440000",
     *         "user_uuid": "123e4567-e89b-12d3-a456-426614174000"
     *       }
     *     }
     *   ],
     *   "meta": {
     *     "total_accounts": 150,
     *     "total_balances": 320,
     *     "asset_totals": {
     *       "USD": "12500000.00",
     *       "EUR": "3250000.00",
     *       "BTC": "2.50000000"
     *     }
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'asset' => 'sometimes|string|size:3',
            'min_balance' => 'sometimes|integer|min:0',
            'user_uuid' => 'sometimes|uuid',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);
        
        $query = \App\Models\AccountBalance::with(['account', 'asset']);
        
        // Apply filters
        if ($request->has('asset')) {
            $query->where('asset_code', strtoupper($request->string('asset')->toString()));
        }
        
        if ($request->has('min_balance')) {
            $query->where('balance', '>=', $request->integer('min_balance'));
        }
        
        if ($request->has('user_uuid')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('user_uuid', $request->string('user_uuid')->toString());
            });
        }
        
        $limit = $request->integer('limit', 50);
        $balances = $query->orderBy('balance', 'desc')->limit($limit)->get();
        
        // Calculate aggregations
        $totalAccounts = Account::count();
        $totalBalances = \App\Models\AccountBalance::count();
        $assetTotals = $this->calculateAssetTotals();
        
        return response()->json([
            'data' => $balances->map(function ($balance) {
                return [
                    'account_uuid' => $balance->account_uuid,
                    'asset_code' => $balance->asset_code,
                    'balance' => $balance->balance,
                    'formatted' => $this->formatAmount($balance->balance, $balance->asset),
                    'account' => [
                        'uuid' => $balance->account->uuid,
                        'user_uuid' => $balance->account->user_uuid,
                    ],
                ];
            }),
            'meta' => [
                'total_accounts' => $totalAccounts,
                'total_balances' => $totalBalances,
                'asset_totals' => $assetTotals,
            ],
        ]);
    }
    
    private function formatAmount(int $amount, Asset $asset): string
    {
        $formatted = number_format(
            $amount / (10 ** $asset->precision),
            $asset->precision,
            '.',
            ''
        );
        
        return "{$formatted} {$asset->code}";
    }
    
    private function calculateUsdEquivalent($balances): float
    {
        // Calculate without caching to avoid type issues
        $total = 0.0;
        
        foreach ($balances as $balance) {
            if ($balance->asset_code === 'USD') {
                $total += $balance->balance / 100; // USD is stored in cents
            } else {
                // For now, return 0 for non-USD. In production, you'd convert using exchange rates
                // $rate = app(ExchangeRateService::class)->getRate($balance->asset_code, 'USD');
                // if ($rate) {
                //     $usdAmount = $rate->convert($balance->balance);
                //     $total += $usdAmount / 100;
                // }
            }
        }
        
        return $total;
    }
    
    private function calculateAssetTotals(): array
    {
        return Cache::remember('asset_totals', 300, function () {
            $totals = \App\Models\AccountBalance::selectRaw('asset_code, SUM(balance) as total')
                ->groupBy('asset_code')
                ->with('asset')
                ->get()
                ->mapWithKeys(function ($item) {
                    $asset = Asset::where('code', $item->asset_code)->first();
                    if ($asset) {
                        $formatted = number_format(
                            $item->total / (10 ** $asset->precision),
                            $asset->precision,
                            '.',
                            ''
                        );
                        return [$item->asset_code => $formatted];
                    }
                    return [$item->asset_code => (string) $item->total];
                });
            
            return $totals->toArray();
        });
    }
}