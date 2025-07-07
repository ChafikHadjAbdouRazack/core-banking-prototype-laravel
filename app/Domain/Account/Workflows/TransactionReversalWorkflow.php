<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use Workflow\ActivityStub;
use Workflow\Workflow;

class TransactionReversalWorkflow extends Workflow
{
    /**
     * Reverse a transaction with proper audit trail
     *
     * @param AccountUuid $accountUuid
     * @param Money $originalAmount
     * @param string $transactionType - 'debit' or 'credit'
     * @param string $reversalReason
     * @param string|null $authorizedBy
     *
     * @return \Generator
     */
    public function execute(
        AccountUuid $accountUuid,
        Money $originalAmount,
        string $transactionType,
        string $reversalReason,
        ?string $authorizedBy = null
    ): \Generator {
        try {
            $result = yield ActivityStub::make(
                TransactionReversalActivity::class,
                $accountUuid,
                $originalAmount,
                $transactionType,
                $reversalReason,
                $authorizedBy
            );

            return $result;
        } catch (\Throwable $th) {
            // Log reversal failure for audit
            logger()->error('Transaction reversal failed', [
                'account_uuid' => $accountUuid->getUuid(),
                'amount' => $originalAmount->getAmount(),
                'type' => $transactionType,
                'reason' => $reversalReason,
                'error' => $th->getMessage(),
            ]);

            throw $th;
        }
    }
}
