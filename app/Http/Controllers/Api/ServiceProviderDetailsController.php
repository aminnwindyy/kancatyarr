<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderActivity;
use App\Models\ServiceProviderRating;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ServiceProviderDetailsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ServiceProviderDetailsController extends Controller
{
    /**
     * Display details of the service provider.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function details(int $id)
    {
        // Find service provider or return 404
        $serviceProvider = ServiceProvider::with('activities')->findOrFail($id);

        // Get completed and in-progress orders
        $completedOrders = $this->getOrdersByStatus($id, 'completed');
        $inProgressOrders = $this->getOrdersByStatus($id, 'in_progress');

        // Get activity chart data with cache
        $activityChart = $this->getActivityChartData($id);

        return response()->json([
            'success' => true,
            'service_provider' => [
                'id' => $serviceProvider->id,
                'name' => $serviceProvider->name,
                'email' => $serviceProvider->email,
                'category' => $serviceProvider->type, // We're using 'type' field as 'category'
                'status' => $serviceProvider->status,
                'rating' => $serviceProvider->rating,
                'created_at' => $serviceProvider->created_at->format('Y-m-d')
            ],
            'activities' => $serviceProvider->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'activity_name' => $activity->activity_name,
                    'is_active' => $activity->is_active
                ];
            }),
            'activity_chart' => $activityChart,
            'orders' => [
                'completed' => $completedOrders,
                'in_progress' => $inProgressOrders
            ]
        ]);
    }

    /**
     * Toggle activity status.
     *
     * @param Request $request
     * @param int $id
     * @param int $activityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActivity(Request $request, int $id, int $activityId)
    {
        // Check admin permission
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه دسترسی به این بخش را ندارید'
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find service provider activity or return 404
        $activity = ServiceProviderActivity::where('service_provider_id', $id)
            ->where('id', $activityId)
            ->firstOrFail();

        // Update activity status
        $activity->is_active = $request->is_active;
        $activity->save();

        // Clear activity cache
        $this->clearActivityCache($id);

        $message = $request->is_active ? 'فعالیت با موفقیت فعال شد.' : 'فعالیت با موفقیت غیرفعال شد.';

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Get activity chart data.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityChart(int $id)
    {
        // Find service provider or return 404
        $serviceProvider = ServiceProvider::findOrFail($id);

        $chartData = $this->getActivityChartData($id);

        return response()->json([
            'success' => true,
            'labels' => $chartData['labels'],
            'data' => $chartData['data']
        ]);
    }

    /**
     * Rate the service provider.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rate(Request $request, int $id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:0|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find service provider or return 404
        $serviceProvider = ServiceProvider::findOrFail($id);

        // Create or update rating
        ServiceProviderRating::updateOrCreate(
            [
                'service_provider_id' => $id,
                'user_id' => auth()->id()
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment ?? null
            ]
        );

        // Update the average rating on service provider
        $this->updateServiceProviderRating($id);

        return response()->json([
            'success' => true,
            'message' => 'امتیاز با موفقیت ثبت شد.'
        ]);
    }

    /**
     * Get orders for the service provider.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function orders(Request $request, int $id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:completed,in_progress',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find service provider or return 404
        $serviceProvider = ServiceProvider::findOrFail($id);

        // Prepare query with JOIN for optimization
        $query = Order::select(
                'orders.*',
                'users.name as customer_name'
            )
            ->leftJoin('users', 'orders.user_id', '=', 'users.user_id')
            ->where('orders.seller_id', $id);

        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('orders.status', $request->status);
        }

        // Paginate results
        $limit = $request->input('limit', 10);
        $orders = $query->latest()->paginate($limit);

        // Format orders data
        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->order_id,
                'customer_name' => $order->customer_name ?? 'کاربر ناشناس',
                'order_date' => Carbon::parse($order->created_at)->format('Y-m-d'),
                'status' => $order->status
            ];
        });

        // Get orders by status for response
        $completedOrders = $request->status === 'in_progress' ? [] : 
            $formattedOrders->where('status', 'completed')->values();
        
        $inProgressOrders = $request->status === 'completed' ? [] : 
            $formattedOrders->where('status', 'in_progress')->values();

        return response()->json([
            'success' => true,
            'completed_orders' => $completedOrders,
            'in_progress_orders' => $inProgressOrders,
            'total_pages' => $orders->lastPage(),
            'current_page' => $orders->currentPage()
        ]);
    }

    /**
     * Export details to PDF/Excel.
     *
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function exportDetails(Request $request, int $id)
    {
        try {
            // Find service provider or return 404
            $serviceProvider = ServiceProvider::with(['activities', 'orders', 'ratings'])->findOrFail($id);
            
            $format = $request->input('format', 'excel'); // Default to Excel
            $fileName = 'service-provider-' . $id . '-details-' . date('Y-m-d');
            
            if ($format === 'pdf') {
                $pdf = PDF::loadView('exports.service-provider-details', [
                    'serviceProvider' => $serviceProvider,
                    'completedOrders' => $serviceProvider->orders->where('status', 'completed'),
                    'inProgressOrders' => $serviceProvider->orders->where('status', 'in_progress'),
                    'activityChart' => $this->getActivityChartData($id)
                ]);
                
                Storage::put('public/exports/' . $fileName . '.pdf', $pdf->output());
                
                return response()->json([
                    'success' => true,
                    'message' => 'فایل با موفقیت ایجاد شد.',
                    'download_url' => url('storage/exports/' . $fileName . '.pdf')
                ]);
            } else {
                // Export to Excel
                Excel::store(
                    new ServiceProviderDetailsExport($serviceProvider, $this->getActivityChartData($id)),
                    'public/exports/' . $fileName . '.xlsx'
                );
                
                return response()->json([
                    'success' => true,
                    'message' => 'فایل با موفقیت ایجاد شد.',
                    'download_url' => url('storage/exports/' . $fileName . '.xlsx')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * لیست خدمات‌دهندگان بر اساس نوع
     *
     * @param Request $request
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByType(Request $request, string $type)
    {
        // اعتبارسنجی نوع
        if (!in_array($type, ['commercial', 'connectyar'])) {
            return response()->json([
                'success' => false,
                'message' => 'نوع خدمات‌دهنده نامعتبر است.'
            ], 400);
        }

        // آماده‌سازی کوئری با JOIN برای بهینه‌سازی
        $limit = $request->input('limit', 10);
        $query = ServiceProvider::select(
                'service_providers.*', 
                DB::raw('(SELECT COUNT(*) FROM orders WHERE orders.seller_id = service_providers.id AND orders.status = "completed") as completed_orders_count')
            )
            ->where('type', $type);

        // اعمال جستجو
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('email', 'LIKE', "%{$request->search}%");
            });
        }

        // اعمال فیلتر وضعیت
        if ($request->filled('status') && in_array($request->status, ['active', 'inactive', 'pending', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        if (in_array($sortField, ['name', 'email', 'created_at', 'status'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        // پیج‌بندی نتایج با load relationship ها
        $serviceProviders = $query->with('activities')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $serviceProviders
        ]);
    }

    /**
     * جزئیات خدمات‌دهنده کانکت یار
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function connectyarDetails(int $id)
    {
        // یافتن خدمات‌دهنده یا برگرداندن 404
        $serviceProvider = ServiceProvider::with(['activities', 'documents'])
            ->where('type', 'connectyar')
            ->findOrFail($id);

        // دریافت سفارشات تکمیل شده و در حال انجام
        $completedOrders = $this->getOrdersByStatus($id, 'completed');
        $inProgressOrders = $this->getOrdersByStatus($id, 'in_progress');

        // دریافت داده‌های نمودار فعالیت با cache
        $activityChart = $this->getActivityChartData($id);
        
        // اطلاعات اضافی مخصوص کانکت یار
        $connectyarInfo = [
            'specialty_fields' => $serviceProvider->activities->pluck('activity_name'),
            'service_areas' => $this->getServiceAreas($id),
            'availability_hours' => $this->getAvailabilityHours($id),
            'verification_status' => $serviceProvider->documents->pluck('status', 'name')
        ];

        return response()->json([
            'success' => true,
            'service_provider' => [
                'id' => $serviceProvider->id,
                'name' => $serviceProvider->name,
                'email' => $serviceProvider->email,
                'category' => 'connectyar',
                'status' => $serviceProvider->status,
                'rating' => $serviceProvider->rating,
                'created_at' => $serviceProvider->created_at->format('Y-m-d'),
                'phone' => $serviceProvider->phone,
                'address' => $serviceProvider->address,
                'description' => $serviceProvider->description,
                'website' => $serviceProvider->website,
            ],
            'connectyar_info' => $connectyarInfo,
            'activities' => $serviceProvider->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'activity_name' => $activity->activity_name,
                    'is_active' => $activity->is_active
                ];
            }),
            'activity_chart' => $activityChart,
            'orders' => [
                'completed' => $completedOrders,
                'in_progress' => $inProgressOrders
            ]
        ]);
    }

    /**
     * آمار عملکرد خدمات‌دهنده
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function performanceStats(int $id)
    {
        // یافتن خدمات‌دهنده یا برگرداندن 404
        $serviceProvider = ServiceProvider::findOrFail($id);

        // محاسبه آمار عملکرد با JOIN برای بهینه‌سازی
        $stats = DB::table('orders')
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_orders'),
                DB::raw('AVG(CASE WHEN status = "completed" THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_completion_time')
            ])
            ->where('seller_id', $id)
            ->first();
        
        $totalOrders = $stats->total_orders ?? 0;
        $completedOrders = $stats->completed_orders ?? 0;
        $avgCompletionTime = $stats->avg_completion_time ?? 0;
        
        // آمار عملکرد ماهانه
        $monthlyStats = $this->getMonthlyPerformanceStats($id);

        return response()->json([
            'success' => true,
            'stats' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 2) : 0,
                'average_rating' => $serviceProvider->rating,
                'average_completion_time_days' => round($avgCompletionTime, 1),
                'monthly_performance' => $monthlyStats
            ]
        ]);
    }

    /**
     * Helper method to get orders by status with JOIN for optimization.
     *
     * @param int $serviceProviderId
     * @param string $status
     * @return array
     */
    private function getOrdersByStatus(int $serviceProviderId, string $status)
    {
        return DB::table('orders')
            ->select(
                'orders.order_id as id',
                'users.name as customer_name',
                DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m-%d") as order_date'),
                'orders.status'
            )
            ->leftJoin('users', 'orders.user_id', '=', 'users.user_id')
            ->where('orders.seller_id', $serviceProviderId)
            ->where('orders.status', $status)
            ->orderBy('orders.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'customer_name' => $order->customer_name ?? 'کاربر ناشناس',
                    'order_date' => $order->order_date,
                    'status' => $order->status
                ];
            })
            ->toArray();
    }

    /**
     * Helper method to generate activity chart data with caching.
     *
     * @param int $serviceProviderId
     * @return array
     */
    private function getActivityChartData(int $serviceProviderId)
    {
        $cacheKey = 'activity_chart_' . $serviceProviderId;
        
        // Cache for 1 hour
        return Cache::remember($cacheKey, 3600, function () use ($serviceProviderId) {
            $months = [];
            $monthsData = [];
            
            for ($i = 2; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthName = $this->getPersianMonth($date->month);
                $months[] = $monthName;
                
                // Count completed orders for this month with optimized query
                $count = DB::table('orders')
                    ->where('seller_id', $serviceProviderId)
                    ->where('status', 'completed')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                    
                $monthsData[] = $count;
            }
            
            return [
                'labels' => $months,
                'data' => $monthsData
            ];
        });
    }

    /**
     * Clear activity chart cache.
     *
     * @param int $serviceProviderId
     * @return void
     */
    private function clearActivityCache(int $serviceProviderId)
    {
        Cache::forget('activity_chart_' . $serviceProviderId);
    }

    /**
     * Helper method to update the service provider's average rating.
     *
     * @param int $serviceProviderId
     * @return void
     */
    private function updateServiceProviderRating(int $serviceProviderId)
    {
        $avgRating = ServiceProviderRating::where('service_provider_id', $serviceProviderId)
            ->avg('rating') ?? 0;
            
        ServiceProvider::where('id', $serviceProviderId)
            ->update(['rating' => $avgRating]);
    }
    
    /**
     * Helper method to get Persian month name.
     *
     * @param int $month
     * @return string
     */
    private function getPersianMonth(int $month)
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
            12 => 'اسفند'
        ];
        
        return $persianMonths[$month] ?? '';
    }
    
    /**
     * متد کمکی برای دریافت مناطق سرویس‌دهی
     * 
     * @param int $serviceProviderId
     * @return array
     */
    private function getServiceAreas(int $serviceProviderId)
    {
        // این بخش پیاده‌سازی نشده است و باید بر اساس مدل داده‌های شما پیاده‌سازی شود
        // در این مثال، داده‌های نمونه برگردانده می‌شود
        return [
            'تهران - مرکز',
            'تهران - شمال',
            'کرج'
        ];
    }

    /**
     * متد کمکی برای دریافت ساعات کاری
     * 
     * @param int $serviceProviderId
     * @return array
     */
    private function getAvailabilityHours(int $serviceProviderId)
    {
        // این بخش پیاده‌سازی نشده است و باید بر اساس مدل داده‌های شما پیاده‌سازی شود
        // در این مثال، داده‌های نمونه برگردانده می‌شود
        return [
            'شنبه' => '9:00 - 18:00',
            'یکشنبه' => '9:00 - 18:00',
            'دوشنبه' => '9:00 - 18:00',
            'سه‌شنبه' => '9:00 - 18:00',
            'چهارشنبه' => '9:00 - 18:00',
            'پنج‌شنبه' => '9:00 - 14:00',
            'جمعه' => 'تعطیل'
        ];
    }

    /**
     * متد کمکی برای دریافت آمار عملکرد ماهانه با بهینه‌سازی کوئری
     * 
     * @param int $serviceProviderId
     * @return array
     */
    private function getMonthlyPerformanceStats(int $serviceProviderId)
    {
        $stats = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $this->getPersianMonth($date->month);
            $yearMonth = $date->format('Y-m');
            
            // Use a single query with conditional aggregates
            $monthStats = DB::table('orders')
                ->select([
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count')
                ])
                ->where('seller_id', $serviceProviderId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->first();
                
            $ordersCount = $monthStats->orders_count ?? 0;
            $completedCount = $monthStats->completed_count ?? 0;
                
            $stats[] = [
                'month' => $monthName,
                'year_month' => $yearMonth,
                'orders_count' => $ordersCount,
                'completed_count' => $completedCount,
                'completion_rate' => $ordersCount > 0 ? round(($completedCount / $ordersCount) * 100, 2) : 0
            ];
        }
        
        return $stats;
    }
}
