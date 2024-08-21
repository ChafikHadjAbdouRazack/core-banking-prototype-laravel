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
     * @param array $attributes
     * @param $customerId
     */
    public function __construct( array $attributes = [], $customerId = null)
    {
        parent::__construct($attributes);
        $this->setTable( 'accounts_' . $this->getCustomerId($customerId));
    }

    /**
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($account) {
            if ($customerId = $this->getCustomerId()) {
                $account->setTable('accounts_' . $customerId);
            } else {
                throw new \Exception('Could not determine customer id');
            }
        });
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        // Ensure that the dynamic table name is always set
        if (!$this->table) {
            $this->setTable('accounts_' . $this->getCustomerId());
        }

        return parent::getTable();
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
            related: User::class
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
