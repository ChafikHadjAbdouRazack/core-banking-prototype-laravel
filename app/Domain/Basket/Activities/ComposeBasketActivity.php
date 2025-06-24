<?php

namespace App\Domain\Basket\Activities;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Basket\Services\BasketAccountService;
use App\Models\Account;
use Workflow\Activity;

class ComposeBasketActivity extends Activity
{
    public function __construct(
        private BasketAccountService $basketAccountService
    ) {}

    /**
     * Execute basket composition activity.
     * 
     * @param array $input Expected format: [
     *   'account_uuid' => string,
     *   'basket_code' => string,
     *   'amount' => int
     * ]
     * @return array
     */
    public function execute(array $input): array
    {
        $accountUuid = $input['account_uuid'] ?? null;
        $basketCode = $input['basket_code'] ?? null;
        $amount = $input['amount'] ?? null;

        if (!$accountUuid || !$basketCode || !$amount) {
            throw new \InvalidArgumentException('Missing required parameters: account_uuid, basket_code, amount');
        }

        $account = Account::where('uuid', $accountUuid)->firstOrFail();

        return $this->basketAccountService->composeBasket($account, $basketCode, $amount);
    }
}