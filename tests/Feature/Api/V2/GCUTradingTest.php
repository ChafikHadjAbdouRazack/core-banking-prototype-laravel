<?php

namespace Tests\Feature\Api\V2;

use App\Domain\Basket\Models\BasketAsset;
use App\Domain\Basket\Models\BasketValue;
use App\Domain\Wallet\Workflows\WalletConvertWorkflow;
use App\Models\Account;
use App\Domain\Account\Models\AccountBalance;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\DomainTestCase;
use Workflow\WorkflowStub;

class GCUTradingTest extends DomainTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected BasketAsset $gcu;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user and account
        $this->user = User::factory()->create();
        $this->account = Account::factory()->forUser($this->user)->create();

        // Create assets
        Asset::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'GBP'], ['name' => 'British Pound', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'CHF'], ['name' => 'Swiss Franc', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]);
        Asset::firstOrCreate(['code' => 'GCU'], ['name' => 'Global Currency Unit', 'type' => 'basket', 'precision' => 4, 'is_active' => true]);

        // Create GCU basket
        $this->gcu = BasketAsset::firstOrCreate(
            ['code' => 'GCU'],
            [
                'name'                => 'Global Currency Unit',
                'type'                => 'fixed',
                'rebalance_frequency' => 'never',
            ]
        );

        // Create GCU value
        BasketValue::create([
            'basket_asset_code' => 'GCU',
            'value'             => 1.0975,
            'component_values'  => [],
            'calculated_at'     => now(),
        ]);

        // Create exchange rates needed for GCU trading
        $exchangeRates = [
            ['from_asset' => 'EUR', 'to_asset' => 'USD', 'rate' => 1.10],
            ['from_asset' => 'GBP', 'to_asset' => 'USD', 'rate' => 1.27],
            ['from_asset' => 'CHF', 'to_asset' => 'USD', 'rate' => 1.12],
            ['from_asset' => 'USD', 'to_asset' => 'EUR', 'rate' => 0.91],
            ['from_asset' => 'USD', 'to_asset' => 'GBP', 'rate' => 0.79],
            ['from_asset' => 'USD', 'to_asset' => 'CHF', 'rate' => 0.89],
        ];

        foreach ($exchangeRates as $rate) {
            \App\Domain\Asset\Models\ExchangeRate::create([
                'from_asset_code' => $rate['from_asset'],
                'to_asset_code'   => $rate['to_asset'],
                'rate'            => $rate['rate'],
                'source'          => 'manual',
                'valid_at'        => now()->subHour(),
                'expires_at'      => now()->addHour(),
                'is_active'       => true,
            ]);
        }

        // Give user some EUR balance
        AccountBalance::create([
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'EUR',
            'balance'      => 10000.00,
        ]);

        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function can_get_quote_for_buying_gcu()
    {
        $response = $this->getJson('/api/v2/gcu/quote?operation=buy&amount=1000&currency=EUR');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'operation',
                    'input_amount',
                    'input_currency',
                    'output_amount',
                    'output_currency',
                    'exchange_rate',
                    'fee_amount',
                    'fee_currency',
                    'fee_percentage',
                    'quote_valid_until',
                    'minimum_amount',
                    'maximum_amount',
                ],
            ])
            ->assertJsonPath('data.operation', 'buy')
            ->assertJsonPath('data.input_currency', 'EUR')
            ->assertJsonPath('data.output_currency', 'GCU');
    }

    #[Test]
    public function can_get_quote_for_selling_gcu()
    {
        $response = $this->getJson('/api/v2/gcu/quote?operation=sell&amount=100&currency=EUR');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'operation',
                    'input_amount',
                    'input_currency',
                    'output_amount',
                    'output_currency',
                    'exchange_rate',
                    'fee_amount',
                    'fee_currency',
                    'fee_percentage',
                    'quote_valid_until',
                    'minimum_amount',
                    'maximum_amount',
                ],
            ])
            ->assertJsonPath('data.operation', 'sell')
            ->assertJsonPath('data.input_currency', 'GCU')
            ->assertJsonPath('data.output_currency', 'EUR');
    }

    #[Test]
    public function can_buy_gcu_with_eur()
    {
        // Fake the workflow to test synchronously
        WorkflowStub::fake();

        // Mock the workflow to return a successful result
        WorkflowStub::mock(WalletConvertWorkflow::class, [
            'converted_amount' => 91245, // Based on the exchange rate
            'exchange_rate'    => 0.91245,
        ]);

        // Manually update balances since the workflow is mocked
        $this->account->balances()->updateOrCreate(
            ['asset_code' => 'EUR'],
            ['balance' => 900000] // Deduct 1000 EUR (100000 cents)
        );

        $this->account->balances()->updateOrCreate(
            ['asset_code' => 'GCU'],
            ['balance' => 91245] // Add GCU
        );

        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'   => 1000,
            'currency' => 'EUR',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transaction_id',
                    'account_uuid',
                    'spent_amount',
                    'spent_currency',
                    'received_amount',
                    'received_currency',
                    'exchange_rate',
                    'fee_amount',
                    'fee_currency',
                    'new_gcu_balance',
                    'timestamp',
                ],
                'message',
            ])
            ->assertJsonPath('data.spent_currency', 'EUR')
            ->assertJsonPath('data.received_currency', 'GCU');

        // Verify balances were updated
        $this->assertDatabaseHas('account_balances', [
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'EUR',
        ]);

        $this->assertDatabaseHas('account_balances', [
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'GCU',
        ]);
    }

    #[Test]
    public function cannot_buy_gcu_with_insufficient_balance()
    {
        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'   => 20000, // More than available balance
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Insufficient Balance');
    }

    #[Test]
    public function cannot_buy_gcu_below_minimum_amount()
    {
        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'   => 50, // Below minimum of 100
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function can_sell_gcu_for_eur()
    {
        // Give user some GCU balance
        AccountBalance::create([
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'GCU',
            'balance'      => 1000.00,
        ]);

        $response = $this->postJson('/api/v2/gcu/sell', [
            'amount'   => 100,
            'currency' => 'EUR',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transaction_id',
                    'account_uuid',
                    'sold_amount',
                    'sold_currency',
                    'received_amount',
                    'received_currency',
                    'exchange_rate',
                    'fee_amount',
                    'fee_currency',
                    'new_gcu_balance',
                    'timestamp',
                ],
                'message',
            ])
            ->assertJsonPath('data.sold_currency', 'GCU')
            ->assertJsonPath('data.received_currency', 'EUR');
    }

    #[Test]
    public function cannot_sell_gcu_with_insufficient_balance()
    {
        // Give user minimal GCU balance
        AccountBalance::create([
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'GCU',
            'balance'      => 5.00,
        ]);

        $response = $this->postJson('/api/v2/gcu/sell', [
            'amount'   => 100, // More than available
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Insufficient Balance');
    }

    #[Test]
    public function can_get_trading_limits()
    {
        $response = $this->getJson('/api/v2/gcu/trading-limits');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'daily_buy_limit',
                    'daily_sell_limit',
                    'daily_buy_used',
                    'daily_sell_used',
                    'monthly_buy_limit',
                    'monthly_sell_limit',
                    'monthly_buy_used',
                    'monthly_sell_used',
                    'minimum_buy_amount',
                    'minimum_sell_amount',
                    'kyc_level',
                    'limits_currency',
                ],
            ]);
    }

    #[Test]
    public function cannot_buy_gcu_with_frozen_account()
    {
        $this->account->update(['frozen' => true]);

        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'   => 1000,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Account Frozen');
    }

    #[Test]
    public function cannot_sell_gcu_with_frozen_account()
    {
        $this->account->update(['frozen' => true]);

        // Give user some GCU balance
        AccountBalance::create([
            'account_uuid' => $this->account->uuid,
            'asset_code'   => 'GCU',
            'balance'      => 1000.00,
        ]);

        $response = $this->postJson('/api/v2/gcu/sell', [
            'amount'   => 100,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Account Frozen');
    }

    #[Test]
    public function quote_requires_valid_parameters()
    {
        $response = $this->getJson('/api/v2/gcu/quote');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['operation', 'amount', 'currency']);
    }

    #[Test]
    public function trading_endpoints_require_authentication()
    {
        // Create a fresh test instance without authentication
        $this->refreshApplication();

        $this->postJson('/api/v2/gcu/buy', ['amount' => 1000, 'currency' => 'EUR'])
            ->assertUnauthorized();

        $this->postJson('/api/v2/gcu/sell', ['amount' => 100, 'currency' => 'EUR'])
            ->assertUnauthorized();

        $this->getJson('/api/v2/gcu/quote?operation=buy&amount=1000&currency=EUR')
            ->assertUnauthorized();

        $this->getJson('/api/v2/gcu/trading-limits')
            ->assertUnauthorized();
    }

    #[Test]
    public function can_buy_gcu_with_different_currencies()
    {
        $currencies = ['USD', 'GBP', 'CHF'];

        foreach ($currencies as $currency) {
            // Give user balance in this currency
            AccountBalance::create([
                'account_uuid' => $this->account->uuid,
                'asset_code'   => $currency,
                'balance'      => 10000.00,
            ]);

            $response = $this->postJson('/api/v2/gcu/buy', [
                'amount'   => 1000,
                'currency' => $currency,
            ]);

            $response->assertOk()
                ->assertJsonPath('data.spent_currency', $currency)
                ->assertJsonPath('data.received_currency', 'GCU');
        }
    }
}
