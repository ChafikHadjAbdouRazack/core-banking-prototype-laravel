<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionReadModel extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'uuid',
        'account_uuid',
        'type',
        'amount',
        'asset_code',
        'exchange_rate',
        'reference_currency',
        'reference_amount',
        'description',
        'related_transaction_uuid',
        'initiated_by',
        'status',
        'metadata',
        'hash',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'reference_amount' => 'integer',
        'exchange_rate' => 'decimal:10',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Transaction types
     */
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';

    /**
     * Transaction statuses
     */
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    /**
     * Get the account that owns the transaction
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_uuid', 'uuid');
    }

    /**
     * Get the asset for this transaction
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_code', 'code');
    }

    /**
     * Get the user who initiated the transaction
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by', 'uuid');
    }

    /**
     * Get the related transaction (for transfers)
     */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_transaction_uuid', 'uuid');
    }

    /**
     * Check if transaction is a deposit
     */
    public function isDeposit(): bool
    {
        return $this->type === self::TYPE_DEPOSIT;
    }

    /**
     * Check if transaction is a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return $this->type === self::TYPE_WITHDRAWAL;
    }

    /**
     * Check if transaction is a transfer
     */
    public function isTransfer(): bool
    {
        return in_array($this->type, [self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT]);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmount(): string
    {
        $amount = $this->amount / 100;
        return number_format($amount, 2) . ' ' . $this->asset_code;
    }

    /**
     * Get transaction direction (credit/debit)
     */
    public function getDirection(): string
    {
        return in_array($this->type, [self::TYPE_DEPOSIT, self::TYPE_TRANSFER_IN]) 
            ? 'credit' 
            : 'debit';
    }

    /**
     * Get transaction sign for calculations
     */
    public function getSign(): int
    {
        return $this->getDirection() === 'credit' ? 1 : -1;
    }

    /**
     * Get signed amount (positive for credits, negative for debits)
     */
    public function getSignedAmount(): int
    {
        return $this->amount * $this->getSign();
    }

    /**
     * Scope for account transactions
     */
    public function scopeForAccount($query, string $accountUuid)
    {
        return $query->where('account_uuid', $accountUuid);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('processed_at', [$from, $to]);
    }

    /**
     * Get available transaction types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEPOSIT,
            self::TYPE_WITHDRAWAL,
            self::TYPE_TRANSFER_IN,
            self::TYPE_TRANSFER_OUT,
        ];
    }

    /**
     * Get available transaction statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_PENDING,
            self::STATUS_FAILED,
            self::STATUS_REVERSED,
        ];
    }
}