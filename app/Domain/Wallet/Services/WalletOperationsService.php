<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Services;

use App\Domain\Account\Models\Account;
use App\Domain\Shared\Contracts\WalletOperationsInterface;
use App\Domain\Wallet\Exceptions\InsufficientBalanceException;
use App\Domain\Wallet\Exceptions\LockNotFoundException;
use App\Domain\Wallet\Exceptions\UnsupportedConversionException;
use App\Domain\Wallet\Workflows\WalletConvertWorkflow;
use App\Domain\Wallet\Workflows\WalletDepositWorkflow;
use App\Domain\Wallet\Workflows\WalletTransferWorkflow;
use App\Domain\Wallet\Workflows\WalletWithdrawWorkflow;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

/**
 * Implementation of WalletOperationsInterface for domain decoupling.
 *
 * This service bridges the shared interface with the Wallet domain
 * implementation, enabling other domains to depend on the abstraction.
 *
 * Note: In this codebase, wallets are represented by Account models.
 * This adapter translates wallet operations to account operations.
 */
class WalletOperationsService implements WalletOperationsInterface
{
    private const LOCK_PREFIX = 'wallet_lock:';

    private const LOCK_TTL = 86400; // 24 hours

    /**
     * {@inheritDoc}
     */
    public function deposit(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        $transactionId = (string) Str::uuid();

        $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
        $workflow->start(
            __account_uuid($walletId),
            $assetCode,
            $amount
        );

        return $transactionId;
    }

    /**
     * {@inheritDoc}
     */
    public function withdraw(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($assetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, $availableBalance);
        }

        $transactionId = (string) Str::uuid();

        $workflow = WorkflowStub::make(WalletWithdrawWorkflow::class);
        $workflow->start(
            __account_uuid($walletId),
            $assetCode,
            $amount
        );

        return $transactionId;
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(string $walletId, string $assetCode): string
    {
        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            return '0';
        }

