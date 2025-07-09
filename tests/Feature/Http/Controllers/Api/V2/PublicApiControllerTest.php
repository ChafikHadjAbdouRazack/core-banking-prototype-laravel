<?php

namespace Tests\Feature\Http\Controllers\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_api_info_returns_information(): void
    {
        $response = $this->getJson('/api/v2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'version',
                'description',
                'status',
                'endpoints' => [
                    'accounts',
                    'assets',
                    'exchange_rates',
                    'baskets',
                    'webhooks',
                ],
                'documentation',
                'support',
            ])
            ->assertJson([
                'name' => 'FinAegis Public API',
                'version' => '2.0.0',
                'status' => 'operational',
            ]);
    }

    public function test_get_health_status_returns_system_status(): void
    {
        $response = $this->getJson('/api/v2/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'cache',
                    'queue',
                ],
                'version',
                'environment',
            ])
            ->assertJson([
                'status' => 'healthy',
            ]);
    }

    public function test_get_rate_limits_returns_limit_information(): void
    {
        $response = $this->getJson('/api/v2/rate-limits');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'limits' => [
                    'public' => [
                        'requests_per_minute',
                        'requests_per_hour',
                        'burst_size',
                    ],
                    'authenticated' => [
                        'requests_per_minute',
                        'requests_per_hour',
                        'burst_size',
                    ],
                    'transaction' => [
                        'requests_per_minute',
                        'requests_per_hour',
                        'burst_size',
                    ],
                ],
                'headers' => [
                    'rate_limit',
                    'rate_limit_remaining',
                    'rate_limit_reset',
                ],
                'documentation',
            ])
            ->assertJsonPath('limits.public.requests_per_minute', 60)
            ->assertJsonPath('limits.authenticated.requests_per_minute', 300);
    }

    public function test_get_supported_currencies_returns_list(): void
    {
        $response = $this->getJson('/api/v2/currencies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'currencies' => [
                    '*' => [
                        'code',
                        'name',
                        'symbol',
                        'type',
                        'decimal_places',
                        'supported_operations',
                    ],
                ],
                'total',
                'categories' => [
                    'fiat',
                    'crypto',
                    'stablecoin',
                ],
            ]);
    }

    public function test_get_supported_countries_returns_list(): void
    {
        $response = $this->getJson('/api/v2/countries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'countries' => [
                    '*' => [
                        'code',
                        'name',
                        'region',
                        'supported_services',
                        'regulatory_status',
                    ],
                ],
                'total',
                'regions' => [
                    'europe',
                    'north_america',
                    'asia_pacific',
                    'latin_america',
                    'africa',
                ],
            ]);
    }

    public function test_get_api_changelog_returns_version_history(): void
    {
        $response = $this->getJson('/api/v2/changelog');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_version',
                'changes' => [
                    '*' => [
                        'version',
                        'date',
                        'type',
                        'changes',
                        'breaking_changes',
                        'deprecations',
                    ],
                ],
                'deprecation_policy',
                'migration_guides',
            ])
            ->assertJsonPath('current_version', '2.0.0');
    }

    public function test_get_error_codes_returns_error_reference(): void
    {
        $response = $this->getJson('/api/v2/errors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'error_codes' => [
                    '*' => [
                        'code',
                        'message',
                        'description',
                        'http_status',
                        'resolution',
                    ],
                ],
                'categories' => [
                    'authentication',
                    'validation',
                    'business_logic',
                    'rate_limiting',
                    'system',
                ],
            ]);
    }

    public function test_get_webhooks_info_returns_webhook_details(): void
    {
        $response = $this->getJson('/api/v2/webhooks/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'supported_events' => [
                    '*' => [
                        'event',
                        'description',
                        'payload_schema',
                        'retry_policy',
                    ],
                ],
                'security' => [
                    'signature_header',
                    'signature_algorithm',
                    'ip_whitelist_available',
                ],
                'limits' => [
                    'max_endpoints_per_account',
                    'max_retries',
                    'timeout_seconds',
                ],
                'documentation',
            ])
            ->assertJsonPath('security.signature_header', 'X-FinAegis-Signature')
            ->assertJsonPath('security.signature_algorithm', 'HMAC-SHA256');
    }

    public function test_options_request_returns_cors_headers(): void
    {
        $response = $this->options('/api/v2');

        $response->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->assertHeader('Access-Control-Allow-Headers')
            ->assertHeader('Access-Control-Max-Age');
    }
}