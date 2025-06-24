<?php

namespace App\Domain\Basket\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Basket\Activities\ComposeBasketActivity;
use App\Domain\Basket\Activities\DecomposeBasketActivity;
use Workflow\ActivityStub;
use Workflow\Workflow;

class ComposeBasketWorkflow extends Workflow
{
    /**
     * Execute basket composition workflow.
     * 
     * @param AccountUuid $accountUuid
     * @param string $basketCode
     * @param int $amount
     * @return \Generator
     */
    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): \Generator
    {
        try {
            $result = yield ActivityStub::make(
                ComposeBasketActivity::class,
                $accountUuid,
                $basketCode,
                $amount
            );
            
            // Add compensation to decompose the basket if composition fails later
            $this->addCompensation(fn() => ActivityStub::make(
                DecomposeBasketActivity::class,
                $accountUuid,
                $basketCode,
                $amount // Same amount that was composed
            ));
            
            return $result;
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }
}