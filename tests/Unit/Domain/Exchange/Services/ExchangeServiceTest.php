<?php

namespace Tests\Unit\Domain\Exchange\Services;

use App\Domain\Exchange\Aggregates\Order;
use App\Domain\Exchange\Aggregates\OrderBook;
use App\Domain\Exchange\Projections\Order as OrderProjection;
use App\Domain\Exchange\Projections\OrderBook as OrderBookProjection;
use App\Domain\Exchange\Services\ExchangeService;
use App\Domain\Exchange\Services\FeeCalculator;
use App\Domain\Exchange\Workflows\OrderMatchingWorkflow;
use App\Models\Account;
use App\Models\Asset;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use Workflow\WorkflowStub;

class ExchangeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExchangeService $service;

    private FeeCalculator $feeCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExchangeService();
        $this->feeCalculator = Mockery::mock(FeeCalculator::class);

        // Inject mocked fee calculator
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('feeCalculator');
        $property->setAccessible(true);
        $property->setValue($this->service, $this->feeCalculator);
    }

    public function test_place_order_validates_account_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Account not found');

        $this->service->placeOrder(
            accountId: 'non-existent-account',
            type: 'buy',
            orderType: 'market',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.1'
        );
    }

    public function test_place_order_validates_currencies_exist(): void
    {
        $account = Account::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid currency pair');

        $this->service->placeOrder(
            accountId: $account->id,
            type: 'buy',
            orderType: 'market',
            baseCurrency: 'INVALID',
            quoteCurrency: 'USDT',
            amount: '0.1'
        );
    }

    public function test_place_order_validates_currencies_are_tradeable(): void
    {
        $account = Account::factory()->create();

        Asset::create([
            'code'         => 'BTC',
            'name'         => 'Bitcoin',
            'type'         => 'crypto',
            'is_active'    => true,
            'is_tradeable' => false,
            'precision'    => 8,
        ]);

        Asset::create([
            'code'         => 'USDT',
            'name'         => 'Tether',
            'type'         => 'crypto',
            'is_active'    => true,
            'is_tradeable' => true,
            'precision'    => 2,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency pair not available for trading');

        $this->service->placeOrder(
            accountId: $account->id,
            type: 'buy',
            orderType: 'market',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.1'
        );
    }

    public function test_place_order_requires_price_for_limit_orders(): void
    {
        $account = Account::factory()->create();
        $this->createTradeableAssets();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price is required for limit orders');

        $this->service->placeOrder(
            accountId: $account->id,
            type: 'buy',
            orderType: 'limit',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.1',
            price: null
        );
    }

    public function test_place_order_requires_stop_price_for_stop_orders(): void
    {
        $account = Account::factory()->create();
        $this->createTradeableAssets();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stop price is required for stop orders');

        $this->service->placeOrder(
            accountId: $account->id,
            type: 'buy',
            orderType: 'stop',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.1',
            stopPrice: null
        );
    }

    public function test_place_order_validates_minimum_amount(): void
    {
        $account = Account::factory()->create();
        $this->createTradeableAssets();

        $this->feeCalculator->shouldReceive('calculateMinimumOrderValue')
            ->with('BTC', 'USDT')
            ->andReturn(BigDecimal::of('0.001'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum order amount is 0.001 BTC');

        $this->service->placeOrder(
            accountId: $account->id,
            type: 'buy',
            orderType: 'market',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.0001'
        );
    }

    public function test_place_order_creates_market_order_successfully(): void
    {
        $account = Account::factory()->create();
        $this->createTradeableAssets();

        $this->feeCalculator->shouldReceive('calculateMinimumOrderValue')
            ->with('BTC', 'USDT')
            ->andReturn(BigDecimal::of('0.0001'));

        // Mock request
        $request = Mockery::mock('alias:' . Request::class);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Test Browser');
        $this->app->instance('request', $request);

        // Mock order aggregate
        $orderAggregate = Mockery::mock(Order::class);
        $orderAggregate->shouldReceive('placeOrder')->once();
        $orderAggregate->shouldReceive('persist')->once();

        Order::shouldReceive('retrieve')
            ->once()
            ->andReturn($orderAggregate);

        // Mock order book aggregate
        $orderBookAggregate = Mockery::mock(OrderBook::class);
        $orderBookAggregate->shouldReceive('createOrderBook')->once();
        $orderBookAggregate->shouldReceive('persist')->once();

        OrderBook::shouldReceive('retrieve')
            ->once()
            ->andReturn($orderBookAggregate);

        // Mock workflow
        WorkflowStub::fake();
        $workflowMock = Mockery::mock(WorkflowStub::class);
        $workflowMock->shouldReceive('start')->once();
        $workflowMock->shouldReceive('id')->andReturn('workflow-123');

        WorkflowStub::shouldReceive('make')
            ->with(OrderMatchingWorkflow::class)
            ->andReturn($workflowMock);

        $result = $this->service->placeOrder(
            accountId: $account->id,
            type: 'buy',
            orderType: 'market',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.1'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertEquals('Order placed successfully', $result['message']);
        $this->assertEquals('workflow-123', $result['workflow_id']);
    }

    public function test_place_order_creates_limit_order_with_price(): void
    {
        $account = Account::factory()->create();
        $this->createTradeableAssets();

        $this->feeCalculator->shouldReceive('calculateMinimumOrderValue')
            ->andReturn(BigDecimal::of('0.0001'));

        // Mock dependencies
        $this->mockOrderCreation();

        $result = $this->service->placeOrder(
            accountId: $account->id,
            type: 'sell',
            orderType: 'limit',
            baseCurrency: 'BTC',
            quoteCurrency: 'USDT',
            amount: '0.5',
            price: '45000'
        );

        $this->assertTrue($result['success']);
    }

    public function test_cancel_order_validates_order_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order not found');

        $this->service->cancelOrder('non-existent-order');
    }

    public function test_cancel_order_validates_order_can_be_cancelled(): void
    {
        OrderProjection::create([
            'order_id'       => 'completed-order',
            'account_id'     => 'account-123',
            'type'           => 'buy',
            'order_type'     => 'market',
            'base_currency'  => 'BTC',
            'quote_currency' => 'USDT',
            'amount'         => '0.1',
            'filled_amount'  => '0.1',
            'status'         => 'completed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order cannot be cancelled. Status: completed');

        $this->service->cancelOrder('completed-order');
    }

    public function test_cancel_order_cancels_successfully(): void
    {
        OrderProjection::create([
            'order_id'       => 'active-order',
            'account_id'     => 'account-123',
            'type'           => 'buy',
            'order_type'     => 'limit',
            'base_currency'  => 'BTC',
            'quote_currency' => 'USDT',
            'amount'         => '0.1',
            'price'          => '40000',
            'filled_amount'  => '0',
            'status'         => 'pending',
        ]);

        // Mock order aggregate
        $orderAggregate = Mockery::mock(Order::class);
        $orderAggregate->shouldReceive('cancelOrder')
            ->once()
            ->with('User requested');
        $orderAggregate->shouldReceive('persist')->once();

        Order::shouldReceive('retrieve')
            ->with('active-order')
            ->andReturn($orderAggregate);

        // Mock order book aggregate
        $orderBookAggregate = Mockery::mock(OrderBook::class);
        $orderBookAggregate->shouldReceive('removeOrder')
            ->once()
            ->with('active-order', 'cancelled');
        $orderBookAggregate->shouldReceive('persist')->once();

        OrderBook::shouldReceive('retrieve')
            ->once()
            ->andReturn($orderBookAggregate);

        $result = $this->service->cancelOrder('active-order');

        $this->assertTrue($result['success']);
        $this->assertEquals('Order cancelled successfully', $result['message']);
    }

    public function test_get_order_book_returns_empty_for_non_existent_pair(): void
    {
        $result = $this->service->getOrderBook('BTC', 'ETH');

        $this->assertEquals('BTC/ETH', $result['pair']);
        $this->assertEmpty($result['bids']);
        $this->assertEmpty($result['asks']);
        $this->assertNull($result['spread']);
        $this->assertNull($result['mid_price']);
    }

    public function test_get_order_book_returns_formatted_data(): void
    {
        OrderBookProjection::create([
            'order_book_id'  => 'btc-usdt-book',
            'base_currency'  => 'BTC',
            'quote_currency' => 'USDT',
            'bids'           => [
                ['price' => '42000', 'amount' => '0.5', 'order_count' => 2],
                ['price' => '41900', 'amount' => '1.0', 'order_count' => 3],
            ],
            'asks' => [
                ['price' => '42100', 'amount' => '0.3', 'order_count' => 1],
                ['price' => '42200', 'amount' => '0.7', 'order_count' => 2],
            ],
            'last_price'    => '42050',
            'volume_24h'    => '125.5',
            'high_24h'      => '43000',
            'low_24h'       => '41000',
            'bid_liquidity' => '1.5',
            'ask_liquidity' => '1.0',
        ]);

        $result = $this->service->getOrderBook('BTC', 'USDT', 10);

        $this->assertEquals('BTC/USDT', $result['pair']);
        $this->assertCount(2, $result['bids']);
        $this->assertCount(2, $result['asks']);
        $this->assertEquals('42000', $result['bids'][0]['price']);
        $this->assertEquals('0.5', $result['bids'][0]['amount']);
        $this->assertEquals('42100', $result['asks'][0]['price']);
        $this->assertEquals('100', $result['spread']); // 42100 - 42000
        $this->assertEquals('42050', $result['mid_price']); // (42000 + 42100) / 2
        $this->assertEquals('42050', $result['last_price']);
        $this->assertEquals('125.5', $result['volume_24h']);
    }

    // Helper methods
    private function createTradeableAssets(): void
    {
        Asset::create([
            'code'         => 'BTC',
            'name'         => 'Bitcoin',
            'type'         => 'crypto',
            'is_active'    => true,
            'is_tradeable' => true,
            'precision'    => 8,
        ]);

        Asset::create([
            'code'         => 'USDT',
            'name'         => 'Tether',
            'type'         => 'crypto',
            'is_active'    => true,
            'is_tradeable' => true,
            'precision'    => 2,
        ]);
    }

    private function mockOrderCreation(): void
    {
        // Mock request
        $request = Mockery::mock('alias:' . Request::class);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Test Browser');
        $this->app->instance('request', $request);

        // Mock order aggregate
        $orderAggregate = Mockery::mock(Order::class);
        $orderAggregate->shouldReceive('placeOrder')->once();
        $orderAggregate->shouldReceive('persist')->once();

        Order::shouldReceive('retrieve')->andReturn($orderAggregate);

        // Mock order book aggregate
        $orderBookAggregate = Mockery::mock(OrderBook::class);
        $orderBookAggregate->shouldReceive('createOrderBook')->once();
        $orderBookAggregate->shouldReceive('persist')->once();

        OrderBook::shouldReceive('retrieve')->andReturn($orderBookAggregate);

        // Mock workflow
        WorkflowStub::fake();
        $workflowMock = Mockery::mock(WorkflowStub::class);
        $workflowMock->shouldReceive('start')->once();
        $workflowMock->shouldReceive('id')->andReturn('workflow-123');

        WorkflowStub::shouldReceive('make')
            ->with(OrderMatchingWorkflow::class)
            ->andReturn($workflowMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
