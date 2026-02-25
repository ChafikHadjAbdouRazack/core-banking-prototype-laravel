<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment receipt for completed transactions.
 *
 * @property string $id
 * @property string $public_id
 * @property string|null $payment_intent_id
 * @property int $user_id
 * @property string $merchant_name
 * @property string $amount
 * @property string $asset
 * @property string $network
 * @property string|null $tx_hash
 * @property string $network_fee
 * @property string|null $pdf_path
 * @property string $share_token
 * @property \Carbon\Carbon $transaction_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PaymentReceipt extends Model
{
    use HasUuids;

    protected $table = 'payment_receipts';

    protected $fillable = [
        'public_id',
        'payment_intent_id',
        'user_id',
        'merchant_name',
        'amount',
        'asset',
        'network',
        'tx_hash',
        'network_fee',
        'pdf_path',
        'share_token',
        'transaction_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'user_id',
        'payment_intent_id',
        'share_token',
        'pdf_path',
    ];

    protected $casts = [
        'transaction_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PaymentIntent, $this>
     */
    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function getShareUrl(): string
    {
        return config('app.url') . '/receipt/' . $this->public_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'receiptId'    => $this->public_id,
            'merchantName' => $this->merchant_name,
            'amount'       => $this->amount,
            'asset'        => $this->asset,
            'dateTime'     => $this->transaction_at->toIso8601String(),
            'networkFee'   => $this->network_fee,
            'sharePayload' => $this->getShareUrl(),
            'pdfUrl'       => $this->pdf_path,
        ];
    }
}
