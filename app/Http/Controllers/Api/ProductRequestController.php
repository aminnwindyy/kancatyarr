<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Events\ProductRequestUpdated;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductRequestsExport;
use App\Exports\AllRequestsExport;
use Illuminate\Support\Facades\Mail;

class ProductRequestController extends Controller
{
    /**
     * نمایش لیست درخواست‌های ثبت محصول
     */
    public function index(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // فیلترها
        $status = $request->input('status');
        $period = $request->input('period'); // daily, weekly, monthly
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = ProductRequest::query();

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
        $productRequests = $query->with([
                'serviceProvider:id,name',
                'category:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        // تهیه پاسخ
        $result = [];
        
        foreach ($productRequests as $request) {
            $result[] = [
                'id' => $request->id,
                'service_provider_name' => $request->serviceProvider->name ?? 'نامشخص',
                'name' => $request->name,
                'category_name' => $request->category->name ?? 'نامشخص',
                'price' => $request->price,
                'status' => $request->status,
                'created_at' => $request->created_at->format('Y-m-d')
            ];
        }

        return response()->json([
            'data' => $result,
            'total_pages' => $productRequests->lastPage(),
            'current_page' => $productRequests->currentPage()
        ]);
    }

    /**
     * نمایش جزئیات درخواست ثبت محصول
     */
    public function show(Request $request, $requestId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // یافتن درخواست
        $productRequest = ProductRequest::with([
                'serviceProvider:id,name,email',
                'category:id,name'
            ])
            ->findOrFail($requestId);

        return response()->json([
            'product_request' => [
                'id' => $productRequest->id,
                'service_provider_name' => $productRequest->serviceProvider->name ?? 'نامشخص',
                'service_provider_email' => $productRequest->serviceProvider->email ?? '',
                'name' => $productRequest->name,
                'description' => $productRequest->description,
                'category_name' => $productRequest->category->name ?? 'نامشخص',
                'price' => $productRequest->price,
                'image_path' => $productRequest->image_path,
                'status' => $productRequest->status,
                'rejection_reason' => $productRequest->rejection_reason,
                'created_at' => $productRequest->created_at->format('Y-m-d')
            ]
        ]);
    }

    /**
     * تایید/رد درخواست ثبت محصول
     */
    public function approve(Request $request, $requestId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // اعتبارسنجی داده‌ها
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'description' => 'nullable|string|max:500',
        ]);

        // یافتن درخواست
        $productRequest = ProductRequest::with(['serviceProvider'])
            ->findOrFail($requestId);

        // به‌روزرسانی وضعیت
        $productRequest->status = $validated['status'];
        if (isset($validated['description'])) {
            $productRequest->rejection_reason = $validated['description'];
        }
        $productRequest->save();

        // ایجاد محصول جدید در صورت تایید
        if ($validated['status'] === 'approved') {
            $product = new Product([
                'service_provider_id' => $productRequest->service_provider_id,
                'name' => $productRequest->name,
                'description' => $productRequest->description,
                'category_id' => $productRequest->category_id,
                'price' => $productRequest->price,
                'image_path' => $productRequest->image_path,
                'is_active' => true
            ]);
            $product->save();
        }

        // ارسال اعلان به خدمات‌دهنده
        try {
            if ($productRequest->serviceProvider && $productRequest->serviceProvider->email) {
                // ارسال ایمیل به خدمات‌دهنده
                // Mail::to($productRequest->serviceProvider->email)->send(new ProductRequestStatusNotification($productRequest));
            }
        } catch (\Exception $e) {
            // ثبت خطای ارسال ایمیل
            \Log::error('خطای ارسال ایمیل: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'وضعیت درخواست ثبت محصول با موفقیت تغییر کرد.'
        ]);
    }

    /**
     * دانلود فایل پیوست درخواست محصول
     */
    public function downloadFile(Request $request, $id)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // یافتن درخواست محصول
        $productRequest = ProductRequest::findOrFail($id);

        // بررسی وجود فایل
        if (!$productRequest->file_path) {
            return response()->json(['message' => 'فایلی برای این درخواست وجود ندارد'], 404);
        }

        // دانلود فایل
        return Storage::download($productRequest->file_path);
    }

    /**
     * نمایش درخواست‌های محصول در انتظار
     */
    public function pendingRequests(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // دریافت درخواست‌های محصول در انتظار
        $pendingRequests = ProductRequest::where('status', 'pending')
            ->with(['serviceProvider:id,name', 'category:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('limit', 10));

        // تهیه پاسخ
        $result = [];
        
        foreach ($pendingRequests as $request) {
            $result[] = [
                'id' => $request->id,
                'service_provider_name' => $request->serviceProvider->name ?? 'نامشخص',
                'product_name' => $request->product_name,
                'status' => $request->status,
                'created_at' => $request->created_at->format('Y-m-d')
            ];
        }

        return response()->json([
            'data' => $result,
            'total_pages' => $pendingRequests->lastPage(),
            'current_page' => $pendingRequests->currentPage()
        ]);
    }

    /**
     * صدور گزارش درخواست‌های ثبت محصول
     */
    public function exportProductRequests(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        return Excel::download(new ProductRequestsExport, 'product_requests.xlsx');
    }

    /**
     * صدور گزارش ترکیبی از درخواست‌های محصولات و تبلیغات
     */
    public function exportAllRequests(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('product_requests.export') || !$request->user()->can('advertisement_requests.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        $type = $request->input('type', 'excel'); // نوع فایل خروجی: excel یا pdf
        
        if ($type === 'excel') {
            // خروجی Excel
            return Excel::download(new AllRequestsExport, 'all_requests.xlsx');
        } else {
            // خروجی PDF - نیازمند پیاده‌سازی مناسب از کلاس AllRequestsPdfExport
            return response()->json(['message' => 'خروجی PDF در حال حاضر پشتیبانی نمی‌شود'], 501);
        }
    }
}
