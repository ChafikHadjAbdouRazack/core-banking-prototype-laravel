<?php

namespace App\Domain\Account\Models;

use App\Domain\Account\Models\Account;
use Database\Factories\TurnoverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static \Illuminate\Database\Eloquent\Builder lockForUpdate()
 * @method static static updateOrCreate(array $attributes, array $values = [])
 */
class Turnover extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return TurnoverFactory::new();
    }

    protected $fillable
        = [
            'date',
            'account_uuid',
            'count',
            'amount',
            'debit',
            'credit',
        ];

    protected $casts
        = [
            'date' => 'date',
        ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            related: Account::class,
            foreignKey: 'account_uuid',
            ownerKey: 'uuid'
        );
    }

    /**
     * Get the activity logs for this model.
     */
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function logs()
    {
        return $this->morphMany(\App\Domain\Activity\Models\Activity::class, 'subject');
    }
}
