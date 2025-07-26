<?php

namespace Tests\Unit\Domain\Exchange\Services;

use App\Domain\Exchange\Aggregates\LiquidityPool;
use App\Domain\Exchange\Projections\LiquidityPool as PoolProjection;
use App\Domain\Exchange\Projections\LiquidityProvider;
use App\Domain\Exchange\Services\ExchangeService;
use App\Domain\Exchange\Services\LiquidityPoolService;
use App\Domain\Exchange\ValueObjects\LiquidityAdditionInput;
use App\Domain\Exchange\ValueObjects\LiquidityRemovalInput;
use App\Domain\Exchange\Workflows\LiquidityManagementWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;
use Workflow\WorkflowStub;

class LiquidityPoolServiceTest extends ServiceTestCase
{
    use RefreshDatabase;

    private LiquidityPoolService $service;

    private ExchangeService $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exchangeService = Mockery::mock(ExchangeService::class);
        $this->service = new LiquidityPoolService($this->exchangeService);
    }

    #[Test]
    public function test_create_pool_creates_new_liquidity_pool(): void
    {
        $baseCurrency = 'ETH';
        $quoteCurrency = 'USDT';
        $feeRate = '0.003';
        $metadata = ['description' => 'ETH/USDT Pool'];

        $poolId = $this->service->createPool($baseCurrency, $quoteCurrency, $feeRate, $metadata);

        $this->assertIsString($poolId);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $poolId);
    }

    #[Test]
    public function test_create_pool_throws_exception_if_pool_exists(): void
    {
        // Create initial pool
        PoolProjection::create([
            'pool_id'        => 'existing-pool-id',
            'base_currency'  => 'BTC',
            'quote_currency' => 'USDT',
            'base_reserve'   => '0',
            'quote_reserve'  => '0',
            'total_shares'   => '0',
            'fee_rate'       => '0.003',
            'is_active'      => true,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Liquidity pool already exists for this pair');

        $this->service->createPool('BTC', 'USDT');
    }

    #[Test]
    public function test_add_liquidity_delegates_to_workflow(): void
    {
        $input = new LiquidityAdditionInput(
            poolId: 'pool-123',
            providerId: 'provider-456',
            baseAmount: '1000000',
            quoteAmount: '2000000'
        );

        $expectedResult = [
            'shares_minted'   => '1414213',
            'base_deposited'  => '1000000',
            'quote_deposited' => '2000000',
        ];

        WorkflowStub::fake();
        WorkflowStub::mock(LiquidityManagementWorkflow::class, $expectedResult);

        $result = $this->service->addLiquidity($input);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function test_remove_liquidity_delegates_to_workflow(): void
    {
        $input = new LiquidityRemovalInput(
            poolId: 'pool-123',
            providerId: 'provider-456',
            shares: '500000'
        );

        $expectedResult = [
            'shares_burned'   => '500000',
            'base_withdrawn'  => '250000',
            'quote_withdrawn' => '500000',
        ];

        WorkflowStub::fake();
        WorkflowStub::mock(LiquidityManagementWorkflow::class, $expectedResult);

        $result = $this->service->removeLiquidity($input);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function test_swap_executes_pool_swap(): void
    {
        $poolId = 'pool-123';
        $accountId = 'account-456';
        $inputCurrency = 'ETH';
        $inputAmount = '1000000';
        $minOutputAmount = '1900000';

        $swapDetails = [
            'outputCurrency' => 'USDT',
            'outputAmount'   => '2000000',
            'feeAmount'      => '3000',
            'priceImpact'    => '0.15',
        ];

        // Mock the aggregate to return swap details
        $poolAggregate = Mockery::mock(LiquidityPool::class);
        $poolAggregate->shouldReceive('executeSwap')
            ->with($inputCurrency, $inputAmount, $minOutputAmount)
            ->andReturn($swapDetails);

        // Mock the static retrieve method
        LiquidityPool::shouldReceive('retrieve')
            ->with($poolId)
            ->andReturn($poolAggregate);

        // Expect exchange service to be called
        $this->exchangeService->shouldReceive('executePoolSwap')
            ->once()
            ->with(
                $poolId,
                $accountId,
                $inputCurrency,
                $inputAmount,
                'USDT',
                '2000000',
                '3000'
            );

        $result = $this->service->swap($poolId, $accountId, $inputCurrency, $inputAmount, $minOutputAmount);

        $this->assertEquals($swapDetails, $result);
    }

    #[Test]
    public function test_get_pool_returns_pool_projection(): void
    {
        $pool = PoolProjection::create([
            'pool_id'        => 'test-pool-id',
            'base_currency'  => 'ETH',
            'quote_currency' => 'USDT',
            'base_reserve'   => '1000000',
            'quote_reserve'  => '2000000',
            'total_shares'   => '1414213',
            'fee_rate'       => '0.003',
            'is_active'      => true,
        ]);

        $result = $this->service->getPool('test-pool-id');

        $this->assertInstanceOf(PoolProjection::class, $result);
        $this->assertEquals('test-pool-id', $result->pool_id);
        $this->assertEquals('ETH', $result->base_currency);
        $this->assertEquals('USDT', $result->quote_currency);
    }

    #[Test]
    public function test_get_pool_returns_null_for_non_existent_pool(): void
    {
        $result = $this->service->getPool('non-existent-id');

        $this->assertNull($result);
    }

    #[Test]
    public function test_get_pool_by_pair_returns_matching_pool(): void
    {
        PoolProjection::create([
            'pool_id'        => 'btc-usdt-pool',
            'base_currency'  => 'BTC',
            'quote_currency' => 'USDT',
            'base_reserve'   => '100',
            'quote_reserve'  => '4000000',
            'total_shares'   => '20000',
            'fee_rate'       => '0.003',
            'is_active'      => true,
        ]);

        $result = $this->service->getPoolByPair('BTC', 'USDT');

        $this->assertInstanceOf(PoolProjection::class, $result);
        $this->assertEquals('BTC', $result->base_currency);
        $this->assertEquals('USDT', $result->quote_currency);
    }

    #[Test]
    public function test_get_active_pools_returns_only_active_pools(): void
    {
        // Create active pools
        PoolProjection::create([
            'pool_id'        => 'active-pool-1',
            'base_currency'  => 'ETH',
            'quote_currency' => 'USDT',
            'base_reserve'   => '1000',
            'quote_reserve'  => '2000',
            'total_shares'   => '1414',
            'fee_rate'       => '0.003',
            'is_active'      => true,
        ]);

        PoolProjection::create([
            'pool_id'        => 'active-pool-2',
            'base_currency'  => 'BTC',
            'quote_currency' => 'USDT',
            'base_reserve'   => '10',
            'quote_reserve'  => '400000',
            'total_shares'   => '2000',
            'fee_rate'       => '0.003',
            'is_active'      => true,
        ]);

        // Create inactive pool
        PoolProjection::create([
            'pool_id'        => 'inactive-pool',
            'base_currency'  => 'DOT',
            'quote_currency' => 'USDT',
            'base_reserve'   => '0',
            'quote_reserve'  => '0',
            'total_shares'   => '0',
            'fee_rate'       => '0.003',
            'is_active'      => false,
        ]);

        $activePools = $this->service->getActivePools();

        $this->assertCount(2, $activePools);
        $this->assertTrue($activePools->every(fn ($pool) => $pool->is_active === true));
    }

    #[Test]
    public function test_get_provider_positions_returns_positions_with_pools(): void
    {
        $providerId = 'provider-123';

        // Create pool
        $pool = PoolProjection::create([
            'pool_id'        => 'pool-abc',
            'base_currency'  => 'ETH',
            'quote_currency' => 'USDT',
            'base_reserve'   => '1000000',
            'quote_reserve'  => '2000000',
            'total_shares'   => '1414213',
            'fee_rate'       => '0.003',
            'is_active'      => true,
        ]);

        // Create provider positions
        LiquidityProvider::create([
            'pool_id'         => 'pool-abc',
            'provider_id'     => $providerId,
            'shares'          => '500000',
            'base_deposited'  => '353553',
            'quote_deposited' => '707107',
        ]);

        LiquidityProvider::create([
            'pool_id'         => 'pool-xyz',
            'provider_id'     => $providerId,
            'shares'          => '200000',
            'base_deposited'  => '100000',
            'quote_deposited' => '200000',
        ]);

        $positions = $this->service->getProviderPositions($providerId);

        $this->assertCount(2, $positions);
        $this->assertTrue($positions->every(fn ($pos) => $pos->provider_id === $providerId));
    }

    #[Test]
    public function test_get_pool_metrics_calculates_correct_values(): void
    {
        // Create pool with activity
        $pool = PoolProjection::create([
            'pool_id'            => 'metrics-pool',
            'base_currency'      => 'ETH',
            'quote_currency'     => 'USDT',
            'base_reserve'       => '1000',
            'quote_reserve'      => '2000000',
            'total_shares'       => '44721',
            'fee_rate'           => '0.003',
            'volume_24h'         => '500000',
            'fees_collected_24h' => '1500',
            'is_active'          => true,
        ]);

        // Create some providers
        LiquidityProvider::create([
            'pool_id'     => 'metrics-pool',
            'provider_id' => 'provider-1',
            'shares'      => '20000',
        ]);

        LiquidityProvider::create([
            'pool_id'     => 'metrics-pool',
            'provider_id' => 'provider-2',
            'shares'      => '24721',
        ]);

        $metrics = $this->service->getPoolMetrics('metrics-pool');

        $this->assertEquals('metrics-pool', $metrics['pool_id']);
        $this->assertEquals('ETH', $metrics['base_currency']);
        $this->assertEquals('USDT', $metrics['quote_currency']);
        $this->assertEquals('1000', $metrics['base_reserve']);
        $this->assertEquals('2000000', $metrics['quote_reserve']);
        $this->assertEquals('44721', $metrics['total_shares']);
        $this->assertEquals('500000', $metrics['volume_24h']);
        $this->assertEquals('1500', $metrics['fees_24h']);
        $this->assertEquals(2, $metrics['provider_count']);

        // Verify calculated values
        $this->assertArrayHasKey('spot_price', $metrics);
        $this->assertArrayHasKey('tvl', $metrics);
        $this->assertArrayHasKey('apy', $metrics);

        // Spot price should be quote/base = 2000000/1000 = 2000
        $this->assertEquals('2000', $metrics['spot_price']);

        // TVL should be (base * spot_price) + quote = (1000 * 2000) + 2000000 = 4000000
        $this->assertEquals('4000000', $metrics['tvl']);
    }

    #[Test]
    public function test_rebalance_pool_delegates_to_workflow(): void
    {
        $poolId = 'pool-123';
        $targetRatio = '1.5';

        $expectedResult = [
            'rebalanced'       => true,
            'base_adjustment'  => '-100000',
            'quote_adjustment' => '150000',
            'new_ratio'        => '1.5',
        ];

        WorkflowStub::fake();
        WorkflowStub::mock(LiquidityManagementWorkflow::class, $expectedResult);

        $result = $this->service->rebalancePool($poolId, $targetRatio);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function test_distribute_rewards_calls_aggregate(): void
    {
        $poolId = 'pool-123';
        $rewardAmount = '10000';
        $rewardCurrency = 'USDT';
        $metadata = ['campaign' => 'monthly-rewards'];

        $poolAggregate = Mockery::mock(LiquidityPool::class);
        $poolAggregate->shouldReceive('distributeRewards')
            ->once()
            ->with($rewardAmount, $rewardCurrency, $metadata)
            ->andReturnSelf();
        $poolAggregate->shouldReceive('persist')
            ->once();

        LiquidityPool::shouldReceive('retrieve')
            ->with($poolId)
            ->andReturn($poolAggregate);

        $this->service->distributeRewards($poolId, $rewardAmount, $rewardCurrency, $metadata);
    }

    #[Test]
    public function test_claim_rewards_processes_pending_rewards(): void
    {
        $poolId = 'pool-123';
        $providerId = 'provider-456';

        // Create provider with pending rewards
        $provider = LiquidityProvider::create([
            'pool_id'         => $poolId,
            'provider_id'     => $providerId,
            'shares'          => '100000',
            'pending_rewards' => [
                'USDT' => '1500',
                'ETH'  => '0.01',
            ],
        ]);

        $poolAggregate = Mockery::mock(LiquidityPool::class);
        $poolAggregate->shouldReceive('claimRewards')
            ->once()
            ->with($providerId)
            ->andReturnSelf();
        $poolAggregate->shouldReceive('persist')
            ->once();

        LiquidityPool::shouldReceive('retrieve')
            ->with($poolId)
            ->andReturn($poolAggregate);

        // Expect transfers for each reward currency
        $this->exchangeService->shouldReceive('transferFromPool')
            ->times(2);

        $result = $this->service->claimRewards($poolId, $providerId);

        $this->assertEquals(['USDT' => '1500', 'ETH' => '0.01'], $result);
    }

    #[Test]
    public function test_claim_rewards_throws_exception_when_no_rewards(): void
    {
        $poolId = 'pool-123';
        $providerId = 'provider-456';

        // Create provider without pending rewards
        LiquidityProvider::create([
            'pool_id'         => $poolId,
            'provider_id'     => $providerId,
            'shares'          => '100000',
            'pending_rewards' => [],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No rewards to claim');

        $this->service->claimRewards($poolId, $providerId);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
