<?php

namespace App\Models;

use App\Models\User;
use App\Traits\HasDynamicClientTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{
    use HasFactory;
    use HasDynamicClientTable;

    public $guarded = [];

    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'accounts_' . $this->getCustomerId();
    }

    /**
     * @param string $uuid
     *
     * @return self
     */
    public static function uuid( string $uuid): self
    {
        return static::where('uuid', $uuid)->first();
    }

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

        return;
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

        return;
    }
}
