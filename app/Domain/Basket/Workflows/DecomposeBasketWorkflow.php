<?php

namespace App\Domain\Basket\Workflows;

use Workflow\ActivityStub;
use Workflow\Workflow;

class DecomposeBasketWorkflow extends Workflow
{
    public function execute(array $input): \Generator
    {
        try {
            $result = yield ActivityStub::make(
                'App\Domain\Basket\Activities\DecomposeBasketActivity',
                $input
            );
            
            // Add compensation to recompose the basket if decomposition needs to be rolled back
            $this->addCompensation(fn() => ActivityStub::make(
                'App\Domain\Basket\Activities\ComposeBasketActivity',
                [
                    'account_uuid' => $input['account_uuid'],
                    'basket_code' => $input['basket_code'],
                    'amount' => $input['amount'], // Same amount that was decomposed
                ]
            ));
            
            return $result;
        } catch (\Throwable $th) {
            yield from $this->compensate();
            throw $th;
        }
    }

}