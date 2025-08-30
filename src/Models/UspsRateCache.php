<?php

namespace UspsShipping\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class UspsRateCache extends Model
{
    protected $table = 'usps_rate_cache';

    protected $fillable = [
        'cache_key',
        'request_params',
        'rates_data',
        'expires_at'
    ];

    protected $casts = [
        'request_params' => 'json',
        'rates_data' => 'json',
        'expires_at' => 'datetime'
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function getCachedRates(string $cacheKey): ?array
    {
        $cache = static::where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        return $cache?->rates_data;
    }

    public static function cacheRates(string $cacheKey, array $params, array $rates, int $ttl = 3600): void
    {
        static::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'request_params' => $params,
                'rates_data' => $rates,
                'expires_at' => now()->addSeconds($ttl)
            ]
        );
    }

    public static function clearExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
