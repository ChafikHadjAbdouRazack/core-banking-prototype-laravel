<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Stablecoin\Services;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\ExchangeRate;
use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Stablecoin\Services\CollateralService;
use App\Models\Account;
use App\Models\Stablecoin;
use App\Models\StablecoinCollateralPosition;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class CollateralServiceTest extends ServiceTestCase
{
    protected CollateralService $service;

    protected $exchangeRateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
        $this->service = new CollateralService($this->exchangeRateService);

        // Create assets if they don't exist
        Asset::firstOrCreate(
            ['code' => 'USD'],
            [
                'name'      => 'US Dollar',
                'type'      => 'fiat',
                'precision' => 2,
                'is_active' => true,
            ]
        );

        Asset::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name'      => 'Euro',
                'type'      => 'fiat',
                'precision' => 2,
                'is_active' => true,
            ]
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_convert_to_peg_asset()
    {
        // Test same asset conversion
        $result = $this->service->convertToPegAsset('USD', 100000, 'USD');
        $this->assertEquals(100000, $result);

        // Test different asset conversion
        $mockRate = new ExchangeRate([
            'from_asset_code' => 'EUR',
            'to_asset_code'   => 'USD',
            'rate'            => 1.1,
            'source'          => ExchangeRate::SOURCE_API,
            'valid_at'        => now(),
            'expires_at'      => now()->addHour(),
            'is_active'       => true,
        ]);

        $this->exchangeRateService
            ->shouldReceive('getRate')
            ->with('EUR', 'USD')
            ->once()
            ->andReturn($mockRate);

        $result = $this->service->convertToPegAsset('EUR', 100000, 'USD');
        $this->assertEquals(110000, $result);
    }

    #[Test]
    public function it_can_calculate_total_collateral_value()
    {
        $stablecoin = Stablecoin::create([
            'code'                   => 'FUSD',
            'name'                   => 'FinAegis USD',
            'symbol'                 => 'FUSD',
            'peg_asset_code'         => 'USD',
            'peg_ratio'              => 1.0,
            'target_price'           => 1.0,
            'stability_mechanism'    => 'collateralized',
            'collateral_ratio'       => 1.5,
            'min_collateral_ratio'   => 1.2,
            'liquidation_penalty'    => 0.1,
            'total_supply'           => 0,
            'max_supply'             => 10000000,
            'total_collateral_value' => 0,
            'mint_fee'               => 0.005,
            'burn_fee'               => 0.003,
            'precision'              => 2,
            'is_active'              => true,
            'minting_enabled'        => true,
            'burning_enabled'        => true,
        ]);

        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        // Create positions with different accounts
        StablecoinCollateralPosition::create([
            'account_uuid'          => $account1->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 100000,
            'debt_amount'           => 50000,
            'collateral_ratio'      => 2.0,
            'status'                => 'active',
        ]);

        StablecoinCollateralPosition::create([
            'account_uuid'          => $account2->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'EUR',
            'collateral_amount'     => 50000,
            'debt_amount'           => 25000,
            'collateral_ratio'      => 2.0,
            'status'                => 'active',
        ]);

        $mockRate = new ExchangeRate([
            'from_asset_code' => 'EUR',
            'to_asset_code'   => 'USD',
            'rate'            => 1.1,
            'source'          => ExchangeRate::SOURCE_API,
            'valid_at'        => now(),
            'expires_at'      => now()->addHour(),
            'is_active'       => true,
        ]);

        $this->exchangeRateService
            ->shouldReceive('getRate')
            ->with('EUR', 'USD')
            ->once()
            ->andReturn($mockRate);

        $totalValue = $this->service->calculateTotalCollateralValue('FUSD');
        $this->assertEquals(155000, $totalValue); // 100000 + (50000 * 1.1)
    }

    #[Test]
    public function it_can_calculate_position_health_score()
    {
        $stablecoin = Stablecoin::create([
            'code'                   => 'FUSD',
            'name'                   => 'FinAegis USD',
            'symbol'                 => 'FUSD',
            'peg_asset_code'         => 'USD',
            'peg_ratio'              => 1.0,
            'target_price'           => 1.0,
            'stability_mechanism'    => 'collateralized',
            'collateral_ratio'       => 1.5,
            'min_collateral_ratio'   => 1.2,
            'liquidation_penalty'    => 0.1,
            'total_supply'           => 0,
            'max_supply'             => 10000000,
            'total_collateral_value' => 0,
            'mint_fee'               => 0.005,
            'burn_fee'               => 0.003,
            'precision'              => 2,
            'is_active'              => true,
            'minting_enabled'        => true,
            'burning_enabled'        => true,
        ]);

        $account = Account::factory()->create();

        $position = StablecoinCollateralPosition::create([
            'account_uuid'          => $account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'debt_amount'           => 100000,
            'collateral_ratio'      => 1.5,
            'status'                => 'active',
        ]);

        // Health score = (1.5 - 1.2) / 1.2 = 0.25
        $healthScore = $this->service->calculatePositionHealthScore($position);
        $this->assertEqualsWithDelta(0.25, $healthScore, 0.0001);

        // Test with zero debt
        $position->debt_amount = 0;
        $healthScore = $this->service->calculatePositionHealthScore($position);
        $this->assertEquals(1.0, $healthScore);

        // Test at liquidation threshold
        $position->debt_amount = 100000;
        $position->collateral_ratio = 1.2;
        $healthScore = $this->service->calculatePositionHealthScore($position);
        $this->assertEquals(0.0, $healthScore);
    }

    #[Test]
    public function it_can_calculate_liquidation_priority()
    {
        $stablecoin = Stablecoin::create([
            'code'                   => 'FUSD',
            'name'                   => 'FinAegis USD',
            'symbol'                 => 'FUSD',
            'peg_asset_code'         => 'USD',
            'peg_ratio'              => 1.0,
            'target_price'           => 1.0,
            'stability_mechanism'    => 'collateralized',
            'collateral_ratio'       => 1.5,
            'min_collateral_ratio'   => 1.2,
            'liquidation_penalty'    => 0.1,
            'total_supply'           => 0,
            'max_supply'             => 10000000,
            'total_collateral_value' => 0,
            'mint_fee'               => 0.005,
            'burn_fee'               => 0.003,
            'precision'              => 2,
            'is_active'              => true,
            'minting_enabled'        => true,
            'burning_enabled'        => true,
        ]);

        $account = Account::factory()->create();

        $position = StablecoinCollateralPosition::create([
            'account_uuid'          => $account->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 150000,
            'debt_amount'           => 100000,
            'collateral_ratio'      => 1.5,
            'status'                => 'active',
            'last_interaction_at'   => now()->subDays(3),
        ]);

        $priority = $this->service->calculateLiquidationPriority($position);

        // Priority should be between 0 and 1
        $this->assertGreaterThanOrEqual(0, $priority);
        $this->assertLessThanOrEqual(1, $priority);

        // Lower health should increase priority
        $position->collateral_ratio = 1.25;
        $priority2 = $this->service->calculateLiquidationPriority($position);
        $this->assertGreaterThan($priority, $priority2);
    }

    #[Test]
    public function it_can_get_position_recommendations()
    {
        $stablecoin = Stablecoin::create([
            'code'                   => 'FUSD',
            'name'                   => 'FinAegis USD',
            'symbol'                 => 'FUSD',
            'peg_asset_code'         => 'USD',
            'peg_ratio'              => 1.0,
            'target_price'           => 1.0,
            'stability_mechanism'    => 'collateralized',
            'collateral_ratio'       => 1.5,
            'min_collateral_ratio'   => 1.2,
            'liquidation_penalty'    => 0.1,
            'total_supply'           => 0,
            'max_supply'             => 10000000,
            'total_collateral_value' => 0,
            'mint_fee'               => 0.005,
            'burn_fee'               => 0.003,
            'precision'              => 2,
            'is_active'              => true,
            'minting_enabled'        => true,
            'burning_enabled'        => true,
        ]);

        $account = Account::factory()->create();

        // Very healthy position
        $position = StablecoinCollateralPosition::create([
            'account_uuid'             => $account->uuid,
            'stablecoin_code'          => 'FUSD',
            'collateral_asset_code'    => 'USD',
            'collateral_amount'        => 300000,
            'debt_amount'              => 100000,
            'collateral_ratio'         => 3.0,
            'status'                   => 'active',
            'auto_liquidation_enabled' => true,
        ]);

        // Mock getRate for healthy position (USD to USD = 1.0)
        $this->exchangeRateService
            ->shouldReceive('getRate')
            ->with('USD', 'USD')
            ->andReturn(null); // Same currency, no rate needed

        $recommendations = $this->service->getPositionRecommendations($position);
        $this->assertCount(1, $recommendations);
        $this->assertEquals('mint_more', $recommendations[0]['action']);

        // At-risk position - reduce collateral to force low ratio
        $position->collateral_amount = 125000; // This will give ratio of 1.25
        $position->collateral_ratio = 1.25;
        $position->save();

        // Mock getRate for at-risk position (USD to USD = 1.0)
        $this->exchangeRateService
            ->shouldReceive('getRate')
            ->with('USD', 'USD')
            ->andReturn(null); // Same currency, no rate needed

        $recommendations = $this->service->getPositionRecommendations($position);

        // With ratio of 1.25 and min_ratio of 1.2:
        // health_score = (1.25 - 1.2) / 1.2 = 0.0417 which is < 0.2
        $this->assertCount(1, $recommendations);
        $this->assertEquals('add_collateral', $recommendations[0]['action']);
        $this->assertEquals('high', $recommendations[0]['urgency']);
    }

    #[Test]
    public function it_can_get_collateral_distribution()
    {
        $stablecoin = Stablecoin::create([
            'code'                   => 'FUSD',
            'name'                   => 'FinAegis USD',
            'symbol'                 => 'FUSD',
            'peg_asset_code'         => 'USD',
            'peg_ratio'              => 1.0,
            'target_price'           => 1.0,
            'stability_mechanism'    => 'collateralized',
            'collateral_ratio'       => 1.5,
            'min_collateral_ratio'   => 1.2,
            'liquidation_penalty'    => 0.1,
            'total_supply'           => 0,
            'max_supply'             => 10000000,
            'total_collateral_value' => 0,
            'mint_fee'               => 0.005,
            'burn_fee'               => 0.003,
            'precision'              => 2,
            'is_active'              => true,
            'minting_enabled'        => true,
            'burning_enabled'        => true,
        ]);

        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        $account3 = Account::factory()->create();

        // Create positions with different accounts to avoid unique constraint
        StablecoinCollateralPosition::create([
            'account_uuid'          => $account1->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 100000,
            'debt_amount'           => 50000,
            'collateral_ratio'      => 2.0,
            'status'                => 'active',
        ]);

        StablecoinCollateralPosition::create([
            'account_uuid'          => $account2->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'USD',
            'collateral_amount'     => 50000,
            'debt_amount'           => 25000,
            'collateral_ratio'      => 2.0,
            'status'                => 'active',
        ]);

        StablecoinCollateralPosition::create([
            'account_uuid'          => $account3->uuid,
            'stablecoin_code'       => 'FUSD',
            'collateral_asset_code' => 'EUR',
            'collateral_amount'     => 50000,
            'debt_amount'           => 25000,
            'collateral_ratio'      => 2.0,
            'status'                => 'active',
        ]);

        $mockRate = new ExchangeRate([
            'from_asset_code' => 'EUR',
            'to_asset_code'   => 'USD',
            'rate'            => 1.1,
            'source'          => ExchangeRate::SOURCE_API,
            'valid_at'        => now(),
            'expires_at'      => now()->addHour(),
            'is_active'       => true,
        ]);

        $this->exchangeRateService
            ->shouldReceive('getRate')
            ->with('EUR', 'USD')
            ->once()
            ->andReturn($mockRate);

        $distribution = $this->service->getCollateralDistribution('FUSD');

        $this->assertCount(2, $distribution);
        $this->assertEquals(150000, $distribution['USD']['total_amount']);
        $this->assertEquals(2, $distribution['USD']['position_count']);
        $this->assertEquals(50000, $distribution['EUR']['total_amount']);
        $this->assertEquals(1, $distribution['EUR']['position_count']);

        // Check percentages
        $this->assertEqualsWithDelta(73.17, $distribution['USD']['percentage'], 0.01);
        $this->assertEqualsWithDelta(26.83, $distribution['EUR']['percentage'], 0.01);
    }
}
