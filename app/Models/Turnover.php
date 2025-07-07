<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Turnover extends Model
{
    use HasFactory;

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

    /**
     * @return BelongsTo
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
