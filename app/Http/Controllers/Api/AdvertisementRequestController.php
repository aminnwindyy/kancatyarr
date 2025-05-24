<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvertisementRequest;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use App\Events\AdvertisementRequestUpdated;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdvertisementRequestsExport;
use Illuminate\Support\Facades\Mail;

class AdvertisementRequestController extends Controller
{
    /**
     * نمایش لیست درخواست‌های تبلیغات
     */
    public function index(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('advertisement_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // فیلترها
        $status = $request->input('status');
        $period = $request->input('period'); // daily, weekly, monthly
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = AdvertisementRequest::query();

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
        $advertisementRequests = $query->with([
                'serviceProvider:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        // تهیه پاسخ
        $result = [];
        
        foreach ($advertisementRequests as $request) {
            $result[] = [
                'id' => $request->id,
                'service_provider_name' => $request->serviceProvider->name ?? 'نامشخص',
                'title' => $request->title,
                'start_date' => $request->start_date->format('Y-m-d'),
                'end_date' => $request->end_date->format('Y-m-d'),
                'is_featured' => $request->is_featured,
                'status' => $request->status,
                'created_at' => $request->created_at->format('Y-m-d')
            ];
        }

        return response()->json([
            'data' => $result,
            'total_pages' => $advertisementRequests->lastPage(),
            'current_page' => $advertisementRequests->currentPage()
        ]);
    }

    /**
     * نمایش جزئیات درخواست تبلیغات
     */
    public function show(Request $request, $requestId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('advertisement_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // یافتن درخواست
        $advertisementRequest = AdvertisementRequest::with([
                'serviceProvider:id,name,email'
            ])
            ->findOrFail($requestId);

        return response()->json([
            'advertisement_request' => [
                'id' => $advertisementRequest->id,
                'service_provider_name' => $advertisementRequest->serviceProvider->name ?? 'نامشخص',
                'service_provider_email' => $advertisementRequest->serviceProvider->email ?? '',
                'title' => $advertisementRequest->title,
                'description' => $advertisementRequest->description,
                'image_path' => $advertisementRequest->image_path,
                'start_date' => $advertisementRequest->start_date->format('Y-m-d'),
                'end_date' => $advertisementRequest->end_date->format('Y-m-d'),
                'price' => $advertisementRequest->price,
                'is_featured' => $advertisementRequest->is_featured,
                'status' => $advertisementRequest->status,
                'rejection_reason' => $advertisementRequest->rejection_reason,
                'created_at' => $advertisementRequest->created_at->format('Y-m-d')
            ]
        ]);
    }

    /**
     * تایید/رد درخواست تبلیغات
     */
    public function approve(Request $request, $requestId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('advertisement_requests.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // اعتبارسنجی داده‌ها
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'description' => 'nullable|string|max:500',
        ]);

        // یافتن درخواست
        $advertisementRequest = AdvertisementRequest::with(['serviceProvider'])
            ->findOrFail($requestId);

        // به‌روزرسانی وضعیت
        $advertisementRequest->status = $validated['status'];
        if (isset($validated['description'])) {
            $advertisementRequest->rejection_reason = $validated['description'];
        }
        $advertisementRequest->save();

        // ایجاد تبلیغات جدید در صورت تایید
        if ($validated['status'] === 'approved') {
            $advertisement = new Advertisement([
                'service_provider_id' => $advertisementRequest->service_provider_id,
                'title' => $advertisementRequest->title,
                'description' => $advertisementRequest->description,
                'image_path' => $advertisementRequest->image_path,
                'start_date' => $advertisementRequest->start_date,
                'end_date' => $advertisementRequest->end_date,
                'price' => $advertisementRequest->price,
                'is_featured' => $advertisementRequest->is_featured,
                'is_active' => true
            ]);
            $advertisement->save();
        }

        // ارسال اعلان به خدمات‌دهنده
        try {
            if ($advertisementRequest->serviceProvider && $advertisementRequest->serviceProvider->email) {
                // ارسال ایمیل به خدمات‌دهنده
                // Mail::to($advertisementRequest->serviceProvider->email)->send(new AdvertisementRequestStatusNotification($advertisementRequest));
            }
        } catch (\Exception $e) {
            // ثبت خطای ارسال ایمیل
            \Log::error('خطای ارسال ایمیل: ' . $e->getMessage());
        }

        // ارسال رویداد به‌روزرسانی درخواست تبلیغات
        event(new AdvertisementRequestUpdated($advertisementRequest));

        return response()->json([
            'message' => 'وضعیت درخواست تبلیغات با موفقیت تغییر کرد.'
        ]);
    }

    /**
     * نمایش درخواست‌های تبلیغات در انتظار
     */
    public function pendingRequests(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('advertisement_requests.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // دریافت درخواست‌های تبلیغات در انتظار
        $pendingRequests = AdvertisementRequest::where('status', 'pending')
            ->with(['serviceProvider:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('limit', 10));

        // تهیه پاسخ
        $result = [];
        
        foreach ($pendingRequests as $request) {
            $result[] = [
                'id' => $request->id,
                'service_provider_name' => $request->serviceProvider->name ?? 'نامشخص',
                'ad_title' => $request->title,
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
     * صدور گزارش درخواست‌های تبلیغات
     */
    public function exportAdvertisementRequests(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('advertisement_requests.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        return Excel::download(new AdvertisementRequestsExport, 'advertisement_requests.xlsx');
    }
}
