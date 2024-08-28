<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    use HasFactory;

    public $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: User::class,
            foreignKey: 'user_uuid',
            ownerKey: 'uuid'
        );
    }

    /**
     * @param int $amount
     *
     * @return void
     */
    public function addMoney(int $amount): void
    {
        $this->balance += $amount;
        $this->save();
    }

    /**
     * @param int $amount
     *
     * @return void
     */
    public function subtractMoney(int $amount): void
    {
        $this->balance -= $amount;
        $this->save();
    }
}
