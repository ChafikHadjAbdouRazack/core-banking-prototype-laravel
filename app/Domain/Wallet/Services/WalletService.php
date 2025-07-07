<?php

namespace App\Domain\Wallet\Services;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Wallet\Contracts\WalletServiceInterface;
use App\Domain\Wallet\Workflows\WalletDepositWorkflow;
use App\Domain\Wallet\Workflows\WalletWithdrawWorkflow;
use App\Domain\Wallet\Workflows\WalletTransferWorkflow;
use App\Domain\Wallet\Workflows\WalletConvertWorkflow;
use App\Models\Account;
use Workflow\WorkflowStub;

class WalletService implements WalletServiceInterface
{
    /**
     * Deposit funds to an account for a specific asset
     */
    public function deposit(mixed $accountUuid, string $assetCode, mixed $amount): void
    {
        $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
        $workflow->start(__account_uuid($accountUuid), $assetCode, $amount);
    }

    /**
     * Withdraw funds from an account for a specific asset
     */
    public function withdraw(mixed $accountUuid, string $assetCode, mixed $amount): void
    {
        // Validate sufficient balance before starting workflow
        $accountUuidObj = __account_uuid($accountUuid);
        $account = Account::where('uuid', (string) $accountUuidObj)->first();

        if (!$account || !$account->hasSufficientBalance($assetCode, $amount)) {
            throw new \Exception('Insufficient balance');
        }

        $workflow = WorkflowStub::make(WalletWithdrawWorkflow::class);
        $workflow->start($accountUuidObj, $assetCode, $amount);
    }

    /**
     * Transfer funds between accounts for a specific asset
     */
    public function transfer(mixed $fromAccountUuid, mixed $toAccountUuid, string $assetCode, mixed $amount, ?string $reference = null): void
    {
        // Validate sufficient balance before starting workflow
        $fromAccountUuidObj = __account_uuid($fromAccountUuid);
        $fromAccount = Account::where('uuid', (string) $fromAccountUuidObj)->first();

        if (!$fromAccount || !$fromAccount->hasSufficientBalance($assetCode, $amount)) {
            throw new \Exception('Insufficient balance');
        }

        $workflow = WorkflowStub::make(WalletTransferWorkflow::class);
        $workflow->start(
            $fromAccountUuidObj,
            __account_uuid($toAccountUuid),
            $assetCode,
            $amount,
            $reference
        );
    }

    /**
     * Convert between different assets within the same account
     */
    public function convert(mixed $accountUuid, string $fromAssetCode, string $toAssetCode, mixed $amount): void
    {
        $workflow = WorkflowStub::make(WalletConvertWorkflow::class);
        $workflow->start(
            __account_uuid($accountUuid),
            $fromAssetCode,
            $toAssetCode,
            $amount
        );
    }
}
