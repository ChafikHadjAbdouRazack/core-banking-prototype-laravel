<?php

namespace App\Domain\Account\Snapshots;

use App\Traits\HasDynamicClientTable;
use Spatie\EventSourcing\Snapshots\EloquentSnapshot;

class AccountSnapshot extends EloquentSnapshot
{
    use HasDynamicClientTable;

    public function getTable(): string
    {
        return 'snapshots_' . $this->getCustomerId();
    }
}
