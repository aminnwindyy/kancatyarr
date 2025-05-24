<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\LoginHistory;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // دریافت کاربری که وارد شده است
        $user = $event->user;

        // گرفتن اطلاعات درخواست و آی‌پی کاربر
        $ip = Request::ip();
        $userAgent = Request::userAgent();

        // استخراج اطلاعات دستگاه با استفاده از Agent
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        // تعیین نوع دستگاه
        $deviceType = 'unknown';
        if ($agent->isDesktop()) {
            $deviceType = 'desktop';
        } elseif ($agent->isPhone()) {
            $deviceType = 'mobile';
        } elseif ($agent->isTablet()) {
            $deviceType = 'tablet';
        }

        // ایجاد رکورد جدید در جدول login_histories
        LoginHistory::create([
            'user_id' => $user->user_id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'browser_name' => $agent->browser(),
            'platform_name' => $agent->platform(),
            'login_at' => now()
        ]);
    }
}
