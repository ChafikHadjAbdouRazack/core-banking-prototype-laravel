<?php

namespace Tests\Feature\Http\Controllers\Api\V2;

use App\Models\Account;
use App\Models\Asset;
use App\Models\BasketAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class GCUTradingControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected BasketAsset $gcu;

    protected Asset $eurAsset;

    protected Asset $gcuAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_uuid' => $this->user->uuid,
        ]);

        $this->setupAssets();
        $this->setupAccountBalances();
    }

    protected function setupAssets(): void
    {
        // Create EUR asset
        $this->eurAsset = Asset::firstOrCreate(
            ['code' => 'EUR'],
            ['name' => 'Euro', 'type' => 'fiat', 'precision' => 2, 'is_active' => true]
        );

        // Create GCU as both a basket and an asset
        $this->gcuAsset = Asset::firstOrCreate(
            ['code' => 'GCU'],
            ['name' => 'Global Currency Unit', 'type' => 'basket', 'precision' => 4, 'is_active' => true]
        );

        $this->gcu = BasketAsset::firstOrCreate(
            ['code' => 'GCU'],
            [
                'name'        => 'Global Currency Unit',
                'description' => 'A basket of global currencies',
                'type'        => 'weighted',
                'is_active'   => true,
            ]
        );

        // Create a current value for GCU
        BasketValue::create([
            'basket_code'        => 'GCU',
            'value'              => 1.0975,
            'reference_currency' => 'USD',
            'calculated_at'      => now(),
        ]);
    }

    protected function setupAccountBalances(): void
    {
        // Give user EUR balance
        AccountBalance::create([
            'account_uuid'      => $this->account->uuid,
            'asset_code'        => 'EUR',
            'balance'           => 100000000, // 1,000 EUR
            'available_balance' => 100000000,
            'reserved_balance'  => 0,
        ]);

        // Initialize GCU balance
        AccountBalance::create([
            'account_uuid'      => $this->account->uuid,
            'asset_code'        => 'GCU',
            'balance'           => 0,
            'available_balance' => 0,
            'reserved_balance'  => 0,
        ]);
    }

    #[Test]
    public function test_buy_gcu_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'       => 100.00,
            'currency'     => 'EUR',
            'account_uuid' => $this->account->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'transaction_id',
                    'gcu_amount',
                    'spent_amount',
                    'spent_currency',
                    'exchange_rate',
                    'fee',
                    'timestamp',
                ],
            ])
            ->assertJsonPath('data.spent_currency', 'EUR');
    }

    #[Test]
    public function test_buy_gcu_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'   => 100.00,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function test_buy_gcu_validates_input(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/buy', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'currency']);
    }

    #[Test]
    public function test_buy_gcu_validates_minimum_amount(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'   => 50.00, // Below minimum of 100
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function test_buy_gcu_fails_with_insufficient_balance(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/buy', [
            'amount'       => 2000.00, // More than available balance
            'currency'     => 'EUR',
            'account_uuid' => $this->account->uuid,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Insufficient balance',
            ]);
    }

    #[Test]
    public function test_sell_gcu_successfully(): void
    {
        Sanctum::actingAs($this->user);

        // Give user some GCU to sell
        AccountBalance::where('account_uuid', $this->account->uuid)
            ->where('asset_code', 'GCU')
            ->update([
                'balance'           => 1000000, // 100 GCU
                'available_balance' => 1000000,
            ]);

        $response = $this->postJson('/api/v2/gcu/sell', [
            'amount'       => 50,
            'currency'     => 'EUR',
            'account_uuid' => $this->account->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'transaction_id',
                    'gcu_amount',
                    'received_amount',
                    'received_currency',
                    'exchange_rate',
                    'fee',
                    'timestamp',
                ],
            ])
            ->assertJsonPath('data.gcu_amount', 50)
            ->assertJsonPath('data.received_currency', 'EUR');
    }

    #[Test]
    public function test_sell_gcu_requires_authentication(): void
    {
        $response = $this->postJson('/api/v2/gcu/sell', [
            'amount'   => 50,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function test_sell_gcu_validates_input(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/sell', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'currency']);
    }

    #[Test]
    public function test_sell_gcu_fails_with_insufficient_gcu_balance(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v2/gcu/sell', [
            'amount'       => 100, // User has 0 GCU
            'currency'     => 'EUR',
            'account_uuid' => $this->account->uuid,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Insufficient GCU balance',
            ]);
    }

    #[Test]
    public function test_get_gcu_quote_returns_pricing(): void
    {
        $response = $this->getJson('/api/v2/gcu/quote?amount=100&currency=EUR&type=buy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'source_amount',
                    'source_currency',
                    'gcu_amount',
                    'exchange_rate',
                    'fee',
                    'fee_percentage',
                    'total_cost',
                    'quote_valid_until',
                ],
            ])
            ->assertJsonPath('data.type', 'buy')
            ->assertJsonPath('data.source_currency', 'EUR');
    }

    #[Test]
    public function test_get_gcu_quote_validates_parameters(): void
    {
        $response = $this->getJson('/api/v2/gcu/quote?amount=invalid&currency=EUR&type=buy');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function test_get_trading_limits_returns_user_limits(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/gcu/limits');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'daily_limits' => [
                        'buy'  => ['used', 'limit', 'remaining'],
                        'sell' => ['used', 'limit', 'remaining'],
                    ],
                    'transaction_limits' => [
                        'min_buy_amount',
                        'max_buy_amount',
                        'min_sell_amount',
                        'max_sell_amount',
                    ],
                    'kyc_level',
                    'enhanced_limits_available',
                ],
            ]);
    }

    #[Test]
    public function test_get_trading_limits_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/gcu/limits');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_get_trading_history_returns_transactions(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/gcu/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'transaction_id',
                        'type',
                        'gcu_amount',
                        'fiat_amount',
                        'fiat_currency',
                        'exchange_rate',
                        'fee',
                        'status',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function test_get_trading_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/gcu/history');

        $response->assertStatus(401);
    }
}
