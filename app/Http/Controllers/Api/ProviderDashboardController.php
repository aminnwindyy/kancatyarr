<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderDashboardController extends Controller
{
    /**
     * دریافت اطلاعات داشبورد ارائه دهنده خدمات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $serviceProvider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$serviceProvider) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات ارائه دهنده خدمات یافت نشد.',
            ], 404);
        }

        // بررسی وضعیت ارائه‌دهنده
        if ($serviceProvider->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'حساب ارائه‌دهنده خدمات شما هنوز تأیید نشده است.',
                'provider_status' => $serviceProvider->status,
            ], 403);
        }

        // دریافت آمار سفارش‌ها
        $ordersStats = $this->getOrdersStats($serviceProvider->id);

        // دریافت آمار مالی
        $financialStats = $this->getFinancialStats($serviceProvider->id);

        // به‌روزرسانی آخرین فعالیت ارائه‌دهنده
        $serviceProvider->updateLastActivity();

        // دریافت فعالیت‌های اخیر
        $recentActivities = $this->getRecentActivities($serviceProvider->id);

        // دریافت نمودار فروش ماهانه
        $salesChart = $this->getMonthlySalesChart($serviceProvider->id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'provider' => [
                    'id' => $serviceProvider->id,
                    'name' => $serviceProvider->name,
                    'rating' => $serviceProvider->rating,
                    'status' => $serviceProvider->status,
                    'last_activity_at' => $serviceProvider->last_activity_at,
                ],
                'orders_stats' => $ordersStats,
                'financial_stats' => $financialStats,
                'recent_activities' => $recentActivities,
                'sales_chart' => $salesChart,
            ],
        ]);
    }

    /**
     * دریافت آمار سفارش‌ها
     *
     * @param int $providerId
     * @return array
     */
    private function getOrdersStats($providerId)
    {
        // تعداد کل سفارشات
        $totalOrders = Order::where('seller_id', $providerId)->count();

        // تعداد سفارشات در انتظار تایید
        $pendingOrders = Order::where('seller_id', $providerId)
            ->where('status', 'pending')
            ->count();

        // تعداد سفارشات در حال انجام
        $inProgressOrders = Order::where('seller_id', $providerId)
            ->where('status', 'processing')
            ->count();

        // تعداد سفارشات تکمیل شده
        $completedOrders = Order::where('seller_id', $providerId)
            ->where('status', 'completed')
            ->count();

        // تعداد سفارشات لغو شده
        $cancelledOrders = Order::where('seller_id', $providerId)
            ->where('status', 'cancelled')
            ->count();

        // نرخ تکمیل سفارش
        $completionRate = ($totalOrders > 0) 
            ? round(($completedOrders / $totalOrders) * 100, 2) 
            : 0;

        return [
            'total' => $totalOrders,
            'pending' => $pendingOrders,
            'in_progress' => $inProgressOrders,
            'completed' => $completedOrders,
            'cancelled' => $cancelledOrders,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * دریافت آمار مالی
     *
     * @param int $providerId
     * @return array
     */
    private function getFinancialStats($providerId)
    {
        // درآمد کل
        $totalRevenue = Order::where('seller_id', $providerId)
            ->where('status', 'completed')
            ->sum('total_amount');

        // درآمد ماه جاری
        $currentMonthRevenue = Order::where('seller_id', $providerId)
            ->where('status', 'completed')
            ->whereMonth('completed_at', Carbon::now()->month)
            ->whereYear('completed_at', Carbon::now()->year)
            ->sum('total_amount');

        // درآمد هفته جاری
        $currentWeekRevenue = Order::where('seller_id', $providerId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('total_amount');

        // موجودی قابل برداشت
        $availableBalance = 0; // این مقدار باید از جدول wallet یا transactions محاسبه شود

        // برداشت‌های انجام شده
        $totalWithdrawals = 0; // این مقدار باید از جدول transactions محاسبه شود

        // میانگین ارزش سفارش
        $averageOrderValue = Order::where('seller_id', $providerId)
            ->where('status', 'completed')
            ->avg('total_amount') ?? 0;

        return [
            'total_revenue' => round($totalRevenue, 2),
            'current_month_revenue' => round($currentMonthRevenue, 2),
            'current_week_revenue' => round($currentWeekRevenue, 2),
            'available_balance' => round($availableBalance, 2),
            'total_withdrawals' => round($totalWithdrawals, 2),
            'average_order_value' => round($averageOrderValue, 2),
        ];
    }

    /**
     * دریافت فعالیت‌های اخیر
     *
     * @param int $providerId
     * @return array
     */
    private function getRecentActivities($providerId)
    {
        // این قسمت باید با توجه به ساختار دیتابیس تکمیل شود
        // برای نمونه، ۵ سفارش اخیر را برمی‌گردانیم
        $recentOrders = Order::where('seller_id', $providerId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => 'order',
                    'title' => "سفارش جدید: #{$order->id}",
                    'description' => "سفارش جدید به مبلغ " . number_format($order->total_amount) . " تومان دریافت شده است.",
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });

        return $recentOrders;
    }

    /**
     * دریافت نمودار فروش ماهانه
     *
     * @param int $providerId
     * @return array
     */
    private function getMonthlySalesChart($providerId)
    {
        $months = [];
        $sales = [];

        // محاسبه فروش برای ۶ ماه گذشته
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $this->getPersianMonth($date->month);
            
            $monthlySales = Order::where('seller_id', $providerId)
                ->where('status', 'completed')
                ->whereMonth('completed_at', $date->month)
                ->whereYear('completed_at', $date->year)
                ->sum('total_amount');
            
            $months[] = $monthName;
            $sales[] = round($monthlySales, 2);
        }

        return [
            'labels' => $months,
            'data' => $sales,
        ];
    }

    /**
     * تبدیل شماره ماه به نام ماه فارسی
     *
     * @param int $month
     * @return string
     */
    private function getPersianMonth($month)
    {
        $persianMonths = [
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
            12 => 'اسفند',
        ];

        return $persianMonths[$month] ?? '';
    }
} 