<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class ExchangeRateProviderControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function test_get_providers_list(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'display_name',
                        'available',
                        'priority',
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_get_rate_from_provider(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/ecb/rate?from=EUR&to=USD');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'from',
                    'to',
                    'rate',
                ],
            ]);
    }

    #[Test]
    public function test_get_rate_validates_currencies(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/ecb/rate?from=INVALID&to=USD');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    }

    #[Test]
    public function test_get_rate_validates_provider(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/invalid/rate?from=EUR&to=USD');

        $response->assertStatus(404);
    }

    #[Test]
    public function test_compare_rates_across_providers(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/compare?from=EUR&to=USD');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    #[Test]
    public function test_compare_rates_validates_currencies(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/compare?from=EUR&to=INVALID');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    #[Test]
    public function test_get_aggregated_rate(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/aggregated?from=EUR&to=USD');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    #[Test]
    public function test_get_aggregated_rate_validates_currencies(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/aggregated?from=INVALID&to=INVALID');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);
    }

    #[Test]
    public function test_refresh_rates_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/exchange-providers/refresh');

        $response->assertStatus(401);
    }

    #[Test]
    public function test_refresh_rates_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/exchange-providers/refresh', [
            'providers' => ['ecb', 'fixer'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    #[Test]
    public function test_get_historical_rates(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/historical?from=EUR&to=USD&start_date=2025-01-01&end_date=2025-01-07');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    #[Test]
    public function test_get_historical_rates_validates_dates(): void
    {
        $response = $this->getJson('/api/v1/exchange-providers/historical?from=EUR&to=USD&start_date=invalid&end_date=2025-01-07');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    #[Test]
    public function test_validate_rate(): void
    {
        $response = $this->postJson('/api/v1/exchange-providers/validate', [
            'from' => 'EUR',
            'to' => 'USD',
            'rate' => 1.08,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    #[Test]
    public function test_validate_rate_validates_input(): void
    {
        $response = $this->postJson('/api/v1/exchange-providers/validate', [
            'from' => 'EUR',
            'to' => 'USD',
            'rate' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rate']);
    }
}
