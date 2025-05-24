<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class OtpRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // استخراج شماره موبایل از درخواست
        $phone = $request->input('phone_number') ?? $request->input('mobile') ?? $request->ip();
        
        // ایجاد کلید منحصر به فرد برای این شماره موبایل
        $key = 'otp_' . str_replace(['+', ' '], '', $phone);

        // اعمال محدودیت نرخ: 3 درخواست در 5 دقیقه
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return response()->json([
                'status' => 'error',
                'message' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً بعد از {$minutes} دقیقه مجدداً تلاش کنید.",
                'retry_after' => $seconds,
            ], 429);
        }

        // ثبت تلاش جدید
        RateLimiter::hit($key, 300); // 300 ثانیه (5 دقیقه) تا منقضی شدن

        return $next($request);
    }
} 