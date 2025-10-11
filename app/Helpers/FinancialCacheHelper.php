<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Helper کلاس برای مدیریت کش‌های مالی
 * 
 * این کلاس از prefix pattern استفاده می‌کند که با تمام cache driver‌ها سازگار است
 * TTL پیش‌فرض: 2-3 دقیقه برای داده‌های حساس مالی
 * 
 * @example
 * $cacheHelper = new FinancialCacheHelper();
 * $data = $cacheHelper->remember('total_credit', function() {
 *     return FundingTransaction::sum('amount');
 * });
 */
class FinancialCacheHelper
{
    /**
     * پیشوند اصلی برای تمام کش‌های مالی
     */
    public const CACHE_PREFIX = 'financial_report';

    /**
     * مدت زمان کش به ثانیه (3 دقیقه)
     */
    public const CACHE_TTL = 180;

    /**
     * مدت زمان کش کوتاه برای داده‌های حساس‌تر (2 دقیقه)
     */
    public const CACHE_TTL_SHORT = 120;

    /**
     * ترکیب prefix با key
     * 
     * @param string $key
     * @return string
     * @example getCacheKey('total_credit') returns 'financial_report:total_credit'
     */
    public function getCacheKey(string $key): string
    {
        // Sanitize کردن key برای جلوگیری از cache poisoning
        if (!preg_match('/^[a-zA-Z0-9_\-:]+$/', $key)) {
            throw new \InvalidArgumentException("Invalid cache key format: {$key}");
        }

        return self::CACHE_PREFIX . ':' . $key;
    }

    /**
     * ذخیره یا دریافت داده از کش
     * 
     * @param string $key کلید کش
     * @param \Closure $callback تابع برای دریافت داده در صورت cache miss
     * @param int|null $ttl مدت زمان کش (ثانیه)
     * @return mixed
     */
    public function remember(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? self::CACHE_TTL;

        $startTime = microtime(true);
        
        $result = Cache::remember($cacheKey, $ttl, function() use ($callback, $key, $startTime) {
            $data = $callback();
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::debug('💾 Financial cache MISS', [
                'key' => $key,
                'ttl_seconds' => $this->getTTL(),
                'query_duration_ms' => $duration
            ]);
            
            return $data;
        });

        // اگر از کش آمده، duration کمتری دارد
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        if ($duration < 10) { // کمتر از 10ms یعنی از کش آمده
            Log::debug('⚡ Financial cache HIT', [
                'key' => $key,
                'duration_ms' => $duration
            ]);
        }

        return $result;
    }

    /**
     * پاک کردن یک کلید خاص
     * 
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $result = Cache::forget($cacheKey);

        Log::info('🗑️ Financial cache key forgotten', [
            'key' => $key,
            'cache_key' => $cacheKey,
            'success' => $result
        ]);

        return $result;
    }

    /**
     * پاک کردن تمام کش‌های مالی با prefix
     * 
     * @return bool
     */
    public function flush(): bool
    {
        $driver = config('cache.default');
        $prefix = self::CACHE_PREFIX . ':';
        $keysCleared = 0;

        try {
            if ($this->isTagsSupported()) {
                // برای Redis/Memcached - استفاده از tags
                Cache::tags([self::CACHE_PREFIX])->flush();
                $keysCleared = 'unknown'; // tags flush تعداد دقیق برنمی‌گرداند
            } else {
                // برای database driver - استفاده از wildcard pattern
                if ($driver === 'database') {
                    $tableName = config('cache.stores.database.table', 'cache');
                    $keysCleared = DB::table($tableName)
                        ->where('key', 'LIKE', $prefix . '%')
                        ->count();
                    
                    DB::table($tableName)
                        ->where('key', 'LIKE', $prefix . '%')
                        ->delete();
                } else {
                    // برای سایر driver‌ها - فعلاً پشتیبانی نمی‌شود
                    Log::warning('⚠️ Cache flush not fully supported for driver: ' . $driver);
                    return false;
                }
            }

            Log::info('🧹 Financial cache flushed', [
                'driver' => $driver,
                'keys_cleared' => $keysCleared,
                'prefix' => $prefix,
                'method' => $this->isTagsSupported() ? 'tags' : 'wildcard'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('❌ Failed to flush financial cache', [
                'error' => $e->getMessage(),
                'driver' => $driver
            ]);
            return false;
        }
    }

    /**
     * بازگشت TTL مناسب بر اساس نوع داده
     * 
     * @param string $type
     * @return int
     */
    public function getTTL(string $type = 'default'): int
    {
        return match($type) {
            'short' => self::CACHE_TTL_SHORT,
            'summary' => self::CACHE_TTL,
            'default' => self::CACHE_TTL,
            default => self::CACHE_TTL
        };
    }

    /**
     * بررسی اینکه driver فعلی از tags پشتیبانی می‌کند یا نه
     * 
     * @return bool
     */
    public function isTagsSupported(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    /**
     * دریافت لیست تمام کلیدهای کش مالی
     * 
     * @return array
     */
    public function getAllKeys(): array
    {
        $driver = config('cache.default');
        $prefix = self::CACHE_PREFIX . ':';

        try {
            if ($driver === 'database') {
                $tableName = config('cache.stores.database.table', 'cache');
                return DB::table($tableName)
                    ->where('key', 'LIKE', $prefix . '%')
                    ->pluck('key')
                    ->toArray();
            } else {
                // برای سایر driver‌ها محدودیت دارد
                Log::warning('⚠️ getAllKeys not supported for driver: ' . $driver);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to get cache keys', [
                'error' => $e->getMessage(),
                'driver' => $driver
            ]);
            return [];
        }
    }

    /**
     * دریافت آمار کش (تعداد کلیدها، حجم تقریبی)
     * 
     * @return array
     */
    public function getStats(): array
    {
        $keys = $this->getAllKeys();
        $driver = config('cache.default');
        
        return [
            'driver' => $driver,
            'total_keys' => count($keys),
            'prefix' => self::CACHE_PREFIX,
            'ttl_default' => self::CACHE_TTL,
            'ttl_short' => self::CACHE_TTL_SHORT,
            'tags_supported' => $this->isTagsSupported(),
            'keys' => $keys
        ];
    }
}