<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustodianAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'account_uuid',
        'custodian_name',
        'custodian_account_id',
        'custodian_account_name',
        'status',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'active',
        'is_primary' => false,
        'metadata' => '{}',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustodianAccount $custodianAccount) {
            if (empty($custodianAccount->uuid)) {
                $custodianAccount->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the internal account associated with this custodian account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_uuid', 'uuid');
    }

    /**
     * Check if this custodian account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if this custodian account is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if this custodian account is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Activate this custodian account
     */
    public function activate(): self
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    /**
     * Suspend this custodian account
     */
    public function suspend(): self
    {
        $this->update(['status' => 'suspended']);
        return $this;
    }

    /**
     * Close this custodian account
     */
    public function close(): self
    {
        $this->update(['status' => 'closed']);
        return $this;
    }

    /**
     * Set as primary custodian account for the internal account
     */
    public function setAsPrimary(): self
    {
        // First, unset any other primary accounts for this internal account
        static::where('account_uuid', $this->account_uuid)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        // Then set this one as primary
        $this->update(['is_primary' => true]);
        
        return $this;
    }

    /**
     * Get metadata value by key
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set metadata value by key
     */
    public function setMetadata(string $key, $value): self
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->update(['metadata' => $metadata]);
        
        return $this;
    }
}