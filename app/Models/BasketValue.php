<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasketValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'basket_asset_code',
        'value',
        'calculated_at',
        'component_values',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value'            => 'float',
        'calculated_at'    => 'datetime',
        'component_values' => 'array',
    ];

    /**
     * Get the basket associated with this value.
     */
    public function basket(): BelongsTo
    {
        return $this->belongsTo(BasketAsset::class, 'basket_asset_code', 'code');
    }

    /**
     * Get the total weight of all components.
     */
    public function getTotalWeight(): float
    {
        if (! $this->component_values) {
            return 0.0;
        }

        return collect($this->component_values)
            ->sum('weight');
    }

    /**
     * Get the value contribution of a specific component.
     */
    public function getComponentValue(string $assetCode): ?float
    {
        if (! $this->component_values || ! isset($this->component_values[$assetCode])) {
            return null;
        }

        return $this->component_values[$assetCode]['weighted_value'] ?? null;
    }

    /**
     * Get the actual weight of a component based on current values.
     */
    public function getActualWeight(string $assetCode): ?float
    {
        $componentValue = $this->getComponentValue($assetCode);
        if ($componentValue === null || $this->value == 0) {
            return null;
        }

        return ($componentValue / $this->value) * 100;
    }

    /**
     * Check if this value calculation is recent.
     */
    public function isFresh(int $minutes = 5): bool
    {
        return $this->calculated_at->diffInMinutes(now()) <= $minutes;
    }

    /**
     * Get the performance compared to a previous value.
     */
    public function getPerformance(self $previousValue): array
    {
        $change = $this->value - $previousValue->value;
        $percentageChange = $previousValue->value > 0
            ? ($change / $previousValue->value) * 100
            : 0;

        return [
            'previous_value'    => $previousValue->value,
            'current_value'     => $this->value,
            'change'            => $change,
            'percentage_change' => $percentageChange,
            'time_period'       => $previousValue->calculated_at->diffForHumans($this->calculated_at),
        ];
    }

    /**
     * Scope a query to get values within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculated_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get the latest value for each basket.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculated_at', 'desc');
    }
}
