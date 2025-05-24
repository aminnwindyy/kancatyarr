<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceSnapshot extends Model
{
    use HasFactory;
    
    /**
     * نوع دوره: روزانه
     */
    const PERIOD_DAILY = 'daily';
    
    /**
     * نوع دوره: ماهانه
     */
    const PERIOD_MONTHLY = 'monthly';
    
    /**
     * نوع دوره: سالانه
     */
    const PERIOD_YEARLY = 'yearly';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'period_type',
        'total_balance',
        'total_revenue',
        'total_withdrawals',
        'total_pending_withdrawals',
        'additional_data',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'total_balance' => 'decimal:0',
        'total_revenue' => 'decimal:0',
        'total_withdrawals' => 'decimal:0',
        'total_pending_withdrawals' => 'decimal:0',
        'additional_data' => 'json',
    ];
    
    /**
     * محدود کردن کوئری به اسنپشات‌های روزانه
     */
    public function scopeDaily($query)
    {
        return $query->where('period_type', self::PERIOD_DAILY);
    }
    
    /**
     * محدود کردن کوئری به اسنپشات‌های ماهانه
     */
    public function scopeMonthly($query)
    {
        return $query->where('period_type', self::PERIOD_MONTHLY);
    }
    
    /**
     * محدود کردن کوئری به اسنپشات‌های سالانه
     */
    public function scopeYearly($query)
    {
        return $query->where('period_type', self::PERIOD_YEARLY);
    }
    
    /**
     * محدود کردن کوئری به اسنپشات‌های یک دوره زمانی خاص
     */
    public function scopeForPeriod($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }
    
    /**
     * محدود کردن کوئری به اسنپشات‌های یک بازه زمانی
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
    
    /**
     * دریافت آخرین اسنپشات برای یک نوع دوره خاص
     */
    public static function getLatest($periodType = self::PERIOD_DAILY)
    {
        return self::where('period_type', $periodType)
            ->latest('date')
            ->first();
    }
    
    /**
     * ایجاد یک اسنپشات جدید با داده‌های فعلی
     */
    public static function createSnapshot($periodType = self::PERIOD_DAILY, $date = null)
    {
        // اگر تاریخ مشخص نشده باشد از تاریخ امروز استفاده می‌کنیم
        $date = $date ?? now()->format('Y-m-d');
        
        // بررسی اینکه آیا اسنپشات برای این تاریخ و نوع دوره قبلاً ایجاد شده است
        $exists = self::where('date', $date)
            ->where('period_type', $periodType)
            ->exists();
        
        if ($exists) {
            return null;
        }
        
        // محاسبه مقادیر مالی
        $totalBalance = 0;
        $totalRevenue = 0;
        $totalWithdrawals = 0;
        $totalPendingWithdrawals = 0;
        
        // موجودی کل = جمع همه واریزی‌ها - جمع همه برداشت‌ها
        $totalDeposits = AccountingTransaction::where('type', AccountingTransaction::TYPE_DEPOSIT)
            ->whereIn('status', [AccountingTransaction::STATUS_APPROVED, AccountingTransaction::STATUS_SETTLED])
            ->sum('amount');
        
        $totalWithdrawalsApproved = AccountingTransaction::whereIn('type', [
                AccountingTransaction::TYPE_WITHDRAW_USER, 
                AccountingTransaction::TYPE_WITHDRAW_PROVIDER
            ])
            ->whereIn('status', [AccountingTransaction::STATUS_APPROVED, AccountingTransaction::STATUS_SETTLED])
            ->sum('amount');
        
        $totalBalance = $totalDeposits - $totalWithdrawalsApproved;
        
        // درآمد کل (کارمزدها)
        $totalRevenue = AccountingTransaction::where('type', AccountingTransaction::TYPE_FEE)
            ->whereIn('status', [AccountingTransaction::STATUS_APPROVED, AccountingTransaction::STATUS_SETTLED])
            ->sum('amount');
        
        // مجموع برداشت‌ها
        $totalWithdrawals = $totalWithdrawalsApproved;
        
        // مجموع درخواست‌های برداشت در انتظار
        $totalPendingWithdrawals = AccountingTransaction::whereIn('type', [
                AccountingTransaction::TYPE_WITHDRAW_USER, 
                AccountingTransaction::TYPE_WITHDRAW_PROVIDER
            ])
            ->where('status', AccountingTransaction::STATUS_PENDING)
            ->sum('amount');
        
        // ایجاد اسنپشات جدید
        return self::create([
            'date' => $date,
            'period_type' => $periodType,
            'total_balance' => $totalBalance,
            'total_revenue' => $totalRevenue,
            'total_withdrawals' => $totalWithdrawals,
            'total_pending_withdrawals' => $totalPendingWithdrawals,
            'additional_data' => [
                'total_deposits' => $totalDeposits,
                'created_at' => now()->toIso8601String()
            ],
        ]);
    }
}
