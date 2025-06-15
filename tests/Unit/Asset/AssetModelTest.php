<?php

namespace Tests\Unit\Asset;

use Tests\TestCase;
use App\Domain\Asset\Models\Asset;
use App\Models\AccountBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AssetModelTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function it_can_create_an_asset()
    {
        $asset = Asset::create([
            'code' => 'TEST',
            'name' => 'Test Asset',
            'type' => Asset::TYPE_CUSTOM,
            'precision' => 2,
            'is_active' => true,
            'metadata' => ['symbol' => 'T'],
        ]);
        
        $this->assertDatabaseHas('assets', [
            'code' => 'TEST',
            'name' => 'Test Asset',
            'type' => Asset::TYPE_CUSTOM,
            'precision' => 2,
            'is_active' => true,
        ]);
        
        expect($asset->getSymbol())->toBe('T');
    }
    
    #[Test]
    public function it_has_correct_asset_types()
    {
        $types = Asset::getTypes();
        
        expect($types)->toContain(Asset::TYPE_FIAT);
        expect($types)->toContain(Asset::TYPE_CRYPTO);
        expect($types)->toContain(Asset::TYPE_COMMODITY);
        expect($types)->toContain(Asset::TYPE_CUSTOM);
        expect($types)->toHaveCount(4);
    }
    
    #[Test]
    public function it_can_format_amounts_correctly()
    {
        $usd = Asset::factory()->create([
            'code' => 'USD',
            'precision' => 2,
            'metadata' => ['symbol' => '$'],
        ]);
        
        $btc = Asset::factory()->create([
            'code' => 'BTC',
            'precision' => 8,
            'metadata' => ['symbol' => '₿'],
        ]);
        
        expect($usd->formatAmount(10050))->toBe('$100.50');
        expect($btc->formatAmount(100000000))->toBe('₿1.00000000');
    }
    
    #[Test]
    public function it_can_convert_to_and_from_smallest_unit()
    {
        $asset = Asset::factory()->create(['precision' => 2]);
        
        // To smallest unit
        expect($asset->toSmallestUnit(100.50))->toBe(10050);
        expect($asset->toSmallestUnit(100))->toBe(10000);
        expect($asset->toSmallestUnit(0.01))->toBe(1);
        
        // From smallest unit
        expect($asset->fromSmallestUnit(10050))->toBe(100.50);
        expect($asset->fromSmallestUnit(10000))->toBe(100.0);
        expect($asset->fromSmallestUnit(1))->toBe(0.01);
    }
    
    #[Test]
    public function it_can_check_asset_type()
    {
        $fiat = Asset::factory()->fiat()->create();
        $crypto = Asset::factory()->crypto()->create();
        $commodity = Asset::factory()->commodity()->create();
        
        expect($fiat->isFiat())->toBeTrue();
        expect($fiat->isCrypto())->toBeFalse();
        expect($fiat->isCommodity())->toBeFalse();
        
        expect($crypto->isFiat())->toBeFalse();
        expect($crypto->isCrypto())->toBeTrue();
        expect($crypto->isCommodity())->toBeFalse();
        
        expect($commodity->isFiat())->toBeFalse();
        expect($commodity->isCrypto())->toBeFalse();
        expect($commodity->isCommodity())->toBeTrue();
    }
    
    #[Test]
    public function it_can_scope_active_assets()
    {
        Asset::factory()->count(3)->active()->create();
        Asset::factory()->count(2)->inactive()->create();
        
        $activeAssets = Asset::active()->get();
        
        expect($activeAssets)->toHaveCount(3);
        expect(Asset::count())->toBe(5);
    }
    
    #[Test]
    public function it_can_scope_assets_by_type()
    {
        Asset::factory()->count(2)->fiat()->create();
        Asset::factory()->count(3)->crypto()->create();
        Asset::factory()->count(1)->commodity()->create();
        
        expect(Asset::ofType(Asset::TYPE_FIAT)->count())->toBe(2);
        expect(Asset::ofType(Asset::TYPE_CRYPTO)->count())->toBe(3);
        expect(Asset::ofType(Asset::TYPE_COMMODITY)->count())->toBe(1);
    }
    
    #[Test]
    public function it_has_account_balances_relationship()
    {
        $asset = Asset::factory()->create(['code' => 'TEST']);
        $balances = AccountBalance::factory()->count(3)->forAsset($asset)->create();
        
        expect($asset->accountBalances)->toHaveCount(3);
        expect($asset->accountBalances->first())->toBeInstanceOf(AccountBalance::class);
    }
}