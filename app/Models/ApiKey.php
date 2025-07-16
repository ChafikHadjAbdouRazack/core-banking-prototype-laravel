<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Collection get(array $columns = ['*'])
 * @method static static|null find(mixed $id, array $columns = ['*'])
 * @method static static|null first(array $columns = ['*'])
 * @method static static firstOrFail(array $columns = ['*'])
 * @method static int count(string $columns = '*')
 * @method static bool exists()
 * @method static static create(array $attributes = [])
 * @method static static updateOrCreate(array $attributes, array $values = [])
 */
class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_uuid',
        'name',
        'key_prefix',
        'key_hash',
        'description',
        'permissions',
        'rate_limits',
        'allowed_ips',
        'is_active',
        'last_used_at',
        'last_used_ip',
        'request_count',
        'expires_at',
    ];

    protected $casts = [
        'permissions'   => 'array',
        'rate_limits'   => 'array',
        'allowed_ips'   => 'array',
        'is_active'     => 'boolean',
        'last_used_at'  => 'datetime',
        'expires_at'    => 'datetime',
        'request_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(
            function ($model) {
                if (empty($model->uuid)) {
                    $model->uuid = Str::uuid();
                }
            }
        );
    }

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    /**
     * Get the logs for this API key.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ApiKeyLog::class);
    }

    /**
     * Generate a new API key.
     */
    public static function generateKey(): string
    {
        return 'fak_' . Str::random(32); // FinAegis API Key prefix
    }

    /**
     * Create a new API key for a user.
     */
    public static function createForUser(User $user, array $data): array
    {
        $plainKey = self::generateKey();
        $keyPrefix = substr($plainKey, 0, 8);
        $keyHash = Hash::make($plainKey);

        $apiKey = self::create(
            [
            'user_uuid'   => $user->uuid,
            'name'        => $data['name'],
            'key_prefix'  => $keyPrefix,
            'key_hash'    => $keyHash,
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? ['read'],
            'rate_limits' => $data['rate_limits'] ?? null,
            'allowed_ips' => $data['allowed_ips'] ?? null,
            'expires_at'  => $data['expires_at'] ?? null,
            ]
        );

        return [
            'api_key'   => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Verify an API key.
     */
    public static function verify(string $plainKey): ?ApiKey
    {
        $keyPrefix = substr($plainKey, 0, 8);

        /** @var ApiKey|null $apiKey */
        $apiKey = self::where('key_prefix', $keyPrefix)
            ->where('is_active', true)
            ->where(
                function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                }
            )
            ->first();

        if ($apiKey && Hash::check($plainKey, $apiKey->key_hash)) {
            $apiKey->recordUsage(request()->ip() ?? '127.0.0.1');
            return $apiKey;
        }

        return null;
    }

    /**
     * Check if the API key has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return false;
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    /**
     * Check if the request IP is allowed.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No IP restrictions
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Get rate limit for a specific endpoint.
     */
    public function getRateLimit(string $endpoint = 'default'): ?int
    {
        if (empty($this->rate_limits)) {
            return null; // Use default rate limits
        }

        return $this->rate_limits[$endpoint] ?? $this->rate_limits['default'] ?? null;
    }

    /**
     * Record API key usage.
     */
    public function recordUsage(string $ip): void
    {
        $this->update(
            [
            'last_used_at'  => now(),
            'last_used_ip'  => $ip,
            'request_count' => $this->request_count + 1,
            ]
        );
    }

    /**
     * Check if the API key is valid.
     */
    public function isValid(): bool
    {
        return $this->is_active &&
               (! $this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Revoke the API key.
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }
}
