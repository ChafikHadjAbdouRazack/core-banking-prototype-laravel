<?php

namespace App\Http\Controllers;

use App\Domain\Exchange\Projections\Order;
use App\Domain\Exchange\Projections\Trade;
use App\Domain\Exchange\Services\ExchangeService;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeController extends Controller
{
    private ExchangeService $exchangeService;

    public function __construct(ExchangeService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }

    public function index(Request $request)
    {
        $baseCurrency = $request->input('base', 'BTC');
        $quoteCurrency = $request->input('quote', 'EUR');

        // Get tradeable assets
        $assets = Asset::where('is_tradeable', true)
            ->orderBy('code')
            ->get();

        // Get order book
        $orderBook = $this->exchangeService->getOrderBook($baseCurrency, $quoteCurrency, 20);

        // Get user's open orders
        $userOrders = collect();
        if (Auth::check() && Auth::user()->account) {
            $userOrders = Order::forAccount(Auth::user()->account->id)
                ->open()
                ->forPair($baseCurrency, $quoteCurrency)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Get recent trades
        $recentTrades = Trade::forPair($baseCurrency, $quoteCurrency)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get market data for various pairs
        $markets = $this->getMarketPairs();

        return view(
            'exchange.index', [
            'baseCurrency'  => $baseCurrency,
            'quoteCurrency' => $quoteCurrency,
            'assets'        => $assets,
            'orderBook'     => $orderBook,
            'userOrders'    => $userOrders,
            'recentTrades'  => $recentTrades,
            'markets'       => $markets,
            ]
        );
    }

    public function orders()
    {
        $account = Auth::user()->account;

        if (! $account) {
            return redirect()->route('dashboard')->with('error', 'Please complete your account setup first.');
        }

        $orders = Order::forAccount($account->id)
            ->with(['relatedTrades'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view(
            'exchange.orders', [
            'orders' => $orders,
            ]
        );
    }

    public function trades()
    {
        $account = Auth::user()->account;

        if (! $account) {
            return redirect()->route('dashboard')->with('error', 'Please complete your account setup first.');
        }

        $trades = Trade::forAccount($account->id)
            ->with(['buyOrder', 'sellOrder'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate total fees
        $totalFees = Trade::forAccount($account->id)
            ->selectRaw(
                'SUM(CASE WHEN buyer_account_id = ? THEN 
                           CASE WHEN maker_side = \'buy\' THEN maker_fee ELSE taker_fee END
                         ELSE 
                           CASE WHEN maker_side = \'sell\' THEN maker_fee ELSE taker_fee END
                         END) as total_fees', [$account->id]
            )
            ->value('total_fees') ?? '0';

        return view(
            'exchange.trades', [
            'trades'    => $trades,
            'totalFees' => $totalFees,
            ]
        );
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate(
            [
            'type'           => ['required', 'in:buy,sell'],
            'order_type'     => ['required', 'in:market,limit'],
            'base_currency'  => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
            'amount'         => ['required', 'numeric', 'gt:0'],
            'price'          => ['required_if:order_type,limit', 'nullable', 'numeric', 'gt:0'],
            ]
        );

        $account = Auth::user()->account;

        if (! $account) {
            return redirect()->back()->with('error', 'Please complete your account setup first.');
        }

        try {
            $result = $this->exchangeService->placeOrder(
                accountId: $account->id,
                type: $validated['type'],
                orderType: $validated['order_type'],
                baseCurrency: $validated['base_currency'],
                quoteCurrency: $validated['quote_currency'],
                amount: $validated['amount'],
                price: $validated['price'] ?? null,
                metadata: [
                    'source'  => 'web',
                    'user_id' => Auth::id(),
                ]
            );

            return redirect()->route(
                'exchange.index', [
                'base'  => $validated['base_currency'],
                'quote' => $validated['quote_currency'],
                ]
            )->with('success', 'Order placed successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function cancelOrder(string $orderId)
    {
        $account = Auth::user()->account;

        if (! $account) {
            return redirect()->back()->with('error', 'Please complete your account setup first.');
        }

        // Verify order belongs to user
        $order = Order::where('order_id', $orderId)
            ->where('account_id', $account->id)
            ->first();

        if (! $order) {
            return redirect()->back()->with('error', 'Order not found');
        }

        try {
            $this->exchangeService->cancelOrder($orderId);

            return redirect()->back()->with('success', 'Order cancelled successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get market pairs data.
     */
    private function getMarketPairs(): array
    {
        $pairs = [
            ['base' => 'BTC', 'quote' => 'EUR'],
            ['base' => 'ETH', 'quote' => 'EUR'],
            ['base' => 'BTC', 'quote' => 'USD'],
            ['base' => 'ETH', 'quote' => 'USD'],
        ];

        $markets = [];
        foreach ($pairs as $pair) {
            $marketData = $this->exchangeService->getMarketData($pair['base'], $pair['quote']);
            $markets[] = $marketData;
        }

        return $markets;
    }
}
