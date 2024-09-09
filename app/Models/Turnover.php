<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Turnover extends Model
{
    protected $fillable
        = [
            'date',
            'account_uuid',
            'count',
            'amount',
        ];

    protected $casts
        = [
            'date' => 'date',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(
            related: Account::class,
            foreignKey: 'account_uuid',
            ownerKey: 'uuid'
        );
    }
}
