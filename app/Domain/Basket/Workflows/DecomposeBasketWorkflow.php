<?php

declare(strict_types=1);

namespace App\Domain\Basket\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Basket\Services\BasketAccountService;
use App\Models\Account;
use Workflow\ActivityStub;
use Workflow\Workflow;
use Workflow\Activity;

final class DecomposeBasketWorkflow extends Workflow
{
    /**
     * Decompose a basket into its component assets.
     *
     * @param AccountUuid $accountUuid
     * @param string $basketCode
     * @param int $amount
     * @return \Generator
     */
    public function execute(
        AccountUuid $accountUuid,
        string $basketCode,
        int $amount
    ): \Generator {
        // Validate inputs
        yield ActivityStub::make(ValidateBasketDecompositionActivity::class, $accountUuid, $basketCode, $amount);

        // Perform decomposition and return result
        return yield ActivityStub::make(DecomposeBasketActivity::class, $accountUuid, $basketCode, $amount);
    }
}

/**
 * Activity to validate basket decomposition request.
 */
class ValidateBasketDecompositionActivity extends Activity
{
    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $account = Account::where('uuid', $accountUuid->__toString())->firstOrFail();
        
        if ($account->frozen) {
            throw new \Exception('Account is frozen');
        }

        // Additional validation can be added here
    }
}

/**
 * Activity to perform basket decomposition.
 */
class DecomposeBasketActivity extends Activity
{
    public function __construct(
        private readonly BasketAccountService $basketAccountService
    ) {}

    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): array
    {
        $account = Account::where('uuid', $accountUuid->__toString())->firstOrFail();
        
        return $this->basketAccountService->decomposeBasket($account, $basketCode, $amount);
    }
}