        return (string) $account->getBalance($assetCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllBalances(string $walletId): array
    {
        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            return [];
        }

        $balances = [];
        foreach ($account->balances ?? [] as $balance) {
            $balances[$balance->asset_code] = (string) $balance->amount;
        }

        return $balances;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSufficientBalance(string $walletId, string $assetCode, string $amount): bool
    {
        $balance = $this->getBalance($walletId, $assetCode);

        return $this->compareAmounts($balance, $amount) >= 0;
    }

    /**
     * {@inheritDoc}
     */
    public function lockFunds(
        string $walletId,
        string $assetCode,
        string $amount,
        string $reason,
        array $metadata = []
    ): string {
        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($assetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            throw new InsufficientBalanceException($walletId, $assetCode, $amount, $availableBalance);
        }

        $lockId = 'lock_' . Str::uuid();

        // Store lock in cache (production would use database)
        Cache::put(
            self::LOCK_PREFIX . $lockId,
            [
                'wallet_id'  => $walletId,
                'asset_code' => $assetCode,
                'amount'     => $amount,
                'reason'     => $reason,
                'metadata'   => $metadata,
                'created_at' => now()->toIso8601String(),
            ],
            self::LOCK_TTL
        );

        // Deduct from available balance (via withdrawal workflow)
        $workflow = WorkflowStub::make(WalletWithdrawWorkflow::class);
        $workflow->start(
            __account_uuid($walletId),
            $assetCode,
            $amount
        );

        return $lockId;
    }

    /**
     * {@inheritDoc}
     */
    public function unlockFunds(string $lockId): bool
    {
        $lockData = Cache::get(self::LOCK_PREFIX . $lockId);

        if (! $lockData) {
            throw new LockNotFoundException($lockId);
        }

        // Credit back the locked amount
        $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
        $workflow->start(
            __account_uuid($lockData['wallet_id']),
            $lockData['asset_code'],
            $lockData['amount']
        );

        Cache::forget(self::LOCK_PREFIX . $lockId);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function executeLock(string $lockId, string $destinationWalletId, string $reference = ''): string
    {
        $lockData = Cache::get(self::LOCK_PREFIX . $lockId);

        if (! $lockData) {
            throw new LockNotFoundException($lockId);
        }

        $transactionId = (string) Str::uuid();

        // Credit the destination wallet
        $workflow = WorkflowStub::make(WalletDepositWorkflow::class);
        $workflow->start(
            __account_uuid($destinationWalletId),
            $lockData['asset_code'],
            $lockData['amount']
        );

        // Remove the lock
        Cache::forget(self::LOCK_PREFIX . $lockId);

        return $transactionId;
    }

    /**
     * {@inheritDoc}
     */
    public function transfer(
        string $fromWalletId,
        string $toWalletId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        $account = Account::where('uuid', $fromWalletId)->first();

        if (! $account) {
            throw new InsufficientBalanceException($fromWalletId, $assetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($assetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            throw new InsufficientBalanceException($fromWalletId, $assetCode, $amount, $availableBalance);
        }

        $transactionId = (string) Str::uuid();

        $workflow = WorkflowStub::make(WalletTransferWorkflow::class);
        $workflow->start(
            __account_uuid($fromWalletId),
            __account_uuid($toWalletId),
            $assetCode,
            $amount,
            $reference
        );

        return $transactionId;
    }

    /**
     * {@inheritDoc}
     */
    public function convert(
        string $walletId,
        string $fromAssetCode,
        string $toAssetCode,
        string $amount,
        ?string $exchangeRate = null
    ): array {
        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            throw new InsufficientBalanceException($walletId, $fromAssetCode, $amount, '0');
        }

        $availableBalance = (string) $account->getBalance($fromAssetCode);

        if ($this->compareAmounts($availableBalance, $amount) < 0) {
            throw new InsufficientBalanceException($walletId, $fromAssetCode, $amount, $availableBalance);
        }

        // Get exchange rate if not provided
        if ($exchangeRate === null) {
            $exchangeRate = $this->getExchangeRate($fromAssetCode, $toAssetCode);
            if ($exchangeRate === null) {
                throw new UnsupportedConversionException($fromAssetCode, $toAssetCode);
            }
        }

        $transactionId = (string) Str::uuid();
        $toAmount = $this->multiplyAmounts($amount, $exchangeRate);

        $workflow = WorkflowStub::make(WalletConvertWorkflow::class);
        $workflow->start(
            __account_uuid($walletId),
            $fromAssetCode,
            $toAssetCode,
            $amount
        );

        return [
            'transaction_id' => $transactionId,
            'from_amount'    => $amount,
            'to_amount'      => $toAmount,
            'rate_used'      => $exchangeRate,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getWallet(string $walletId): ?array
    {
        $account = Account::where('uuid', $walletId)->first();

        if (! $account) {
            return null;
        }

        return [
            'id'         => (string) $account->id,
            'uuid'       => $account->uuid,
            'owner_id'   => (string) $account->user_id,
            'owner_type' => 'user',
            'type'       => $account->type ?? 'standard',
            'status'     => $account->status ?? 'active',
            'created_at' => $account->created_at?->toIso8601String() ?? '',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function walletExists(string $walletId): bool
    {
        return Account::where('uuid', $walletId)->exists();
    }

    /**
     * Get exchange rate between two assets.
     */
    private function getExchangeRate(string $fromAssetCode, string $toAssetCode): ?string
    {
        // Use the exchange rate service if available
        try {
            $exchangeRateService = app(\App\Domain\Asset\Services\ExchangeRateService::class);

            return (string) $exchangeRateService->getRate($fromAssetCode, $toAssetCode);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Compare two amounts with bccomp.
     *
     * @param string $amount1 First amount
     * @param string $amount2 Second amount
     * @return int -1, 0, or 1 as per bccomp
     */
    private function compareAmounts(string $amount1, string $amount2): int
    {
        /** @var numeric-string $a */
        $a = $amount1;
        /** @var numeric-string $b */
        $b = $amount2;

        return bccomp($a, $b, 8);
    }

    /**
     * Multiply two amounts with bcmul.
     *
     * @param string $amount1 First amount
     * @param string $amount2 Second amount (typically exchange rate)
     * @return string Result
     */
    private function multiplyAmounts(string $amount1, string $amount2): string
    {
        /** @var numeric-string $a */
        $a = $amount1;
        /** @var numeric-string $b */
        $b = $amount2;

        return bcmul($a, $b, 8);
    }
}
