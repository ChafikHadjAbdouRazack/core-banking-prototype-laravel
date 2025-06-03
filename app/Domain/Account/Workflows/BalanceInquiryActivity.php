<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\TransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Models\Account;
use Workflow\Activity;

class BalanceInquiryActivity extends Activity
{
    /**
     * @param AccountUuid $uuid
     * @param string|null $requestedBy
     * @param TransactionAggregate $transaction
     *
     * @return array
     */
    public function execute(
        AccountUuid $uuid, 
        ?string $requestedBy, 
        TransactionAggregate $transaction
    ): array {
        $aggregate = $transaction->retrieve($uuid->getUuid());
        
        $account = Account::where('uuid', $uuid->getUuid())->first();
        
        // Log the inquiry for audit purposes
        $this->logInquiry($uuid, $requestedBy);
        
        return [
            'account_uuid' => $uuid->getUuid(),
            'balance' => $aggregate->balance,
            'account_name' => $account?->name,
            'status' => $account?->status ?? 'unknown',
            'inquired_at' => now()->toISOString(),
            'inquired_by' => $requestedBy,
        ];
    }
    
    /**
     * @param AccountUuid $uuid
     * @param string|null $requestedBy
     * @return void
     */
    private function logInquiry(AccountUuid $uuid, ?string $requestedBy): void
    {
        // Log to audit trail (could be a separate event or database log)
        logger()->info('Balance inquiry', [
            'account_uuid' => $uuid->getUuid(),
            'requested_by' => $requestedBy,
            'timestamp' => now()->toISOString(),
        ]);
    }
}