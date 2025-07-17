<?php

namespace Tests\Unit\Http\Resources;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WalletAddressResourceTest extends TestCase
{
    #[Test]
    public function test_transforms_wallet_address_to_array(): void
    {
        $walletAddress = (object) [
            'id'              => 1,
            'chain'           => 'ethereum',
            'address'         => '0x742d35Cc6634C0532925a3b844Bc9e7595f8f9e0',
            'label'           => 'Main Treasury',
            'derivation_path' => "m/44'/60'/0'/0/0",
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];

        $resource = new WalletAddressResource($walletAddress);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertEquals([
            'id'              => 1,
            'chain'           => 'ethereum',
            'address'         => '0x742d35Cc6634C0532925a3b844Bc9e7595f8f9e0',
            'label'           => 'Main Treasury',
            'derivation_path' => "m/44'/60'/0'/0/0",
            'is_active'       => true,
            'created_at'      => $walletAddress->created_at,
            'updated_at'      => $walletAddress->updated_at,
        ], $array);
    }

    #[Test]
    public function test_handles_null_optional_fields(): void
    {
        $walletAddress = (object) [
            'id'              => 2,
            'chain'           => 'ethereum',
            'address'         => '0x123456789abcdef',
            'label'           => null,
            'derivation_path' => null,
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];

        $resource = new WalletAddressResource($walletAddress);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertNull($array['label']);
        $this->assertNull($array['derivation_path']);
    }

    #[Test]
    public function test_handles_inactive_wallet(): void
    {
        $walletAddress = (object) [
            'id'              => 3,
            'chain'           => 'polygon',
            'address'         => '0xabcdef123456',
            'label'           => 'Secondary',
            'derivation_path' => "m/44'/60'/0'/0/1",
            'is_active'       => false,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];

        $resource = new WalletAddressResource($walletAddress);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertFalse($array['is_active']);
    }

    #[Test]
    public function test_includes_all_required_fields(): void
    {
        $walletAddress = (object) [
            'id'              => 4,
            'chain'           => 'bsc',
            'address'         => '0xdef456789abc',
            'label'           => 'BSC Wallet',
            'derivation_path' => "m/44'/60'/0'/0/2",
            'is_active'       => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];

        $resource = new WalletAddressResource($walletAddress);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $expectedKeys = [
            'id',
            'chain',
            'address',
            'label',
            'derivation_path',
            'is_active',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    #[Test]
    public function test_resource_collection(): void
    {
        $walletAddresses = [
            (object) [
                'id'              => 5,
                'chain'           => 'ethereum',
                'address'         => '0xaaa111222333',
                'label'           => 'ETH 1',
                'derivation_path' => "m/44'/60'/0'/0/5",
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            (object) [
                'id'              => 6,
                'chain'           => 'polygon',
                'address'         => '0xbbb444555666',
                'label'           => 'POLY 1',
                'derivation_path' => "m/44'/60'/0'/0/6",
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            (object) [
                'id'              => 7,
                'chain'           => 'bsc',
                'address'         => '0xccc777888999',
                'label'           => 'BSC 1',
                'derivation_path' => "m/44'/60'/0'/0/7",
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        $collection = WalletAddressResource::collection($walletAddresses);
        $request = Request::create('/');
        $array = $collection->toArray($request);

        $this->assertCount(3, $array);

        foreach ($array as $index => $item) {
            $this->assertEquals($walletAddresses[$index]->id, $item['id']);
            $this->assertEquals($walletAddresses[$index]->address, $item['address']);
            $this->assertEquals($walletAddresses[$index]->chain, $item['chain']);
        }
    }
}
