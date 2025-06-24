<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\ExchangeRate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExchangeRateControllerTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    public function test_can_list_exchange_rates()
    {
        Sanctum::actingAs($this->user);

        // Create test exchange rates
        ExchangeRate::factory()->create([
            'from_asset' => 'USD',
            'to_asset' => 'EUR',
            'rate' => '0.85',
            'is_active' => true,
        ]);

        ExchangeRate::factory()->create([
            'from_asset' => 'EUR',
            'to_asset' => 'USD',
            'rate' => '1.18',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/exchange-rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'from_asset',
                        'to_asset',
                        'rate',
                        'provider',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
        
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_filter_exchange_rates_by_from_asset()
    {
        Sanctum::actingAs($this->user);

        ExchangeRate::factory()->create(['from_asset' => 'USD', 'to_asset' => 'EUR']);
        ExchangeRate::factory()->create(['from_asset' => 'USD', 'to_asset' => 'GBP']);
        ExchangeRate::factory()->create(['from_asset' => 'EUR', 'to_asset' => 'USD']);

        $response = $this->getJson('/api/exchange-rates?from_asset=USD');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $rate) {
            $this->assertEquals('USD', $rate['from_asset']);
        }
    }

    public function test_can_filter_exchange_rates_by_to_asset()
    {
        Sanctum::actingAs($this->user);

        ExchangeRate::factory()->create(['from_asset' => 'USD', 'to_asset' => 'EUR']);
        ExchangeRate::factory()->create(['from_asset' => 'GBP', 'to_asset' => 'EUR']);
        ExchangeRate::factory()->create(['from_asset' => 'EUR', 'to_asset' => 'USD']);

        $response = $this->getJson('/api/exchange-rates?to_asset=EUR');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $rate) {
            $this->assertEquals('EUR', $rate['to_asset']);
        }
    }

    public function test_can_filter_exchange_rates_by_active_status()
    {
        Sanctum::actingAs($this->user);

        ExchangeRate::factory()->create(['is_active' => true]);
        ExchangeRate::factory()->create(['is_active' => true]);
        ExchangeRate::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/exchange-rates?active=true');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $rate) {
            $this->assertTrue($rate['is_active']);
        }
    }

    public function test_can_show_specific_exchange_rate()
    {
        Sanctum::actingAs($this->user);

        $rate = ExchangeRate::factory()->create([
            'from_asset' => 'USD',
            'to_asset' => 'EUR',
            'rate' => '0.85',
            'provider' => 'manual',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/exchange-rates/{$rate->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'from_asset',
                    'to_asset',
                    'rate',
                    'provider',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'from_asset' => 'USD',
                    'to_asset' => 'EUR',
                    'rate' => '0.85',
                    'provider' => 'manual',
                    'is_active' => true,
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_exchange_rate()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/exchange-rates/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_create_exchange_rate_without_implementation()
    {
        Sanctum::actingAs($this->user);

        $rateData = [
            'from_asset' => 'USD',
            'to_asset' => 'GBP',
            'rate' => '0.75',
            'provider' => 'manual',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/exchange-rates', $rateData);

        // This controller may not have create method implemented
        $this->assertContains($response->status(), [404, 405, 422]);
    }

    public function test_can_update_exchange_rate()
    {
        Sanctum::actingAs($this->user);

        $rate = ExchangeRate::factory()->create([
            'from_asset' => 'USD',
            'to_asset' => 'EUR',
            'rate' => '0.85',
            'is_active' => true,
        ]);

        $updateData = [
            'rate' => '0.88',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/exchange-rates/{$rate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'rate' => '0.88',
                    'is_active' => false,
                ],
                'message' => 'Exchange rate updated successfully',
            ]);

        $this->assertDatabaseHas('exchange_rates', [
            'id' => $rate->id,
            'rate' => '0.88',
            'is_active' => false,
        ]);
    }

    public function test_can_get_current_rate_for_pair()
    {
        Sanctum::actingAs($this->user);

        ExchangeRate::factory()->create([
            'from_asset' => 'USD',
            'to_asset' => 'EUR',
            'rate' => '0.85',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/exchange-rates/current/USD/EUR');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'from_asset',
                    'to_asset',
                    'rate',
                    'last_updated',
                ]
            ])
            ->assertJson([
                'data' => [
                    'from_asset' => 'USD',
                    'to_asset' => 'EUR',
                    'rate' => '0.85',
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_currency_pair()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/exchange-rates/current/USD/INVALID');

        $response->assertStatus(404);
    }

    public function test_requires_authentication_for_all_endpoints()
    {
        $rate = ExchangeRate::factory()->create();

        // Test all endpoints require authentication
        $this->getJson('/api/exchange-rates')->assertStatus(401);
        $this->getJson("/api/exchange-rates/{$rate->id}")->assertStatus(401);
        $this->postJson('/api/exchange-rates', [])->assertStatus(401);
        $this->putJson("/api/exchange-rates/{$rate->id}", [])->assertStatus(401);
        $this->getJson('/api/exchange-rates/current/USD/EUR')->assertStatus(401);
    }
}