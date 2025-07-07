<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'source',
        'status',
        'preferences',
        'tags',
        'ip_address',
        'user_agent',
        'confirmed_at',
        'unsubscribed_at',
        'unsubscribe_reason',
    ];

    protected $casts = [
        'preferences'     => 'array',
        'tags'            => 'array',
        'confirmed_at'    => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const STATUS_BOUNCED = 'bounced';

    public const SOURCE_BLOG = 'blog';

    public const SOURCE_CGO = 'cgo';

    public const SOURCE_INVESTMENT = 'investment';

    public const SOURCE_FOOTER = 'footer';

    public const SOURCE_CONTACT = 'contact';

    public const SOURCE_PARTNER = 'partner';

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function unsubscribe($reason = null): void
    {
        $this->update([
            'status'             => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at'    => now(),
            'unsubscribe_reason' => $reason,
        ]);
    }

    public function addTags(array $tags): void
    {
        $currentTags = $this->tags ?? [];
        $this->update([
            'tags' => array_unique(array_merge($currentTags, $tags)),
        ]);
    }

    public function removeTags(array $tags): void
    {
        $currentTags = $this->tags ?? [];
        $this->update([
            'tags' => array_values(array_diff($currentTags, $tags)),
        ]);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }
}
