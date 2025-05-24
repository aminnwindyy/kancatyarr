<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PurchasesExport;

class PurchaseController extends Controller
{
    /**
     * نمایش لیست خریدهای کاربران
     */
    public function index(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('purchases.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // فیلترها
        $status = $request->input('filter');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = Purchase::query();

        // فیلتر براساس وضعیت
        if ($status) {
            $query->ofStatus($status);
        }

        // دریافت خریدها با اطلاعات مربوطه
        $purchases = $query->with([
                'user:user_id,name',
                'product:product_id,name',
                'serviceProvider:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        // تهیه پاسخ
        $result = [];
        
        foreach ($purchases as $purchase) {
            $result[] = [
                'id' => $purchase->id,
                'user_name' => $purchase->user->name ?? 'نامشخص',
                'product_name' => $purchase->product->name ?? 'نامشخص',
                'service_provider_name' => $purchase->serviceProvider->name ?? 'نامشخص',
                'status' => $purchase->status,
                'created_at' => $purchase->created_at->format('Y-m-d')
            ];
        }

        return response()->json([
            'data' => $result,
            'total_pages' => $purchases->lastPage(),
            'current_page' => $purchases->currentPage()
        ]);
    }

    /**
     * نمایش جزئیات خرید
     */
    public function show(Request $request, $purchaseId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('purchases.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // یافتن خرید
        $purchase = Purchase::with([
                'user:user_id,name,email,phone',
                'product:product_id,name,description,price',
                'serviceProvider:id,name,email'
            ])
            ->findOrFail($purchaseId);

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'user_name' => $purchase->user->name ?? 'نامشخص',
                'user_email' => $purchase->user->email ?? '',
                'user_phone' => $purchase->user->phone ?? '',
                'product_name' => $purchase->product->name ?? 'نامشخص',
                'product_description' => $purchase->product->description ?? '',
                'price' => $purchase->price,
                'quantity' => $purchase->quantity,
                'total_price' => $purchase->price * $purchase->quantity,
                'service_provider_name' => $purchase->serviceProvider->name ?? 'نامشخص',
                'status' => $purchase->status,
                'description' => $purchase->description,
                'rejection_reason' => $purchase->rejection_reason,
                'created_at' => $purchase->created_at->format('Y-m-d')
            ]
        ]);
    }

    /**
     * تایید/رد خرید
     */
    public function approve(Request $request, $purchaseId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('purchases.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // اعتبارسنجی داده‌ها
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'description' => 'nullable|string|max:500',
        ]);

        // یافتن خرید
        $purchase = Purchase::with(['user', 'product', 'serviceProvider'])
            ->findOrFail($purchaseId);

        // به‌روزرسانی وضعیت
        $purchase->status = $validated['status'];
        if (isset($validated['description'])) {
            $purchase->rejection_reason = $validated['description'];
        }
        $purchase->save();

        // ارسال اعلان به کاربر و خدمات‌دهنده
        try {
            if ($purchase->user && $purchase->user->email) {
                // ارسال ایمیل به کاربر
                // Mail::to($purchase->user->email)->send(new PurchaseStatusNotification($purchase));
                
                // در اینجا می‌توان از ایمیل سفارشی یا سیستم اعلان‌های دیگری استفاده کرد
            }
            
            if ($purchase->serviceProvider && $purchase->serviceProvider->email && $validated['status'] === 'approved') {
                // ارسال ایمیل به خدمات‌دهنده در صورت تایید خرید
                // Mail::to($purchase->serviceProvider->email)->send(new NewPurchaseNotification($purchase));
            }
        } catch (\Exception $e) {
            // ثبت خطای ارسال ایمیل
            \Log::error('خطای ارسال ایمیل: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'وضعیت خرید با موفقیت تغییر کرد.'
        ]);
    }

    /**
     * صدور گزارش خریدها
     */
    public function exportPurchases(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('purchases.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        return Excel::download(new PurchasesExport, 'purchases.xlsx');
    }
} 