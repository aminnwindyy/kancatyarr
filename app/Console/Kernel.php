<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // اجرای روزانه تابع حذف گفتگوهای قدیمی (سفارشات تکمیل‌شده بیش از 15 روز)
        $schedule->command('orders:cleanup-conversations')->daily();
        
        // پاکسازی فایل‌های پیوست قدیمی - هر هفته یکشنبه ساعت 3 صبح
        $schedule->command('orders:cleanup-attachments')->weekly()->sundays()->at('03:00');
        
        // ایجاد اسنپشات روزانه موجودی - هر روز ساعت 23:55
        $schedule->command('accounting:create-snapshot --type=daily')->dailyAt('23:55');
        
        // ایجاد اسنپشات ماهانه موجودی - اول هر ماه ساعت 00:05
        $schedule->command('accounting:create-snapshot --type=monthly')->monthlyOn(1, '00:05');
        
        // ایجاد اسنپشات سالانه موجودی - اول فروردین هر سال ساعت 00:30
        $schedule->command('accounting:create-snapshot --type=yearly')->yearlyOn(3, 21, '00:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
