<?php

declare(strict_types=1);

namespace App\Domain\Account\Projectors;

use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Events\MoneyTransferred;
use App\Domain\Asset\Events\AssetBalanceAdded;
use App\Domain\Asset\Events\AssetBalanceSubtracted;
use App\Domain\Asset\Events\AssetTransactionCreated;
use App\Domain\Asset\Events\AssetTransferCompleted;
use App\Domain\Asset\Events\AssetTransferFailed;
use App\Domain\Asset\Events\AssetTransferInitiated;
use App\Models\TransactionReadModel;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class TransactionProjector extends Projector
{
    /**
     * Handle money added event (legacy USD transactions)
     */
    public function onMoneyAdded(MoneyAdded $event): void
    {
        TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->aggregateRootUuid(),
            'type' => TransactionReadModel::TYPE_DEPOSIT,
            'amount' => $event->money->getAmount(),
            'asset_code' => 'USD',
            'description' => $event->metadata['description'] ?? 'Deposit',
            'initiated_by' => $event->metadata['initiated_by'] ?? null,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => $event->metadata ?? [],
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Handle money subtracted event (legacy USD transactions)
     */
    public function onMoneySubtracted(MoneySubtracted $event): void
    {
        TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->aggregateRootUuid(),
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
            'amount' => $event->money->getAmount(),
            'asset_code' => 'USD',
            'description' => $event->metadata['description'] ?? 'Withdrawal',
            'initiated_by' => $event->metadata['initiated_by'] ?? null,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => $event->metadata ?? [],
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Handle money transferred event (legacy USD transfers)
     */
    public function onMoneyTransferred(MoneyTransferred $event): void
    {
        $transferUuid = Str::uuid()->toString();
        $initiatedBy = $event->metadata['initiated_by'] ?? null;
        $description = $event->metadata['description'] ?? 'Transfer';

        // Create outgoing transaction
        $outgoingTransaction = TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->from,
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
            'amount' => $event->money->getAmount(),
            'asset_code' => 'USD',
            'description' => $description . ' to ' . $event->to,
            'related_transaction_uuid' => null, // Will be updated
            'initiated_by' => $initiatedBy,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => array_merge($event->metadata ?? [], [
                'transfer_uuid' => $transferUuid,
                'to_account' => $event->to,
            ]),
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);

        // Create incoming transaction
        $incomingTransaction = TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->to,
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
            'amount' => $event->money->getAmount(),
            'asset_code' => 'USD',
            'description' => $description . ' from ' . $event->from,
            'related_transaction_uuid' => $outgoingTransaction->uuid,
            'initiated_by' => $initiatedBy,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => array_merge($event->metadata ?? [], [
                'transfer_uuid' => $transferUuid,
                'from_account' => $event->from,
            ]),
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);

        // Update outgoing transaction with related transaction UUID
        $outgoingTransaction->update(['related_transaction_uuid' => $incomingTransaction->uuid]);
    }

    /**
     * Handle asset balance added event (multi-asset deposits)
     */
    public function onAssetBalanceAdded(AssetBalanceAdded $event): void
    {
        TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->aggregateRootUuid(),
            'type' => TransactionReadModel::TYPE_DEPOSIT,
            'amount' => $event->amount,
            'asset_code' => $event->asset_code,
            'description' => $event->metadata['description'] ?? 'Asset Deposit',
            'initiated_by' => $event->metadata['initiated_by'] ?? null,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => $event->metadata ?? [],
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Handle asset balance subtracted event (multi-asset withdrawals)
     */
    public function onAssetBalanceSubtracted(AssetBalanceSubtracted $event): void
    {
        TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->aggregateRootUuid(),
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
            'amount' => $event->amount,
            'asset_code' => $event->asset_code,
            'description' => $event->metadata['description'] ?? 'Asset Withdrawal',
            'initiated_by' => $event->metadata['initiated_by'] ?? null,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => $event->metadata ?? [],
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Handle asset transaction created event
     */
    public function onAssetTransactionCreated(AssetTransactionCreated $event): void
    {
        $type = match ($event->transaction_type) {
            'deposit' => TransactionReadModel::TYPE_DEPOSIT,
            'withdrawal' => TransactionReadModel::TYPE_WITHDRAWAL,
            default => $event->transaction_type,
        };

        TransactionReadModel::create([
            'uuid' => $event->transaction_uuid,
            'account_uuid' => $event->account_uuid,
            'type' => $type,
            'amount' => $event->amount,
            'asset_code' => $event->asset_code,
            'description' => $event->metadata['description'] ?? ucfirst($type),
            'initiated_by' => $event->metadata['initiated_by'] ?? null,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => $event->metadata ?? [],
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Handle asset transfer initiated event
     */
    public function onAssetTransferInitiated(AssetTransferInitiated $event): void
    {
        $metadata = $event->metadata ?? [];
        
        // Create pending outgoing transaction
        TransactionReadModel::create([
            'uuid' => Str::uuid()->toString(),
            'account_uuid' => $event->from_account_uuid,
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
            'amount' => $event->amount,
            'asset_code' => $event->from_asset_code,
            'exchange_rate' => $metadata['exchange_rate'] ?? null,
            'reference_currency' => $event->to_asset_code !== $event->from_asset_code ? $event->to_asset_code : null,
            'reference_amount' => $metadata['converted_amount'] ?? null,
            'description' => $metadata['description'] ?? 'Transfer initiated',
            'initiated_by' => $metadata['initiated_by'] ?? null,
            'status' => TransactionReadModel::STATUS_PENDING,
            'metadata' => array_merge($metadata, [
                'transfer_uuid' => $event->transfer_uuid,
                'to_account' => $event->to_account_uuid,
                'to_asset' => $event->to_asset_code,
            ]),
            'hash' => $event->hash->getHash(),
            'processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Handle asset transfer completed event
     */
    public function onAssetTransferCompleted(AssetTransferCompleted $event): void
    {
        $metadata = $event->metadata ?? [];
        $transferUuid = $event->transfer_uuid;

        // Update outgoing transaction to completed
        TransactionReadModel::where('metadata->transfer_uuid', $transferUuid)
            ->where('type', TransactionReadModel::TYPE_TRANSFER_OUT)
            ->update(['status' => TransactionReadModel::STATUS_COMPLETED]);

        // Get the outgoing transaction to link with incoming
        $outgoingTransaction = TransactionReadModel::where('metadata->transfer_uuid', $transferUuid)
            ->where('type', TransactionReadModel::TYPE_TRANSFER_OUT)
            ->first();

        // Create incoming transaction
        if ($outgoingTransaction) {
            $incomingTransaction = TransactionReadModel::create([
                'uuid' => Str::uuid()->toString(),
                'account_uuid' => $event->to_account_uuid,
                'type' => TransactionReadModel::TYPE_TRANSFER_IN,
                'amount' => $event->amount,
                'asset_code' => $event->to_asset_code,
                'exchange_rate' => $metadata['exchange_rate'] ?? null,
                'reference_currency' => $event->from_asset_code !== $event->to_asset_code ? $event->from_asset_code : null,
                'reference_amount' => $metadata['original_amount'] ?? null,
                'description' => $metadata['description'] ?? 'Transfer received',
                'related_transaction_uuid' => $outgoingTransaction->uuid,
                'initiated_by' => $outgoingTransaction->initiated_by,
                'status' => TransactionReadModel::STATUS_COMPLETED,
                'metadata' => array_merge($metadata, [
                    'transfer_uuid' => $transferUuid,
                    'from_account' => $event->from_account_uuid,
                    'from_asset' => $event->from_asset_code,
                ]),
                'hash' => $event->hash->getHash(),
                'processed_at' => Carbon::now(),
            ]);

            // Update outgoing transaction with related transaction UUID
            $outgoingTransaction->update(['related_transaction_uuid' => $incomingTransaction->uuid]);
        }
    }

    /**
     * Handle asset transfer failed event
     */
    public function onAssetTransferFailed(AssetTransferFailed $event): void
    {
        // Update pending transaction to failed
        TransactionReadModel::where('metadata->transfer_uuid', $event->transfer_uuid)
            ->where('status', TransactionReadModel::STATUS_PENDING)
            ->update([
                'status' => TransactionReadModel::STATUS_FAILED,
                'metadata' => array_merge(
                    ['failure_reason' => $event->reason],
                    TransactionReadModel::where('metadata->transfer_uuid', $event->transfer_uuid)->first()?->metadata ?? []
                ),
            ]);
    }
}