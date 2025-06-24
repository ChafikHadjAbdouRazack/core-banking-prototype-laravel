<?php

namespace App\Domain\Basket\Workflows;

use App\Domain\Account\ValueObjects\AccountUuid;
use App\Domain\Basket\Activities\ComposeBasketActivity;
use App\Domain\Basket\Activities\DecomposeBasketActivity;
use Workflow\ActivityStub;
use Workflow\Workflow;

class DecomposeBasketWorkflow extends Workflow
{
    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): \Generator
    {
        try {
            $result = yield ActivityStub::make(
                DecomposeBasketActivity::class,
                $accountUuid,
                $basketCode,
                $amount
            );
            
            $this->addCompensation(fn() => ActivityStub::make(
                ComposeBasketActivity::class,
                $accountUuid,
                $basketCode,
                $amount
            ));
            
            return $result;
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }
}