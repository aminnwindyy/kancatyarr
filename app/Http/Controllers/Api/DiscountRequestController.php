<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Events\DiscountRequestUpdated;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DiscountRequestsExport;
use Illuminate\Support\Facades\Mail;

class DiscountRequestController extends Controller
{
    /**
     * نمایش لیست درخواست‌های تخفیف
     */
    public function index(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('discount_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // فیلترها
        $status = $request->input('status');
        $period = $request->input('period'); // daily, weekly, monthly
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = DiscountRequest::query();

        // فیلتر براساس وضعیت
        if ($status) {
            $query->ofStatus($status);
        }

        // فیلتر براساس دوره زمانی
        if ($period) {
            switch ($period) {
                case 'daily':
                    $query->today();
                    break;
                case 'weekly':
                    $query->thisWeek();
                    break;
                case 'monthly':
                    $query->thisMonth();
                    break;
            }
        }

        // دریافت درخواست‌ها با اطلاعات مربوطه
        $discountRequests = $query->with([
                'serviceProvider:id,name',
                'product:product_id,name,price'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        // تهیه پاسخ
        $result = [];
        
        foreach ($discountRequests as $request) {
            $result[] = [
                'id' => $request->id,
                'service_provider_name' => $request->serviceProvider->name ?? 'نامشخص',
                'product_name' => $request->product->name ?? 'نامشخص',
                'discount_percentage' => $request->discount_percentage,
                'start_date' => $request->start_date->format('Y-m-d'),
                'end_date' => $request->end_date->format('Y-m-d'),
                'status' => $request->status,
                'created_at' => $request->created_at->format('Y-m-d')
            ];
        }

        return response()->json([
            'data' => $result,
            'total_pages' => $discountRequests->lastPage(),
            'current_page' => $discountRequests->currentPage()
        ]);
    }

    /**
     * نمایش جزئیات درخواست تخفیف
     */
    public function show(Request $request, $requestId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('discount_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // یافتن درخواست
        $discountRequest = DiscountRequest::with([
                'serviceProvider:id,name,email',
                'product:product_id,name,description,price'
            ])
            ->findOrFail($requestId);

        return response()->json([
            'discount_request' => [
                'id' => $discountRequest->id,
                'service_provider_name' => $discountRequest->serviceProvider->name ?? 'نامشخص',
                'service_provider_email' => $discountRequest->serviceProvider->email ?? '',
                'product_name' => $discountRequest->product->name ?? 'نامشخص',
                'product_description' => $discountRequest->product->description ?? '',
                'original_price' => $discountRequest->product->price ?? 0,
                'discount_percentage' => $discountRequest->discount_percentage,
                'discounted_price' => ($discountRequest->product->price ?? 0) * (1 - $discountRequest->discount_percentage / 100),
                'start_date' => $discountRequest->start_date->format('Y-m-d'),
                'end_date' => $discountRequest->end_date->format('Y-m-d'),
                'description' => $discountRequest->description,
                'status' => $discountRequest->status,
                'rejection_reason' => $discountRequest->rejection_reason,
                'created_at' => $discountRequest->created_at->format('Y-m-d')
            ]
        ]);
    }

    /**
     * تایید/رد درخواست تخفیف
     */
    public function approve(Request $request, $requestId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('discount_requests.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // اعتبارسنجی داده‌ها
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'description' => 'nullable|string|max:500',
        ]);

        // یافتن درخواست
        $discountRequest = DiscountRequest::with(['serviceProvider', 'product'])
            ->findOrFail($requestId);

        // به‌روزرسانی وضعیت
        $discountRequest->status = $validated['status'];
        if (isset($validated['description'])) {
            $discountRequest->rejection_reason = $validated['description'];
        }
        $discountRequest->save();

        // اعمال تخفیف روی محصول در صورت تایید
        if ($validated['status'] === 'approved') {
            $product = Product::find($discountRequest->product_id);
            if ($product) {
                // فرض می‌کنیم که جدول محصولات دارای فیلدهای مربوط به تخفیف است
                $product->discount_percentage = $discountRequest->discount_percentage;
                $product->discount_start_date = $discountRequest->start_date;
                $product->discount_end_date = $discountRequest->end_date;
                $product->has_discount = true;
                $product->save();
            }
        }

        // ارسال اعلان به خدمات‌دهنده
        try {
            if ($discountRequest->serviceProvider && $discountRequest->serviceProvider->email) {
                // ارسال ایمیل به خدمات‌دهنده
                // Mail::to($discountRequest->serviceProvider->email)->send(new DiscountRequestStatusNotification($discountRequest));
            }
        } catch (\Exception $e) {
            // ثبت خطای ارسال ایمیل
            \Log::error('خطای ارسال ایمیل: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'وضعیت درخواست تخفیف با موفقیت تغییر کرد.'
        ]);
    }

    /**
     * نمایش آمار تخفیف
     */
    public function discountStats(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('discount_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        $period = $request->input('period', 'monthly');
        
        // ساخت کوئری پایه
        $query = DiscountRequest::query();
        
        // اعمال فیلتر دوره زمانی
        $query->ofPeriod($period);
        
        // گروه‌بندی و شمارش براساس وضعیت
        $stats = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        // اطمینان از وجود تمام وضعیت‌ها در نتیجه
        $result = [
            'pending' => $stats['pending'] ?? 0,
            'approved' => $stats['approved'] ?? 0,
            'rejected' => $stats['rejected'] ?? 0,
        ];
        
        return response()->json([
            'stats' => $result
        ]);
    }

    /**
     * صدور گزارش درخواست‌های تخفیف
     */
    public function exportDiscountRequests(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('discount_requests.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        return Excel::download(new DiscountRequestsExport, 'discount_requests.xlsx');
    }
}