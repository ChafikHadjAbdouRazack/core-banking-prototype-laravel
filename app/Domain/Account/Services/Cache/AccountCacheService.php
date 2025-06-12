<?php

declare(strict_types=1);

namespace App\Domain\Account\Services\Cache;

use App\Models\Account;
use Illuminate\Support\Facades\Cache;

class AccountCacheService
{
    /**
     * Cache key prefix for accounts
     */
    private const CACHE_PREFIX = 'account:';

    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get account from cache or database
     */
    public function get(string $uuid): ?Account
    {
        return Cache::remember(
            $this->getCacheKey($uuid),
            self::CACHE_TTL,
            fn() => Account::where('uuid', $uuid)->first()
        );
    }

    /**
     * Update account in cache
     */
    public function put(Account $account): void
    {
        Cache::put(
            $this->getCacheKey($account->uuid),
            $account,
            self::CACHE_TTL
        );
    }

    /**
     * Remove account from cache
     */
    public function forget(string $uuid): void
    {
        Cache::forget($this->getCacheKey($uuid));
    }

    /**
     * Clear all account cache entries
     */
    public function flush(): void
    {
        // In production, you might want to use tags for better cache management
        // For now, we'll clear individual keys
        Cache::flush();
    }

    /**
     * Get balance from cache with shorter TTL for more frequent updates
     */
    public function getBalance(string $uuid): ?int
    {
        $key = $this->getCacheKey($uuid) . ':balance';
        
        return Cache::remember(
            $key,
            300, // 5 minutes for balance
            function () use ($uuid) {
                $account = Account::where('uuid', $uuid)->first();
                return $account ? $account->balance : null;
            }
        );
    }

    /**
     * Update balance in cache
     */
    public function updateBalance(string $uuid, int $balance): void
    {
        $key = $this->getCacheKey($uuid) . ':balance';
        Cache::put($key, $balance, 300);
        
        // Also invalidate the full account cache to ensure consistency
        $this->forget($uuid);
    }

    /**
     * Generate cache key for account
     */
    private function getCacheKey(string $uuid): string
    {
        return self::CACHE_PREFIX . $uuid;
    }
}