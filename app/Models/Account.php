<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasFactory;
    use HasVersion4Uuids;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

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
     * @return static
     */
    public function addMoney(int $amount): static
    {
        $this->balance += $amount;
        $this->save();

        return $this;
    }

    /**
     * @param int $amount
     *
     * @return static
     */
    public function subtractMoney(int $amount): static
    {
        $this->balance -= $amount;
        $this->save();

        return $this;
    }
}
