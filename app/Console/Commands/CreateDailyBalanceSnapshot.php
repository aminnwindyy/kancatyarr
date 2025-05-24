<?php

namespace App\Console\Commands;

use App\Models\BalanceSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateDailyBalanceSnapshot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:create-snapshot {--type=daily : نوع دوره (daily, monthly, yearly)} {--date= : تاریخ به فرمت Y-m-d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ایجاد اسنپشات موجودی و درآمد سیستم';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $date = $this->option('date');
        
        // اعتبارسنجی نوع دوره
        if (!in_array($type, ['daily', 'monthly', 'yearly'])) {
            $this->error('نوع دوره باید یکی از مقادیر daily, monthly, yearly باشد.');
            return 1;
        }
        
        try {
            $this->info('در حال ایجاد اسنپشات ' . $type . '...');
            
            $snapshot = BalanceSnapshot::createSnapshot($type, $date);
            
            if ($snapshot) {
                $this->info('اسنپشات با موفقیت ایجاد شد:');
                $this->table(
                    ['ID', 'تاریخ', 'نوع دوره', 'موجودی کل', 'درآمد کل', 'برداشت‌ها', 'برداشت‌های در انتظار'],
                    [
                        [
                            $snapshot->id,
                            $snapshot->date->format('Y-m-d'),
                            $snapshot->period_type,
                            number_format($snapshot->total_balance),
                            number_format($snapshot->total_revenue),
                            number_format($snapshot->total_withdrawals),
                            number_format($snapshot->total_pending_withdrawals)
                        ]
                    ]
                );
            } else {
                $this->warn('اسنپشات برای این تاریخ و نوع دوره قبلاً ایجاد شده است.');
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error('خطا در ایجاد اسنپشات: ' . $e->getMessage(), [
                'type' => $type,
                'date' => $date
            ]);
            
            $this->error('خطا در ایجاد اسنپشات: ' . $e->getMessage());
            return 1;
        }
    }
}
