<?php

namespace App\Domain\Basket\Activities;

use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\Activity;

class DecomposeBasketActivity extends Activity
{
    public function __construct(
        private DecomposeBasketBusinessActivity $businessActivity
    ) {
    }

    /**
     * Execute basket decomposition activity using proper domain pattern.
     *
     * @param  AccountUuid $accountUuid
     * @param  string      $basketCode
     * @param  int         $amount
     * @return array
     */
    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): array
    {
        return $this->businessActivity->execute($accountUuid, $basketCode, $amount);
    }
}
