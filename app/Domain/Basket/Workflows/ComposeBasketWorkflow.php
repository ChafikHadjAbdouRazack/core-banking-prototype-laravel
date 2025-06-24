<?php

namespace App\Domain\Basket\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class ComposeBasketWorkflow extends Workflow
{
    public function execute(array $input): \Generator
    {
        try {
            $result = yield ActivityStub::make(
                'App\Domain\Basket\Activities\ComposeBasketActivity',
                $input
            );
            
            // Add compensation to decompose the basket if composition fails later
            $this->addCompensation(fn() => ActivityStub::make(
                'App\Domain\Basket\Activities\DecomposeBasketActivity',
                [
                    'account_uuid' => $input['account_uuid'],
                    'basket_code' => $input['basket_code'],
                    'amount' => $input['amount'], // Same amount that was composed
                ]
            ));
            
            return $result;
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }

}