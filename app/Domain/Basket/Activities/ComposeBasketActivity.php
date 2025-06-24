<?php

namespace App\Domain\Basket\Activities;

use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\Activity;

class ComposeBasketActivity extends Activity
{
    public function __construct(
        private ComposeBasketBusinessActivity $businessActivity
    ) {}

    /**
     * Execute basket composition activity using proper domain pattern.
     * 
     * @param AccountUuid $accountUuid
     * @param string $basketCode
     * @param int $amount
     * @return array
     */
    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): array
    {
        return $this->businessActivity->execute($accountUuid, $basketCode, $amount);
    }
}