<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * دریافت اطلاعات پایه داشبورد مانند تاریخ و زمان
     */
    public function getInfo()
    {
        // تنظیم منطقه زمانی به تهران
        $now = Carbon::now('Asia/Tehran');

        // تبدیل به تاریخ شمسی با استفاده از کتابخانه jalalian
        $jalaliDate = Jalalian::fromCarbon($now)->format('Y/m/d');

        // فرمت ساعت
        $time = $now->format('H:i:s');

        return response()->json([
            'date' => [
                'gregorian' => $now->format('Y-m-d'),
                'jalali' => $jalaliDate,
            ],
            'time' => $time,
            'timezone' => 'Asia/Tehran',
            'app_name' => config('app.name'),
            'app_version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * دریافت آمار کلی برای نمایش در قسمت بالایی داشبورد
     */
    public function getStats()
    {
        // محاسبه آمار کلی
        $usersCount = User::where('is_admin', false)->count();
        $sellersCount = Seller::count();
        $ordersCount = Order::count();

        // محاسبه درصد تغییرات نسبت به ماه گذشته
        $lastMonth = Carbon::now()->subMonth();

        $lastMonthUsersCount = User::where('is_admin', false)
            ->where('created_at', '<', $lastMonth)
            ->count();

        $lastMonthSellersCount = Seller::where('created_at', '<', $lastMonth)
            ->count();

        $lastMonthOrdersCount = Order::where('created_at', '<', $lastMonth)
            ->count();

        // محاسبه درصد تغییرات (رشد یا کاهش)
        $userChangePercent = $this->calculateGrowthPercentage($lastMonthUsersCount, $usersCount);
        $sellerChangePercent = $this->calculateGrowthPercentage($lastMonthSellersCount, $sellersCount);
        $orderChangePercent = $this->calculateGrowthPercentage($lastMonthOrdersCount, $ordersCount);

        return response()->json([
            'stats' => [
                'users' => [
                    'count' => $usersCount,
                    'change_percent' => $userChangePercent,
                    'is_positive' => $userChangePercent >= 0
                ],
                'sellers' => [
                    'count' => $sellersCount,
                    'change_percent' => $sellerChangePercent,
                    'is_positive' => $sellerChangePercent >= 0
                ],
                'orders' => [
                    'count' => $ordersCount,
                    'change_percent' => $orderChangePercent,
                    'is_positive' => $orderChangePercent >= 0
                ]
            ]
        ]);
    }

    /**
     * دریافت گزارش هفتگی تیکت‌ها
     */
    public function getTicketsReport()
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('support');
        
        if ($isAdmin) {
            // گزارش برای ادمین
            $ticketStats = [
                'total' => Ticket::count(),
                'open' => Ticket::open()->count(),
                'pending' => Ticket::pending()->count(),
                'closed' => Ticket::closed()->count(),
                'unread' => Ticket::unreadByAdmin()->count(),
                'last_24h' => Ticket::where('created_at', '>=', now()->subDay())->count(),
                'last_7d' => Ticket::where('created_at', '>=', now()->subDays(7))->count(),
                'last_30d' => Ticket::where('created_at', '>=', now()->subDays(30))->count(),
                'by_day' => $this->getTicketsByDay(),
                'response_time' => $this->getAverageResponseTime(),
            ];
        } else {
            // گزارش برای کاربر عادی
            $ticketStats = [
                'total' => Ticket::where('user_id', $user->id)->count(),
                'open' => Ticket::where('user_id', $user->id)->open()->count(),
                'pending' => Ticket::where('user_id', $user->id)->pending()->count(),
                'closed' => Ticket::where('user_id', $user->id)->closed()->count(),
                'unread' => Ticket::where('user_id', $user->id)->unreadByUser()->count(),
            ];
        }
        
        return response()->json(['tickets' => $ticketStats]);
    }

    /**
     * محاسبه تعداد تیکت‌ها بر اساس روز
     * 
     * @return array
     */
    private function getTicketsByDay()
    {
        $result = Ticket::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $data = [];
        foreach ($result as $item) {
            $data[] = [
                'date' => $item->date,
                'total' => $item->total
            ];
        }
        
        return $data;
    }
    
    /**
     * محاسبه میانگین زمان پاسخگویی
     * 
     * @return int میانگین زمان پاسخگویی به دقیقه
     */
    private function getAverageResponseTime()
    {
        // یافتن اولین پاسخ ادمین به هر تیکت
        $tickets = Ticket::with(['messages' => function($query) {
            $query->orderBy('sent_at', 'asc');
        }])->get();
        
        $total = 0;
        $count = 0;
        
        foreach ($tickets as $ticket) {
            $firstUserMessage = null;
            $firstAdminResponse = null;
            
            foreach ($ticket->messages as $message) {
                $isSenderAdmin = $message->sender->hasRole('admin') || $message->sender->hasRole('support');
                
                if (!$firstUserMessage && !$isSenderAdmin) {
                    $firstUserMessage = $message;
                } elseif ($firstUserMessage && $isSenderAdmin && !$firstAdminResponse) {
                    $firstAdminResponse = $message;
                    break;
                }
            }
            
            if ($firstUserMessage && $firstAdminResponse) {
                $diff = $firstAdminResponse->sent_at->diffInMinutes($firstUserMessage->sent_at);
                $total += $diff;
                $count++;
            }
        }
        
        return $count > 0 ? round($total / $count) : 0;
    }

    /**
     * دریافت کاربران جدید (آخرین 4 کاربر)
     */
    public function getNewUsers()
    {
        $newUsers = User::where('is_admin', false)
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get(['user_id', 'first_name', 'last_name', 'email', 'created_at', 'profile_image']);

        // تبدیل داده‌ها به فرمت مناسب برای نمایش
        $formattedUsers = $newUsers->map(function ($user) {
            return [
                'id' => $user->user_id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'date' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
                'profile_image' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
            ];
        });

        return response()->json([
            'new_users' => $formattedUsers
        ]);
    }

    /**
     * دریافت نمودار وضعیت برنامه (ثبت نام کاربران در طول زمان)
     */
    public function getUsersChart(Request $request)
    {
        // تعیین بازه زمانی براساس پارامتر ورودی (پیش‌فرض: هفته)
        $period = $request->input('period', 'week');

        $end = Carbon::now();
        $start = $this->getPeriodStartDate($period);
        $labels = $this->generateDateLabels($start, $end, $period);

        $userRegistrations = [];
        $sellerRegistrations = [];

        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            $nextDate = $this->getNextDate($currentDate, $period);

            // تعداد کاربران عادی ثبت نام شده در این بازه
            $userCount = User::where('is_admin', false)
                ->whereBetween('created_at', [$currentDate, $nextDate])
                ->count();

            // تعداد فروشندگان ثبت نام شده در این بازه
            $sellerCount = Seller::whereBetween('created_at', [$currentDate, $nextDate])
                ->count();

            $userRegistrations[] = $userCount;
            $sellerRegistrations[] = $sellerCount;

            $currentDate = $nextDate;
        }

        return response()->json([
            'chart' => [
                'period' => $period,
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'کاربران',
                        'data' => $userRegistrations,
                    ],
                    [
                        'label' => 'فروشندگان',
                        'data' => $sellerRegistrations,
                    ]
                ]
            ]
        ]);
    }

    /**
     * دریافت اطلاعات کامل داشبورد (ترکیبی از تمام متدهای بالا)
     */
    public function getDashboard(Request $request)
    {
        $period = $request->input('period', 'week');

        // تاریخ و ساعت
        $infoResponse = $this->getInfo();
        $info = json_decode($infoResponse->getContent(), true);

        // آمار کلی
        $statsResponse = $this->getStats();
        $stats = json_decode($statsResponse->getContent(), true);

        // گزارش تیکت‌ها
        $ticketsResponse = $this->getTicketsReport();
        $tickets = json_decode($ticketsResponse->getContent(), true);

        // کاربران جدید
        $usersResponse = $this->getNewUsers();
        $newUsers = json_decode($usersResponse->getContent(), true);

        // نمودار
        $request->merge(['period' => $period]);
        $chartResponse = $this->getUsersChart($request);
        $chart = json_decode($chartResponse->getContent(), true);

        return response()->json([
            'info' => $info,
            'stats' => $stats['stats'],
            'tickets' => $tickets['tickets'],
            'new_users' => $newUsers['new_users'],
            'chart' => $chart['chart']
        ]);
    }

    /**
     * محاسبه درصد رشد بین دو مقدار
     */
    private function calculateGrowthPercentage($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }

    /**
     * تعیین تاریخ شروع براساس بازه زمانی انتخاب شده
     */
    private function getPeriodStartDate($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'week':
                return $now->copy()->subWeek();
            case 'month':
                return $now->copy()->subMonth();
            case 'quarter':
                return $now->copy()->subQuarter();
            case 'year':
                return $now->copy()->subYear();
            default:
                return $now->copy()->subWeek();
        }
    }

    /**
     * تولید برچسب‌های تاریخ برای نمودار
     */
    private function generateDateLabels($start, $end, $period)
    {
        $labels = [];
        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            $format = $this->getDateFormat($period);
            $labels[] = $currentDate->format($format);
            $currentDate = $this->getNextDate($currentDate, $period);
        }

        return $labels;
    }

    /**
     * تعیین فرمت نمایش تاریخ براساس بازه زمانی
     */
    private function getDateFormat($period)
    {
        switch ($period) {
            case 'week':
                return 'D';  // روز هفته
            case 'month':
                return 'd/m';  // روز و ماه
            case 'quarter':
            case 'year':
                return 'M';  // نام ماه
            default:
                return 'Y-m-d';
        }
    }

    /**
     * محاسبه تاریخ بعدی براساس بازه زمانی
     */
    private function getNextDate($date, $period)
    {
        switch ($period) {
            case 'week':
                return $date->copy()->addDay();
            case 'month':
                return $date->copy()->addDays(3);  // هر 3 روز
            case 'quarter':
                return $date->copy()->addWeek();
            case 'year':
                return $date->copy()->addMonth();
            default:
                return $date->copy()->addDay();
        }
    }
}
