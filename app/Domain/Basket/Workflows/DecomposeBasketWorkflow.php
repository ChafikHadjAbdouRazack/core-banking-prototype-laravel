<?php

namespace App\Domain\Basket\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Basket\Activities\ComposeBasketActivity;
use App\Domain\Basket\Activities\DecomposeBasketActivity;
use Workflow\ActivityStub;
use Workflow\Workflow;

class DecomposeBasketWorkflow extends Workflow
{
    /**
     * Execute basket decomposition workflow.
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
                DecomposeBasketActivity::class,
                $accountUuid,
                $basketCode,
                $amount
            );
            
            // Add compensation to recompose the basket if decomposition needs to be rolled back
            $this->addCompensation(fn() => ActivityStub::make(
                ComposeBasketActivity::class,
                $accountUuid,
                $basketCode,
                $amount // Same amount that was decomposed
            ));
            
            return $result;
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }

}