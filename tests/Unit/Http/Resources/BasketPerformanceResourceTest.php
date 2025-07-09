<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\BasketPerformanceResource;
use App\Http\Resources\ComponentPerformanceResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class BasketPerformanceResourceTest extends TestCase
{
    private function createBasketPerformance(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'basket_asset_code' => 'GCU',
            'period_type' => 'day',
            'period_start' => now()->subDay(),
            'period_end' => now(),
            'start_value' => 100.0,
            'end_value' => 105.0,
            'high_value' => 107.0,
            'low_value' => 99.0,
            'average_value' => 103.0,
            'return_value' => 5.0,
            'return_percentage' => 5.0,
            'volatility' => 12.0,
            'sharpe_ratio' => 1.2,
            'max_drawdown' => 2.0,
            'value_count' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($defaults, $attributes);
        
        return new class($data) {
            private array $attributes;
            private array $relations = [];

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }

            public function __get($name)
            {
                if ($name === 'formatted_return') {
                    return $this->getFormattedReturnAttribute();
                }
                if ($name === 'performance_rating') {
                    return $this->getPerformanceRatingAttribute();
                }
                if ($name === 'risk_rating') {
                    return $this->getRiskRatingAttribute();
                }
                return $this->attributes[$name] ?? null;
            }

            public function getFormattedReturnAttribute(): string
            {
                $prefix = $this->return_percentage >= 0 ? '+' : '';
                return $prefix . number_format($this->return_percentage, 2) . '%';
            }

            public function getPerformanceRatingAttribute(): string
            {
                if ($this->return_percentage >= 10) {
                    return 'excellent';
                } elseif ($this->return_percentage >= 5) {
                    return 'good';
                } elseif ($this->return_percentage >= 0) {
                    return 'neutral';
                } elseif ($this->return_percentage >= -5) {
                    return 'poor';
                } else {
                    return 'very_poor';
                }
            }

            public function getRiskRatingAttribute(): string
            {
                if ($this->volatility <= 5) {
                    return 'very_low';
                } elseif ($this->volatility <= 10) {
                    return 'low';
                } elseif ($this->volatility <= 20) {
                    return 'moderate';
                } elseif ($this->volatility <= 30) {
                    return 'high';
                } else {
                    return 'very_high';
                }
            }

            public function getAnnualizedReturn(): float
            {
                return 21.5; // Simplified for testing
            }

            public function relationLoaded($relation): bool
            {
                return isset($this->relations[$relation]);
            }
            
            public function whenLoaded($relationship, $value = null, $default = null)
            {
                if ($this->relationLoaded($relationship)) {
                    return $value ?? $this->getRelation($relationship);
                }
                
                return $default ?? new \Illuminate\Http\Resources\MissingValue();
            }

            public function setRelation($relation, $value)
            {
                $this->relations[$relation] = $value;
                return $this;
            }

            public function getRelation($relation)
            {
                return $this->relations[$relation] ?? null;
            }

            public function __isset($name)
            {
                return isset($this->attributes[$name]);
            }
        };
    }

    public function test_transforms_basket_performance_to_array(): void
    {
        $basketPerformance = $this->createBasketPerformance([
            'basket_asset_code' => 'GCU',
            'period_type' => 'day',
            'start_value' => 100.123456,
            'end_value' => 105.987654,
            'high_value' => 107.555555,
            'low_value' => 99.111111,
            'average_value' => 103.333333,
            'return_value' => 5.864198,
            'return_percentage' => 5.8642,
            'volatility' => 12.3456,
            'sharpe_ratio' => 1.2345,
            'max_drawdown' => 2.3456,
            'value_count' => 24,
        ]);

        $resource = new BasketPerformanceResource($basketPerformance);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertEquals('GCU', $array['basket_code']);
        $this->assertEquals('day', $array['period_type']);
        $this->assertEquals(100.1235, $array['start_value']);
        $this->assertEquals(105.9877, $array['end_value']);
        $this->assertEquals(107.5556, $array['high_value']);
        $this->assertEquals(99.1111, $array['low_value']);
        $this->assertEquals(103.3333, $array['average_value']);
        $this->assertEquals(5.8642, $array['return_value']);
        $this->assertEquals(5.86, $array['return_percentage']);
        $this->assertEquals('+5.86%', $array['formatted_return']);
        $this->assertEquals(12.35, $array['volatility']);
        $this->assertEquals(1.23, $array['sharpe_ratio']);
        $this->assertEquals(2.35, $array['max_drawdown']);
        $this->assertEquals('good', $array['performance_rating']);
        $this->assertEquals('moderate', $array['risk_rating']);
        $this->assertEquals(21.5, $array['annualized_return']);
        $this->assertEquals(24, $array['value_count']);
    }

    public function test_handles_null_risk_metrics(): void
    {
        $basketPerformance = $this->createBasketPerformance([
            'volatility' => null,
            'sharpe_ratio' => null,
            'max_drawdown' => null,
        ]);

        $resource = new BasketPerformanceResource($basketPerformance);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertNull($array['volatility']);
        $this->assertNull($array['sharpe_ratio']);
        $this->assertNull($array['max_drawdown']);
    }

    public function test_includes_components_when_loaded(): void
    {
        $basketPerformance = $this->createBasketPerformance();
        
        // Create mock components with proper structure
        $components = collect([
            (object) ['id' => 1, 'asset_code' => 'BTC'],
            (object) ['id' => 2, 'asset_code' => 'ETH'], 
            (object) ['id' => 3, 'asset_code' => 'USDT'],
        ]);

        $basketPerformance->setRelation('componentPerformances', $components);

        $resource = new BasketPerformanceResource($basketPerformance);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertArrayHasKey('components', $array);
        $this->assertCount(3, $array['components']);
    }

    public function test_excludes_components_when_not_loaded(): void
    {
        $basketPerformance = $this->createBasketPerformance();

        $resource = new BasketPerformanceResource($basketPerformance);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        // whenLoaded() returns an empty array when relation is not loaded
        $this->assertEmpty($array['components']);
    }

    public function test_includes_all_required_fields(): void
    {
        $basketPerformance = $this->createBasketPerformance();
        
        $resource = new BasketPerformanceResource($basketPerformance);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $expectedKeys = [
            'id',
            'basket_code',
            'period_type',
            'period_start',
            'period_end',
            'start_value',
            'end_value',
            'high_value',
            'low_value',
            'average_value',
            'return_value',
            'return_percentage',
            'formatted_return',
            'volatility',
            'sharpe_ratio',
            'max_drawdown',
            'performance_rating',
            'risk_rating',
            'annualized_return',
            'value_count',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    public function test_resource_collection(): void
    {
        $performances = [
            $this->createBasketPerformance(['id' => 1, 'basket_asset_code' => 'GCU', 'period_type' => 'day']),
            $this->createBasketPerformance(['id' => 2, 'basket_asset_code' => 'EUR', 'period_type' => 'week']),
            $this->createBasketPerformance(['id' => 3, 'basket_asset_code' => 'USD', 'period_type' => 'month']),
        ];
        
        $collection = BasketPerformanceResource::collection($performances);
        $request = Request::create('/');
        $array = $collection->toArray($request);

        $this->assertCount(3, $array);
        $this->assertEquals(1, $array[0]['id']);
        $this->assertEquals('GCU', $array[0]['basket_code']);
        $this->assertEquals('day', $array[0]['period_type']);
    }
}