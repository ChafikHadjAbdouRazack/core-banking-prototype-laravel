<?php

namespace Tests\Unit\Http\Resources;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionResourceTest extends TestCase
{
    #[Test]
    public function test_transforms_transaction_to_array(): void
    {
        $transaction = (object) [
            'id'               => 1,
            'chain'            => 'ethereum',
            'transaction_hash' => '0x1234567890abcdef',
            'from_address'     => '0xfrom123',
            'to_address'       => '0xto456',
            'amount'           => '1000000000000000000', // 1 ETH in wei
            'asset'            => 'ETH',
            'gas_used'         => '21000',
            'gas_price'        => '20000000000', // 20 gwei
            'status'           => 'confirmed',
            'confirmations'    => 12,
            'block_number'     => 1234567,
            'metadata'         => json_encode(['note' => 'Test transaction']),
            'confirmed_at'     => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $resource = new TransactionResource($transaction);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertEquals([
            'id'               => $transaction->id,
            'chain'            => 'ethereum',
            'transaction_hash' => '0x1234567890abcdef',
            'from_address'     => '0xfrom123',
            'to_address'       => '0xto456',
            'amount'           => '1000000000000000000',
            'asset'            => 'ETH',
            'gas_used'         => '21000',
            'gas_price'        => '20000000000',
            'status'           => 'confirmed',
            'confirmations'    => 12,
            'block_number'     => 1234567,
            'metadata'         => ['note' => 'Test transaction'],
            'confirmed_at'     => $transaction->confirmed_at,
            'created_at'       => $transaction->created_at,
            'updated_at'       => $transaction->updated_at,
        ], $array);
    }

    #[Test]
    public function test_handles_null_metadata(): void
    {
        $transaction = (object) [
            'id'               => 2,
            'chain'            => 'polygon',
            'transaction_hash' => '0xabcdef',
            'from_address'     => '0xaddr1',
            'to_address'       => '0xaddr2',
            'amount'           => '1000',
            'asset'            => 'MATIC',
            'gas_used'         => '21000',
            'gas_price'        => '20000000000',
            'status'           => 'confirmed',
            'confirmations'    => 10,
            'block_number'     => 9999,
            'metadata'         => null,
            'confirmed_at'     => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $resource = new TransactionResource($transaction);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertIsArray($array['metadata']);
        $this->assertEmpty($array['metadata']);
    }

    #[Test]
    public function test_handles_invalid_json_metadata(): void
    {
        $transaction = (object) [
            'id'               => 3,
            'chain'            => 'bsc',
            'transaction_hash' => '0x123abc',
            'from_address'     => '0xaddr3',
            'to_address'       => '0xaddr4',
            'amount'           => '5000',
            'asset'            => 'BNB',
            'gas_used'         => '21000',
            'gas_price'        => '5000000000',
            'status'           => 'confirmed',
            'confirmations'    => 20,
            'block_number'     => 888888,
            'metadata'         => 'invalid json',
            'confirmed_at'     => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $resource = new TransactionResource($transaction);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertIsArray($array['metadata']);
        $this->assertEmpty($array['metadata']);
    }

    #[Test]
    public function test_includes_all_required_fields(): void
    {
        $transaction = (object) [
            'id'               => 4,
            'chain'            => 'ethereum',
            'transaction_hash' => '0xfield123',
            'from_address'     => '0xfrom',
            'to_address'       => '0xto',
            'amount'           => '100000',
            'asset'            => 'USDT',
            'gas_used'         => '50000',
            'gas_price'        => '30000000000',
            'status'           => 'pending',
            'confirmations'    => 0,
            'block_number'     => 123456,
            'metadata'         => json_encode(['test' => true]),
            'confirmed_at'     => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $resource = new TransactionResource($transaction);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $expectedKeys = [
            'id',
            'chain',
            'transaction_hash',
            'from_address',
            'to_address',
            'amount',
            'asset',
            'gas_used',
            'gas_price',
            'status',
            'confirmations',
            'block_number',
            'metadata',
            'confirmed_at',
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
        $transactions = [
            (object) [
                'id'               => 5,
                'chain'            => 'ethereum',
                'transaction_hash' => '0xcoll1',
                'from_address'     => '0xf1',
                'to_address'       => '0xt1',
                'amount'           => '1000',
                'asset'            => 'ETH',
                'gas_used'         => '21000',
                'gas_price'        => '20000000000',
                'status'           => 'confirmed',
                'confirmations'    => 12,
                'block_number'     => 111,
                'metadata'         => null,
                'confirmed_at'     => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            (object) [
                'id'               => 6,
                'chain'            => 'polygon',
                'transaction_hash' => '0xcoll2',
                'from_address'     => '0xf2',
                'to_address'       => '0xt2',
                'amount'           => '2000',
                'asset'            => 'MATIC',
                'gas_used'         => '21000',
                'gas_price'        => '30000000000',
                'status'           => 'pending',
                'confirmations'    => 0,
                'block_number'     => null,
                'metadata'         => null,
                'confirmed_at'     => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            (object) [
                'id'               => 7,
                'chain'            => 'bsc',
                'transaction_hash' => '0xcoll3',
                'from_address'     => '0xf3',
                'to_address'       => '0xt3',
                'amount'           => '3000',
                'asset'            => 'BNB',
                'gas_used'         => '25000',
                'gas_price'        => '5000000000',
                'status'           => 'failed',
                'confirmations'    => 0,
                'block_number'     => 333,
                'metadata'         => json_encode(['error' => 'Out of gas']),
                'confirmed_at'     => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ];

        $collection = TransactionResource::collection($transactions);
        $request = Request::create('/');
        $array = $collection->toArray($request);

        $this->assertCount(3, $array);

        foreach ($array as $index => $item) {
            $this->assertEquals($transactions[$index]->id, $item['id']);
            $this->assertEquals($transactions[$index]->transaction_hash, $item['transaction_hash']);
        }
    }
}
