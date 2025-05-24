<?php

namespace App\Services;

use App\Models\AccountingTransaction;
use App\Models\BalanceSnapshot;
use App\Models\ServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * زمان ذخیره کش
     */
    const CACHE_TTL = 300; // 5 دقیقه
    
    /**
     * دریافت خلاصه وضعیت مالی
     *
     * @param string $period نوع دوره (daily, monthly, yearly)
     * @return array
     */
    public function getBalanceSummary(string $period = 'daily'): array
    {
        $cacheKey = 'accounting_summary_' . $period;
        
        // سعی می‌کنیم از کش بخوانیم
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // دریافت آخرین اسنپشات
            $snapshot = BalanceSnapshot::getLatest($period);
            
            if (!$snapshot) {
                // اگر اسنپشاتی وجود نداشت، محاسبه لحظه‌ای انجام دهیم
                $snapshot = BalanceSnapshot::createSnapshot($period);
                
                if (!$snapshot) {
                    throw new Exception('خطا در ایجاد اسنپشات');
                }
            }
            
            // روندهای رشد را محاسبه کنیم
            $previousSnapshot = $this->getPreviousSnapshot($snapshot);
            
            $result = [
                'date' => $snapshot->date->format('Y-m-d'),
                'total_balance' => (int) $snapshot->total_balance,
                'total_revenue' => (int) $snapshot->total_revenue,
                'total_withdrawals' => (int) $snapshot->total_withdrawals,
                'total_pending_withdrawals' => (int) $snapshot->total_pending_withdrawals,
                'period' => $period,
                'trends' => [
                    'balance_growth' => $this->calculateGrowth($previousSnapshot?->total_balance, $snapshot->total_balance),
                    'revenue_growth' => $this->calculateGrowth($previousSnapshot?->total_revenue, $snapshot->total_revenue),
                    'withdrawals_growth' => $this->calculateGrowth($previousSnapshot?->total_withdrawals, $snapshot->total_withdrawals),
                ]
            ];
            
            // ذخیره در کش
            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL));
            
            return $result;
        } catch (Exception $e) {
            Log::error('خطا در دریافت خلاصه وضعیت مالی: ' . $e->getMessage());
            
            // مقادیر پیش‌فرض در صورت خطا
            return [
                'date' => now()->format('Y-m-d'),
                'total_balance' => 0,
                'total_revenue' => 0,
                'total_withdrawals' => 0,
                'total_pending_withdrawals' => 0,
                'period' => $period,
                'trends' => [
                    'balance_growth' => 0,
                    'revenue_growth' => 0,
                    'withdrawals_growth' => 0,
                ]
            ];
        }
    }
    
    /**
     * دریافت نمودار درآمد
     *
     * @param string $period نوع دوره (month, year)
     * @param int $limit تعداد آیتم‌ها
     * @return array
     */
    public function getRevenueChart(string $period = 'month', int $limit = 12): array
    {
        $cacheKey = 'accounting_revenue_chart_' . $period . '_' . $limit;
        
        // سعی می‌کنیم از کش بخوانیم
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $now = now();
            $data = [];
            $labels = [];
            
            if ($period === 'month') {
                // نمودار ماهانه در یک سال گذشته
                $periodType = BalanceSnapshot::PERIOD_MONTHLY;
                $startDate = $now->copy()->subMonths($limit)->startOfMonth();
                
                // دریافت اسنپشات‌های ماهانه
                $snapshots = BalanceSnapshot::where('period_type', $periodType)
                    ->where('date', '>=', $startDate)
                    ->orderBy('date')
                    ->get();
                
                // ایجاد آرایه ماه‌ها
                for ($i = 0; $i < $limit; $i++) {
                    $date = $now->copy()->subMonths($limit - 1 - $i)->startOfMonth();
                    $persianMonth = $this->getPersianMonth($date->month);
                    $labels[] = $persianMonth . ' ' . $date->format('Y');
                    
                    // بررسی اینکه آیا برای این ماه اسنپشاتی داریم
                    $monthSnapshot = $snapshots->firstWhere('date', $date->format('Y-m-d'));
                    $data[] = $monthSnapshot ? (int) $monthSnapshot->total_revenue : 0;
                }
            } else {
                // نمودار سالانه
                $periodType = BalanceSnapshot::PERIOD_YEARLY;
                $startDate = $now->copy()->subYears($limit)->startOfYear();
                
                // دریافت اسنپشات‌های سالانه
                $snapshots = BalanceSnapshot::where('period_type', $periodType)
                    ->where('date', '>=', $startDate)
                    ->orderBy('date')
                    ->get();
                
                // ایجاد آرایه سال‌ها
                for ($i = 0; $i < $limit; $i++) {
                    $date = $now->copy()->subYears($limit - 1 - $i)->startOfYear();
                    $labels[] = $date->format('Y');
                    
                    // بررسی اینکه آیا برای این سال اسنپشاتی داریم
                    $yearSnapshot = $snapshots->firstWhere('date', $date->format('Y-m-d'));
                    $data[] = $yearSnapshot ? (int) $yearSnapshot->total_revenue : 0;
                }
            }
            
            $result = [
                'labels' => $labels,
                'data' => $data,
                'period' => $period,
                'total' => array_sum($data)
            ];
            
            // ذخیره در کش
            Cache::put($cacheKey, $result, now()->addSeconds(self::CACHE_TTL));
            
            return $result;
        } catch (Exception $e) {
            Log::error('خطا در دریافت نمودار درآمد: ' . $e->getMessage());
            
            // مقادیر پیش‌فرض در صورت خطا
            return [
                'labels' => [],
                'data' => [],
                'period' => $period,
                'total' => 0
            ];
        }
    }
    
    /**
     * لیست تراکنش‌ها با فیلتر
     *
     * @param array $filters فیلترها
     * @param int $perPage تعداد آیتم در هر صفحه
     * @return LengthAwarePaginator
     */
    public function listTransactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            $query = AccountingTransaction::query()
                ->with(['user', 'provider', 'admin']);
            
            // اعمال فیلترها
            if (isset($filters['status']) && $filters['status']) {
                $query->ofStatus($filters['status']);
            }
            
            if (isset($filters['type']) && $filters['type']) {
                $query->ofType($filters['type']);
            }
            
            if (isset($filters['user_id']) && $filters['user_id']) {
                $query->forUser($filters['user_id']);
            }
            
            if (isset($filters['provider_id']) && $filters['provider_id']) {
                $query->forProvider($filters['provider_id']);
            }
            
            if (isset($filters['date_from']) && $filters['date_from']) {
                $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            }
            
            if (isset($filters['date_to']) && $filters['date_to']) {
                $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
            }
            
            // مرتب‌سازی
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            
            $query->orderBy($sortBy, $sortOrder);
            
            return $query->paginate($perPage)->withQueryString();
        } catch (Exception $e) {
            Log::error('خطا در دریافت لیست تراکنش‌ها: ' . $e->getMessage(), [
                'filters' => $filters
            ]);
            
            // در صورت خطا، یک پاگینیتور خالی برگردانیم
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }
    
    /**
     * دریافت جزئیات یک تراکنش
     *
     * @param int $id شناسه تراکنش
     * @return array|null
     */
    public function getTransactionDetails(int $id): ?array
    {
        try {
            $transaction = AccountingTransaction::with(['user', 'provider', 'admin'])
                ->find($id);
            
            if (!$transaction) {
                return null;
            }
            
            $data = $transaction->toArray();
            
            // اضافه کردن اطلاعات تکمیلی
            if ($transaction->user) {
                $data['user_name'] = $transaction->user->first_name . ' ' . $transaction->user->last_name;
                $data['user_mobile'] = $transaction->user->mobile;
                $data['user_email'] = $transaction->user->email;
            }
            
            if ($transaction->provider) {
                $data['provider_name'] = $transaction->provider->name;
                $data['provider_type'] = $transaction->provider->type;
            }
            
            if ($transaction->admin) {
                $data['admin_name'] = $transaction->admin->first_name . ' ' . $transaction->admin->last_name;
            }
            
            // وضعیت به فارسی
            $data['status_fa'] = $this->getStatusText($transaction->status);
            $data['type_fa'] = $this->getTypeText($transaction->type);
            
            return $data;
        } catch (Exception $e) {
            Log::error('خطا در دریافت جزئیات تراکنش: ' . $e->getMessage(), [
                'transaction_id' => $id
            ]);
            
            return null;
        }
    }
    
    /**
     * تایید تراکنش
     *
     * @param int $id شناسه تراکنش
     * @param int $adminId شناسه ادمین
     * @param string|null $trackingCode کد پیگیری
     * @return bool
     */
    public function approveTransaction(int $id, int $adminId, ?string $trackingCode = null): bool
    {
        try {
            DB::beginTransaction();
            
            $transaction = AccountingTransaction::find($id);
            
            if (!$transaction) {
                return false;
            }
            
            $result = $transaction->approve($adminId, $trackingCode);
            
            if ($result) {
                // حذف کش
                $this->forgetCache();
                
                DB::commit();
                return true;
            }
            
            DB::rollBack();
            return false;
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در تایید تراکنش: ' . $e->getMessage(), [
                'transaction_id' => $id,
                'admin_id' => $adminId
            ]);
            
            return false;
        }
    }
    
    /**
     * رد تراکنش
     *
     * @param int $id شناسه تراکنش
     * @param int $adminId شناسه ادمین
     * @param string $reason دلیل رد
     * @return bool
     */
    public function rejectTransaction(int $id, int $adminId, string $reason): bool
    {
        try {
            DB::beginTransaction();
            
            $transaction = AccountingTransaction::find($id);
            
            if (!$transaction) {
                return false;
            }
            
            $result = $transaction->reject($adminId, $reason);
            
            if ($result) {
                // حذف کش
                $this->forgetCache();
                
                DB::commit();
                return true;
            }
            
            DB::rollBack();
            return false;
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در رد تراکنش: ' . $e->getMessage(), [
                'transaction_id' => $id,
                'admin_id' => $adminId,
                'reason' => $reason
            ]);
            
            return false;
        }
    }
    
    /**
     * تسویه تراکنش
     *
     * @param int $id شناسه تراکنش
     * @param int $adminId شناسه ادمین
     * @param string|null $trackingCode کد پیگیری
     * @return bool
     */
    public function settleTransaction(int $id, int $adminId, ?string $trackingCode = null): bool
    {
        try {
            DB::beginTransaction();
            
            $transaction = AccountingTransaction::find($id);
            
            if (!$transaction) {
                return false;
            }
            
            $result = $transaction->settle($adminId, $trackingCode);
            
            if ($result) {
                // حذف کش
                $this->forgetCache();
                
                DB::commit();
                return true;
            }
            
            DB::rollBack();
            return false;
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در تسویه تراکنش: ' . $e->getMessage(), [
                'transaction_id' => $id,
                'admin_id' => $adminId
            ]);
            
            return false;
        }
    }
    
    /**
     * ایجاد درخواست برداشت کاربر
     *
     * @param int $userId شناسه کاربر
     * @param int $amount مبلغ
     * @param string $bankAccount شماره حساب بانکی
     * @param array $metadata اطلاعات اضافی
     * @return AccountingTransaction|null
     */
    public function createUserWithdrawalRequest(
        int $userId, 
        int $amount, 
        string $bankAccount, 
        array $metadata = []
    ): ?AccountingTransaction {
        try {
            DB::beginTransaction();
            
            // بررسی وجود کاربر
            $user = User::find($userId);
            
            if (!$user) {
                throw new Exception('کاربر یافت نشد');
            }
            
            // ایجاد تراکنش برداشت
            $transaction = AccountingTransaction::create([
                'user_id' => $userId,
                'type' => AccountingTransaction::TYPE_WITHDRAW_USER,
                'amount' => $amount,
                'status' => AccountingTransaction::STATUS_PENDING,
                'bank_account' => $bankAccount,
                'metadata' => $metadata
            ]);
            
            // حذف کش
            $this->forgetCache();
            
            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در ایجاد درخواست برداشت: ' . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount
            ]);
            
            return null;
        }
    }
    
    /**
     * ایجاد درخواست برداشت خدمات‌دهنده
     *
     * @param int $providerId شناسه خدمات‌دهنده
     * @param int $amount مبلغ
     * @param string $bankAccount شماره حساب بانکی
     * @param array $metadata اطلاعات اضافی
     * @return AccountingTransaction|null
     */
    public function createProviderWithdrawalRequest(
        int $providerId, 
        int $amount, 
        string $bankAccount, 
        array $metadata = []
    ): ?AccountingTransaction {
        try {
            DB::beginTransaction();
            
            // بررسی وجود خدمات‌دهنده
            $provider = ServiceProvider::find($providerId);
            
            if (!$provider) {
                throw new Exception('خدمات‌دهنده یافت نشد');
            }
            
            // ایجاد تراکنش برداشت
            $transaction = AccountingTransaction::create([
                'provider_id' => $providerId,
                'type' => AccountingTransaction::TYPE_WITHDRAW_PROVIDER,
                'amount' => $amount,
                'status' => AccountingTransaction::STATUS_PENDING,
                'bank_account' => $bankAccount,
                'metadata' => $metadata
            ]);
            
            // حذف کش
            $this->forgetCache();
            
            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در ایجاد درخواست برداشت خدمات‌دهنده: ' . $e->getMessage(), [
                'provider_id' => $providerId,
                'amount' => $amount
            ]);
            
            return null;
        }
    }
    
    /**
     * حذف کش
     */
    private function forgetCache(): void
    {
        // حذف همه کش‌های مرتبط با حسابداری
        Cache::forget('accounting_summary_daily');
        Cache::forget('accounting_summary_monthly');
        Cache::forget('accounting_summary_yearly');
        Cache::forget('accounting_revenue_chart_month_12');
        Cache::forget('accounting_revenue_chart_year_5');
    }
    
    /**
     * محاسبه درصد رشد
     */
    private function calculateGrowth(?int $oldValue, int $newValue): float
    {
        if (!$oldValue || $oldValue == 0) {
            return 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }
    
    /**
     * دریافت اسنپشات قبلی
     */
    private function getPreviousSnapshot(?BalanceSnapshot $snapshot): ?BalanceSnapshot
    {
        if (!$snapshot) {
            return null;
        }
        
        if ($snapshot->period_type === BalanceSnapshot::PERIOD_DAILY) {
            // اسنپشات روز قبل
            return BalanceSnapshot::where('period_type', BalanceSnapshot::PERIOD_DAILY)
                ->where('date', '<', $snapshot->date)
                ->orderBy('date', 'desc')
                ->first();
        } elseif ($snapshot->period_type === BalanceSnapshot::PERIOD_MONTHLY) {
            // اسنپشات ماه قبل
            $previousMonth = Carbon::parse($snapshot->date)->subMonth();
            return BalanceSnapshot::where('period_type', BalanceSnapshot::PERIOD_MONTHLY)
                ->whereYear('date', $previousMonth->year)
                ->whereMonth('date', $previousMonth->month)
                ->first();
        } else {
            // اسنپشات سال قبل
            $previousYear = Carbon::parse($snapshot->date)->subYear();
            return BalanceSnapshot::where('period_type', BalanceSnapshot::PERIOD_YEARLY)
                ->whereYear('date', $previousYear->year)
                ->first();
        }
    }
    
    /**
     * تبدیل وضعیت به متن فارسی
     */
    private function getStatusText(string $status): string
    {
        $texts = [
            AccountingTransaction::STATUS_PENDING => 'در انتظار',
            AccountingTransaction::STATUS_APPROVED => 'تایید شده',
            AccountingTransaction::STATUS_REJECTED => 'رد شده',
            AccountingTransaction::STATUS_SETTLED => 'تسویه شده',
        ];
        
        return $texts[$status] ?? $status;
    }
    
    /**
     * تبدیل نوع تراکنش به متن فارسی
     */
    private function getTypeText(string $type): string
    {
        $texts = [
            AccountingTransaction::TYPE_WITHDRAW_USER => 'برداشت کاربر',
            AccountingTransaction::TYPE_WITHDRAW_PROVIDER => 'برداشت خدمات‌دهنده',
            AccountingTransaction::TYPE_DEPOSIT => 'واریز',
            AccountingTransaction::TYPE_FEE => 'کارمزد',
            AccountingTransaction::TYPE_REFUND => 'استرداد',
            AccountingTransaction::TYPE_SETTLEMENT => 'تسویه',
        ];
        
        return $texts[$type] ?? $type;
    }
    
    /**
     * تبدیل شماره ماه به نام فارسی
     */
    private function getPersianMonth(int $month): string
    {
        $months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند'
        ];
        
        return $months[$month] ?? "ماه $month";
    }
}
