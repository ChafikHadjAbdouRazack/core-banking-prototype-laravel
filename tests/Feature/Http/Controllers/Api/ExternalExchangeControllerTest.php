<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class ExternalExchangeControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function test_get_connectors_returns_list(): void
    {
        $response = $this->getJson('/api/external-exchange/connectors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'connectors' => [
                    '*' => [
                        'name',
                        'display_name',
                        'available',
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_get_ticker_returns_price_data(): void
    {
        $response = $this->getJson('/api/external-exchange/ticker/BTC/EUR');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ticker',
            ]);
    }

    #[Test]
    public function test_get_ticker_returns_error_for_invalid_pair(): void
    {
        $response = $this->getJson('/api/external-exchange/ticker/INVALID/EUR');

        $response->assertStatus(400);
    }

    #[Test]
    public function test_get_order_book_returns_depth_data(): void
    {
        $response = $this->getJson('/api/external-exchange/orderbook/BTC/EUR');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'orderbook',
            ]);
    }

    #[Test]
    public function test_get_order_book_returns_error_for_invalid_pair(): void
    {
        $response = $this->getJson('/api/external-exchange/orderbook/BTC/INVALID');

        $response->assertStatus(400);
    }

    #[Test]
    public function test_get_arbitrage_opportunities_returns_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/external-exchange/arbitrage/BTC/EUR');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'opportunities',
            ]);
    }

    #[Test]
    public function test_get_arbitrage_opportunities_requires_authentication(): void
    {
        $response = $this->getJson('/api/external-exchange/arbitrage/BTC/EUR');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_get_arbitrage_opportunities_returns_error_for_invalid_pair(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/external-exchange/arbitrage/INVALID/INVALID');

        $response->assertStatus(400);
    }
}
