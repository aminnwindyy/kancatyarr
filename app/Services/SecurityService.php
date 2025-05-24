<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    const MAX_ATTEMPTS = 5;             // حداکثر تعداد تلاش‌های ناموفق
    const LOCKOUT_MINUTES = 15;         // مدت زمان قفل شدن حساب (دقیقه)

    /**
     * بررسی می‌کند آیا حساب کاربری قفل شده است یا خیر
     *
     * @param string $identifier شناسه کاربر (ایمیل یا نام کاربری)
     * @return bool
     */
    public function isAccountLocked(string $identifier): bool
    {
        return Cache::has('login_lockout_' . md5($identifier));
    }

    /**
     * مدت زمان باقی‌مانده تا باز شدن قفل حساب (به ثانیه)
     *
     * @param string $identifier شناسه کاربر
     * @return int
     */
    public function getLockoutRemainingSeconds(string $identifier): int
    {
        return Cache::get('login_lockout_' . md5($identifier), 0);
    }

    /**
     * ثبت یک تلاش ناموفق برای ورود
     *
     * @param string $identifier شناسه کاربر
     * @return int تعداد تلاش‌های ناموفق
     */
    public function recordFailedAttempt(string $identifier): int
    {
        $cacheKey = 'login_attempts_' . md5($identifier);
        $attempts = Cache::get($cacheKey, 0) + 1;

        // تعیین مدت زمان نگهداری در کش (یک ساعت)
        $expiresAt = now()->addHour();
        Cache::put($cacheKey, $attempts, $expiresAt);

        // اگر به حداکثر تعداد تلاش رسیدیم، حساب را قفل کن
        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->lockAccount($identifier);
        }

        return $attempts;
    }

    /**
     * قفل کردن حساب کاربری
     *
     * @param string $identifier شناسه کاربر
     */
    public function lockAccount(string $identifier): void
    {
        $lockoutSeconds = self::LOCKOUT_MINUTES * 60;
        $cacheKey = 'login_lockout_' . md5($identifier);

        Cache::put($cacheKey, $lockoutSeconds, now()->addMinutes(self::LOCKOUT_MINUTES));
        Cache::forget('login_attempts_' . md5($identifier));

        Log::warning("Account locked due to too many failed attempts: {$identifier}");
    }

    /**
     * پاک کردن تعداد تلاش‌های ناموفق پس از ورود موفق
     *
     * @param string $identifier شناسه کاربر
     */
    public function clearFailedAttempts(string $identifier): void
    {
        Cache::forget('login_attempts_' . md5($identifier));
    }

    /**
     * باز کردن قفل حساب کاربری
     *
     * @param string $identifier شناسه کاربر
     */
    public function unlockAccount(string $identifier): void
    {
        Cache::forget('login_lockout_' . md5($identifier));
        Cache::forget('login_attempts_' . md5($identifier));
    }
}
