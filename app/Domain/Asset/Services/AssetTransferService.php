<?php

declare(strict_types=1);

namespace App\Domain\Asset\Services;

use App\Domain\Account\Models\Account;
use App\Domain\Asset\Aggregates\AssetTransferAggregate;
use App\Domain\Asset\Exceptions\AssetNotFoundException;
use App\Domain\Asset\Exceptions\InsufficientAssetBalanceException;
use App\Domain\Asset\Exceptions\UnsupportedAssetConversionException;
use App\Domain\Asset\Models\Asset;
use App\Domain\Shared\Contracts\AssetTransferInterface;
use Exception;
use Illuminate\Support\Str;

/**
 * Implementation of AssetTransferInterface for domain decoupling.
 *
 * This service bridges the shared interface with the Asset domain
 * implementation, enabling other domains to depend on the abstraction.
 */
class AssetTransferService implements AssetTransferInterface
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transfer(
        string $fromAccountId,
        string $toAccountId,
        string $assetCode,
        string $amount,
        string $reference = '',
        array $metadata = []
    ): string {
        $this->validateAsset($assetCode);
        $this->validateSufficientBalance($fromAccountId, $assetCode, $amount);

        $transferId = (string) Str::uuid();

        $aggregate = AssetTransferAggregate::retrieve($transferId);
        $aggregate->initiate(
            $fromAccountId,
            $toAccountId,
            $assetCode,
            $assetCode,
            $amount,
            $amount,
            '1',
            null
        );
        $aggregate->persist();

        return $transferId;
    }

    /**
     * {@inheritDoc}
     */
    public function convertAndTransfer(
        string $fromAccountId,
        string $toAccountId,
        string $fromAssetCode,
        string $toAssetCode,
        string $fromAmount,
        ?string $exchangeRate = null,
        string $reference = '',
        array $metadata = []
    ): array {
        $this->validateAsset($fromAssetCode);
        $this->validateAsset($toAssetCode);
        $this->validateSufficientBalance($fromAccountId, $fromAssetCode, $fromAmount);

        if ($exchangeRate === null) {
            $exchangeRate = $this->getExchangeRate($fromAssetCode, $toAssetCode);
            if ($exchangeRate === null) {
                throw new UnsupportedAssetConversionException($fromAssetCode, $toAssetCode);
            }
        }

        /** @var numeric-string $a */
        $a = $fromAmount;
        /** @var numeric-string $b */
        $b = $exchangeRate;
        $toAmount = bcmul($a, $b, 8);

        $transferId = (string) Str::uuid();

        $aggregate = AssetTransferAggregate::retrieve($transferId);
        $aggregate->initiate(
            $fromAccountId,
            $toAccountId,
            $fromAssetCode,
            $toAssetCode,
            $fromAmount,
            $toAmount,
            $exchangeRate,
            null
        );
        $aggregate->persist();

        return [
            'transfer_id' => $transferId,
            'from_amount' => $fromAmount,
            'to_amount'   => $toAmount,
            'rate_used'   => $exchangeRate,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAssetDetails(string $assetCode): ?array
    {
        $asset = Asset::where('code', $assetCode)->first();

        if (! $asset) {
            return null;
        }

        return [
            'code'      => $asset->code,
            'name'      => $asset->name ?? $asset->code,
            'type'      => $asset->type ?? 'fiat',
            'symbol'    => $asset->symbol ?? $asset->code,
            'decimals'  => $asset->decimals ?? 2,
            'is_active' => $asset->is_active ?? true,
            'metadata'  => $asset->metadata ?? [],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableAssets(?string $type = null, bool $activeOnly = true): array
    {
        $query = Asset::query();

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get()->map(fn ($asset) => [
            'code'      => $asset->code,
            'name'      => $asset->name ?? $asset->code,
            'type'      => $asset->type ?? 'fiat',
            'symbol'    => $asset->symbol ?? $asset->code,
            'is_active' => $asset->is_active ?? true,
        ])->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function assetExists(string $assetCode): bool
    {
        return Asset::where('code', $assetCode)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function validateOperation(
        string $assetCode,
        string $operation,
        array $context = []
    ): array {
        $errors = [];
        $warnings = [];

        if (! $this->assetExists($assetCode)) {
            $errors[] = "Asset {$assetCode} does not exist or is not active";
        }

        $validOperations = ['transfer', 'convert', 'deposit', 'withdraw'];
        if (! in_array($operation, $validOperations, true)) {
            $errors[] = "Invalid operation: {$operation}";
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getExchangeRate(string $fromAssetCode, string $toAssetCode): ?string
    {
        try {
            return (string) $this->exchangeRateService->getRate($fromAssetCode, $toAssetCode);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function calculateConversion(
        string $fromAssetCode,
        string $toAssetCode,
        string $amount
    ): ?array {
        $rate = $this->getExchangeRate($fromAssetCode, $toAssetCode);

        if ($rate === null) {
            return null;
        }

        /** @var numeric-string $a */
        $a = $amount;
        /** @var numeric-string $b */
        $b = $rate;
        $convertedAmount = bcmul($a, $b, 8);

        // Assume 0.1% fee for conversions
        /** @var numeric-string $feeRate */
        $feeRate = '0.001';
        $feeAmount = bcmul($convertedAmount, $feeRate, 8);
        $netAmount = bcsub($convertedAmount, $feeAmount, 8);

        return [
            'converted_amount' => $convertedAmount,
            'rate_used'        => $rate,
            'fee_amount'       => $feeAmount,
            'net_amount'       => $netAmount,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTransferStatus(string $transferId): ?array
    {
        try {
            $aggregate = AssetTransferAggregate::retrieve($transferId);

            return [
                'id'              => $transferId,
                'status'          => (string) ($aggregate->getStatus() ?? 'unknown'),
                'from_account_id' => (string) ($aggregate->getFromAccountUuid() ?? ''),
                'to_account_id'   => (string) ($aggregate->getToAccountUuid() ?? ''),
                'from_asset_code' => (string) ($aggregate->getFromAssetCode() ?? ''),
                'to_asset_code'   => (string) ($aggregate->getToAssetCode() ?? ''),
                'from_amount'     => (string) ($aggregate->getFromAmount() ?? '0'),
                'to_amount'       => (string) ($aggregate->getToAmount() ?? '0'),
                'exchange_rate'   => $aggregate->getExchangeRate() !== null ? (string) $aggregate->getExchangeRate() : null,
                'created_at'      => now()->toIso8601String(),
                'completed_at'    => null,
                'failure_reason'  => $aggregate->getFailureReason() !== null ? (string) $aggregate->getFailureReason() : null,
            ];
        } catch (Exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isConversionSupported(string $fromAssetCode, string $toAssetCode): bool
    {
        return $this->getExchangeRate($fromAssetCode, $toAssetCode) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function formatAmount(string $assetCode, string $amount): string
    {
        $asset = Asset::where('code', $assetCode)->first();
        $decimals = $asset->decimals ?? 2;

        return number_format((float) $amount, $decimals, '.', '');
    }

    /**
     * Validate asset exists and is active.
     */
    private function validateAsset(string $assetCode): void
    {
        if (! $this->assetExists($assetCode)) {
            throw new AssetNotFoundException($assetCode);
        }
    }

    /**
     * Validate account has sufficient balance.
     */
    private function validateSufficientBalance(string $accountId, string $assetCode, string $amount): void
    {
        $account = Account::where('uuid', $accountId)->first();

        if (! $account) {
            throw new InsufficientAssetBalanceException($accountId, $assetCode, $amount, '0');
        }

        $balance = (string) $account->getBalance($assetCode);

        /** @var numeric-string $a */
        $a = $balance;
        /** @var numeric-string $b */
        $b = $amount;

        if (bccomp($a, $b, 8) < 0) {
            throw new InsufficientAssetBalanceException($accountId, $assetCode, $amount, $balance);
        }
    }
}
