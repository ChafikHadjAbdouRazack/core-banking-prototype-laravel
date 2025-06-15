<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Asset\Models\Asset;

class Account extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'frozen' => 'boolean',
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['balance'];
    
    /**
     * Get the balance attribute (USD balance for backward compatibility)
     *
     * @return int
     */
    public function getBalanceAttribute(): int
    {
        return $this->getBalance('USD');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: User::class,
            foreignKey: 'user_uuid',
            ownerKey: 'uuid'
        );
    }

    /**
     * Get all balances for this account
     */
    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'account_uuid', 'uuid');
    }
    
    /**
     * Get balance for a specific asset
     *
     * @param string $assetCode
     * @return AccountBalance|null
     */
    public function getBalanceForAsset(string $assetCode): ?AccountBalance
    {
        return $this->balances()->where('asset_code', $assetCode)->first();
    }
    
    /**
     * Get balance amount for a specific asset
     *
     * @param string $assetCode
     * @return int
     */
    public function getBalance(string $assetCode = 'USD'): int
    {
        $balance = $this->getBalanceForAsset($assetCode);
        return $balance ? $balance->balance : 0;
    }
    
    /**
     * Add balance for a specific asset
     *
     * @param string $assetCode
     * @param int $amount
     * @return AccountBalance
     */
    public function addBalance(string $assetCode, int $amount): AccountBalance
    {
        $balance = $this->balances()->firstOrCreate(
            ['asset_code' => $assetCode],
            ['balance' => 0]
        );
        
        $balance->credit($amount);
        return $balance;
    }
    
    /**
     * Subtract balance for a specific asset
     *
     * @param string $assetCode
     * @param int $amount
     * @return AccountBalance
     * @throws \Exception if insufficient balance
     */
    public function subtractBalance(string $assetCode, int $amount): AccountBalance
    {
        $balance = $this->getBalanceForAsset($assetCode);
        
        if (!$balance || !$balance->hasSufficientBalance($amount)) {
            throw new \Exception("Insufficient {$assetCode} balance");
        }
        
        $balance->debit($amount);
        return $balance;
    }
    
    /**
     * Check if account has sufficient balance for asset
     *
     * @param string $assetCode
     * @param int $amount
     * @return bool
     */
    public function hasSufficientBalance(string $assetCode, int $amount): bool
    {
        $balance = $this->getBalanceForAsset($assetCode);
        return $balance && $balance->hasSufficientBalance($amount);
    }
    
    /**
     * Get all non-zero balances
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveBalances()
    {
        return $this->balances()->positive()->with('asset')->get();
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use addBalance('USD', $amount) instead
     * @param int $amount
     * @return static
     */
    public function addMoney(int $amount): static
    {
        $this->addBalance('USD', $amount);
        return $this;
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use subtractBalance('USD', $amount) instead
     * @param int $amount
     * @return static
     */
    public function subtractMoney(int $amount): static
    {
        $this->subtractBalance('USD', $amount);
        return $this;
    }
}
