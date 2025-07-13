<?php

namespace App\Domain\Exchange\Projections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $pool_id
 * @property string $account_id
 * @property string $base_currency
 * @property string $quote_currency
 * @property string $base_reserve
 * @property string $quote_reserve
 * @property string $total_shares
 * @property string $fee_rate
 * @property bool $is_active
 * @property string $volume_24h
 * @property string $fees_collected_24h
 * @property array<string, mixed> $metadata
 * @property-read string $spot_price
 * @property-read string $total_value_locked
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LiquidityPool where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static LiquidityPool|null find($id, $columns = ['*'])
 * @method static LiquidityPool findOrFail($id, $columns = ['*'])
 * @method static LiquidityPool firstOrFail($columns = ['*'])
 * @method static LiquidityPool create(array $attributes)
 */
class LiquidityPool extends Model
{
    protected $table = 'liquidity_pools';

    protected $fillable = [
        'pool_id',
        'account_id',
        'base_currency',
        'quote_currency',
        'base_reserve',
        'quote_reserve',
        'total_shares',
        'fee_rate',
        'is_active',
        'volume_24h',
        'fees_collected_24h',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function providers(): HasMany
    {
        return $this->hasMany(LiquidityProvider::class, 'pool_id', 'pool_id');
    }

    public function swaps(): HasMany
    {
        return $this->hasMany(PoolSwap::class, 'pool_id', 'pool_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPair($query, string $baseCurrency, string $quoteCurrency)
    {
        return $query->where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency);
    }

    public function getSpotPriceAttribute(): string
    {
        if ($this->base_reserve == 0) {
            return '0';
        }

        return \Brick\Math\BigDecimal::of($this->quote_reserve)
            ->dividedBy($this->base_reserve, 18)
            ->__toString();
    }

    public function getTotalValueLockedAttribute(): string
    {
        // This would need exchange rates to calculate in a common currency
        return \Brick\Math\BigDecimal::of($this->base_reserve)
            ->plus($this->quote_reserve)
            ->__toString();
    }
}
