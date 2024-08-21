<?php

namespace App\Models;

use App\Traits\HasDynamicClientTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class Transaction extends EloquentStoredEvent
{
    use HasDynamicClientTable;

    /**
     * @param array $attributes
     * @param $customerId
     */
    public function __construct( array $attributes = [], $customerId = null)
    {
        parent::__construct($attributes);
        $this->setTable( 'transactions_' . $this->getCustomerId($customerId));
    }

    /**
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($account) {
            if ($customerId = $this->getCustomerId()) {
                $account->setTable('transactions_' . $customerId);
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
            $this->setTable('transactions_' . $this->getCustomerId());
        }

        return parent::getTable();
    }
}
