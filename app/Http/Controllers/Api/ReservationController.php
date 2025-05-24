<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReservationsExport;

class ReservationController extends Controller
{
    /**
     * نمایش لیست رزروها
     */
    public function index(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('reservations.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // فیلترها
        $status = $request->input('filter');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        
        $query = Reservation::query();

        // فیلتر براساس وضعیت
        if ($status) {
            $query->ofStatus($status);
        }

        // دریافت رزروها با اطلاعات مربوطه
        $reservations = $query->with([
                'user:user_id,name',
                'serviceProvider:id,name'
            ])
            ->orderBy('reservation_date', 'desc')
            ->paginate($limit);

        // تهیه پاسخ
        $result = [];
        
        foreach ($reservations as $reservation) {
            $result[] = [
                'id' => $reservation->id,
                'user_name' => $reservation->user->name ?? 'نامشخص',
                'service_provider_name' => $reservation->serviceProvider->name ?? 'نامشخص',
                'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
                'status' => $reservation->status
            ];
        }

        return response()->json([
            'data' => $result,
            'total_pages' => $reservations->lastPage(),
            'current_page' => $reservations->currentPage()
        ]);
    }

    /**
     * نمایش جزئیات رزرو
     */
    public function show(Request $request, $reservationId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('reservations.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
    }

        // یافتن رزرو
        $reservation = Reservation::with([
                'user:user_id,name,email,phone',
                'serviceProvider:id,name,email'
            ])
            ->findOrFail($reservationId);

        return response()->json([
            'reservation' => [
                'id' => $reservation->id,
                'user_name' => $reservation->user->name ?? 'نامشخص',
                'user_email' => $reservation->user->email ?? '',
                'user_phone' => $reservation->user->phone ?? '',
                'service_provider_name' => $reservation->serviceProvider->name ?? 'نامشخص',
                'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
                'reservation_time' => $reservation->reservation_time,
                'number_of_people' => $reservation->number_of_people,
                'status' => $reservation->status,
                'description' => $reservation->description,
                'rejection_reason' => $reservation->rejection_reason,
                'price' => $reservation->price,
                'is_paid' => $reservation->is_paid,
                'created_at' => $reservation->created_at->format('Y-m-d')
            ]
        ]);
    }

    /**
     * تایید/رد رزرو
     */
    public function approve(Request $request, $reservationId)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('reservations.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // اعتبارسنجی داده‌ها
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'description' => 'nullable|string|max:500',
        ]);

        // یافتن رزرو
        $reservation = Reservation::with(['user', 'serviceProvider'])
            ->findOrFail($reservationId);

        // به‌روزرسانی وضعیت
        $reservation->status = $validated['status'];
        if (isset($validated['description'])) {
            $reservation->rejection_reason = $validated['description'];
        }
        $reservation->save();

        // ارسال اعلان به کاربر و خدمات‌دهنده
        try {
            if ($reservation->user && $reservation->user->email) {
                // ارسال ایمیل به کاربر
                // Mail::to($reservation->user->email)->send(new ReservationStatusNotification($reservation));
                
                // در اینجا می‌توان از ایمیل سفارشی یا سیستم اعلان‌های دیگری استفاده کرد
            }
            
            if ($reservation->serviceProvider && $reservation->serviceProvider->email && $validated['status'] === 'approved') {
                // ارسال ایمیل به خدمات‌دهنده در صورت تایید رزرو
                // Mail::to($reservation->serviceProvider->email)->send(new NewReservationNotification($reservation));
            }
        } catch (\Exception $e) {
            // ثبت خطای ارسال ایمیل
            \Log::error('خطای ارسال ایمیل: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'وضعیت رزرو با موفقیت تغییر کرد.'
        ]);
    }

    /**
     * صدور گزارش رزروها
     */
    public function exportReservations(Request $request)
    {
        // بررسی مجوز دسترسی
        if (!$request->user()->can('reservations.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        return Excel::download(new ReservationsExport, 'reservations.xlsx');
    }
}
