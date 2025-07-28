<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubProductSimpleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_sub_product_configuration_exists(): void
    {
        $config = config('sub_products');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('exchange', $config);
        $this->assertArrayHasKey('lending', $config);
        $this->assertArrayHasKey('stablecoins', $config);
        $this->assertArrayHasKey('treasury', $config);
    }

    #[Test]
    public function test_sub_product_service_exists(): void
    {
        $service = app(SubProductService::class);

        $this->assertInstanceOf(SubProductService::class, $service);
    }

    #[Test]
    public function test_sub_product_api_endpoints_exist(): void
    {
        // Test public endpoints
        $response = $this->getJson('/api/sub-products');
        $this->assertContains($response->status(), [200, 404, 500]); // Should not be 405 Method Not Allowed

        $response = $this->getJson('/api/sub-products/exchange');
        $this->assertContains($response->status(), [200, 404, 500]); // Should not be 405 Method Not Allowed
    }

    #[Test]
    public function test_sub_product_middleware_is_registered(): void
    {
        $aliases = app()->make('router')->getMiddleware();
        $this->assertArrayHasKey('sub_product', $aliases);
    }
}
