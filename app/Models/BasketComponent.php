<?php

namespace App\Models;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasketComponent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'basket_asset_id',
        'asset_code',
        'weight',
        'min_weight',
        'max_weight',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'weight' => 'float',
        'min_weight' => 'float',
        'max_weight' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Get the basket that owns this component.
     */
    public function basket(): BelongsTo
    {
        return $this->belongsTo(BasketAsset::class, 'basket_asset_id');
    }

    /**
     * Get the asset for this component.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_code', 'code');
    }

    /**
     * Check if the component weight is within allowed bounds.
     */
    public function isWithinBounds(float $currentWeight): bool
    {
        if ($this->min_weight !== null && $currentWeight < $this->min_weight) {
            return false;
        }

        if ($this->max_weight !== null && $currentWeight > $this->max_weight) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the value contribution of this component in USD.
     */
    public function calculateValueContribution(): float
    {
        $asset = $this->asset;
        if (!$asset) {
            return 0.0;
        }

        // Get exchange rate to USD
        $rate = 1.0;
        if ($this->asset_code !== 'USD') {
            $exchangeRate = app(\App\Domain\Asset\Services\ExchangeRateService::class)
                ->getRate($this->asset_code, 'USD');
                
            if ($exchangeRate) {
                $rate = $exchangeRate->rate;
            }
        }

        return $rate * ($this->weight / 100);
    }

    /**
     * Scope a query to only include active components.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Validate the component configuration.
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->weight < 0 || $this->weight > 100) {
            $errors[] = 'Weight must be between 0 and 100';
        }

        if ($this->min_weight !== null) {
            if ($this->min_weight < 0 || $this->min_weight > 100) {
                $errors[] = 'Minimum weight must be between 0 and 100';
            }
            if ($this->min_weight > $this->weight) {
                $errors[] = 'Minimum weight cannot be greater than weight';
            }
        }

        if ($this->max_weight !== null) {
            if ($this->max_weight < 0 || $this->max_weight > 100) {
                $errors[] = 'Maximum weight must be between 0 and 100';
            }
            if ($this->max_weight < $this->weight) {
                $errors[] = 'Maximum weight cannot be less than weight';
            }
        }

        if ($this->min_weight !== null && $this->max_weight !== null) {
            if ($this->min_weight > $this->max_weight) {
                $errors[] = 'Minimum weight cannot be greater than maximum weight';
            }
        }

        return $errors;
    }
}