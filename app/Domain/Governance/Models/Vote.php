<?php

declare(strict_types=1);

namespace App\Domain\Governance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class Vote extends Model
{
    use HasFactory;

    /**
     * Flag to prevent auto-generation of signature during testing
     */
    public bool $skipSignatureGeneration = false;

    protected static function newFactory()
    {
        return VoteFactory::new();
    }

    protected $fillable = [
        'poll_id',
        'user_uuid',
        'selected_options',
        'voting_power',
        'voted_at',
        'signature',
        'metadata',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'voting_power' => 'integer',
        'voted_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vote) {
            if (empty($vote->voted_at)) {
                $vote->voted_at = now();
            }
        });
        
        static::created(function ($vote) {
            // Generate signature after the vote is created and has an ID
            if (!$vote->skipSignatureGeneration && empty($vote->signature)) {
                $vote->signature = $vote->generateSignature();
                $vote->saveQuietly(); // Save without triggering events
            }
        });
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    public function getSelectedOptionsAsString(): string
    {
        return implode(', ', $this->selected_options ?? []);
    }

    public function hasSelectedOption(string $optionId): bool
    {
        return in_array($optionId, $this->selected_options ?? [], true);
    }

    public function getSelectedOptionCount(): int
    {
        return count($this->selected_options ?? []);
    }

    public function generateSignature(): string
    {
        $data = [
            'poll_id' => $this->poll_id,
            'user_uuid' => $this->user_uuid,
            'selected_options' => $this->selected_options,
            'voting_power' => $this->voting_power,
            'voted_at' => $this->voted_at?->toISOString(),
        ];

        return hash_hmac('sha256', json_encode($data), config('app.key'));
    }

    public function verifySignature(): bool
    {
        if (is_null($this->signature) || empty($this->signature)) {
            return false;
        }

        $expectedSignature = $this->generateSignature();
        return hash_equals($expectedSignature, $this->signature);
    }

    public function isValid(): bool
    {
        return $this->verifySignature() 
            && !empty($this->selected_options)
            && $this->voting_power > 0;
    }

    public function scopeByUser($query, string $userUuid)
    {
        return $query->where('user_uuid', $userUuid);
    }

    public function scopeByPoll($query, int $pollId)
    {
        return $query->where('poll_id', $pollId);
    }

    public function scopeWithHighVotingPower($query, int $minimumPower = 10)
    {
        return $query->where('voting_power', '>=', $minimumPower);
    }

    public function scopeRecentVotes($query, int $hours = 24)
    {
        return $query->where('voted_at', '>=', now()->subHours($hours));
    }

    public function getVotingPowerWeight(): float
    {
        if (!$this->poll) {
            return 0.0;
        }

        $totalPower = $this->poll->getTotalVotingPower();
        
        return $totalPower > 0 ? ($this->voting_power / $totalPower) * 100 : 0.0;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'selected_options_string' => $this->getSelectedOptionsAsString(),
            'selected_option_count' => $this->getSelectedOptionCount(),
            'voting_power_weight' => $this->getVotingPowerWeight(),
            'is_valid' => $this->isValid(),
        ]);
    }
}