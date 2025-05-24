<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ServiceProviderReview;
use App\Models\ServiceProviderReviewLog;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ReviewService
{
    /**
     * ثبت نظر جدید برای خدمات‌دهنده
     *
     * @param int $userId آیدی کاربر
     * @param int $orderId آیدی سفارش
     * @param int $rating امتیاز (۱ تا ۵)
     * @param string|null $comment نظر کاربر
     * @return array
     */
    public function submitReview(int $userId, int $orderId, int $rating, ?string $comment = null): array
    {
        try {
            // بررسی امکان ثبت نظر
            if (!ServiceProviderReview::canSubmitReview($orderId, $userId)) {
                return [
                    'status' => false,
                    'message' => 'امکان ثبت نظر برای این سفارش وجود ندارد. سفارش باید تکمیل شده باشد و قبلاً نظری ثبت نشده باشد.',
                ];
            }

            // دریافت اطلاعات سفارش
            $order = Order::where('order_id', $orderId)
                ->where('user_id', $userId)
                ->first();

            if (!$order) {
                return [
                    'status' => false,
                    'message' => 'سفارش مورد نظر یافت نشد.',
                ];
            }

            $serviceProviderId = $order->service_provider_id;

            // ایجاد رکورد نظر جدید
            $review = new ServiceProviderReview();
            $review->order_id = $orderId;
            $review->user_id = $userId;
            $review->service_provider_id = $serviceProviderId;
            $review->rating = $rating;
            $review->comment = $comment;
            $review->status = 'pending';
            $review->save();

            Log::info("نظر جدید برای خدمات‌دهنده {$serviceProviderId} توسط کاربر {$userId} ثبت شد.");

            return [
                'status' => true,
                'message' => 'نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.',
                'data' => $review,
            ];

        } catch (Exception $e) {
            Log::error('خطا در ثبت نظر: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'خطایی در ثبت نظر رخ داد. لطفاً دوباره تلاش کنید.',
            ];
        }
    }

    /**
     * دریافت لیست نظرات با امکان فیلتر
     *
     * @param array $filterParams پارامترهای فیلتر
     * @return LengthAwarePaginator
     */
    public function listReviews(array $filterParams = []): LengthAwarePaginator
    {
        $query = ServiceProviderReview::with(['user', 'serviceProvider', 'order']);

        // اعمال فیلترها
        if (isset($filterParams['status']) && in_array($filterParams['status'], ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filterParams['status']);
        }

        if (isset($filterParams['provider_id'])) {
            $query->where('service_provider_id', $filterParams['provider_id']);
        }

        if (isset($filterParams['date_from'])) {
            $query->whereDate('created_at', '>=', $filterParams['date_from']);
        }

        if (isset($filterParams['date_to'])) {
            $query->whereDate('created_at', '<=', $filterParams['date_to']);
        }

        if (isset($filterParams['rating'])) {
            $query->where('rating', $filterParams['rating']);
        }

        // مرتب‌سازی
        $sortBy = $filterParams['sort_by'] ?? 'created_at';
        $sortOrder = $filterParams['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // صفحه‌بندی
        $perPage = $filterParams['per_page'] ?? 15;
        $page = $filterParams['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * دریافت جزئیات یک نظر
     *
     * @param int $reviewId آیدی نظر
     * @return array
     */
    public function getReviewDetails(int $reviewId): array
    {
        try {
            $review = ServiceProviderReview::with(['user', 'serviceProvider', 'order', 'statusLogs.changedBy'])
                ->findOrFail($reviewId);

            return [
                'status' => true,
                'data' => $review,
            ];

        } catch (Exception $e) {
            Log::error('خطا در دریافت جزئیات نظر: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'نظر مورد نظر یافت نشد.',
            ];
        }
    }

    /**
     * تایید نظر توسط ادمین
     *
     * @param int $reviewId آیدی نظر
     * @param int $adminId آیدی ادمین
     * @return array
     */
    public function approveReview(int $reviewId, int $adminId): array
    {
        try {
            return DB::transaction(function () use ($reviewId, $adminId) {
                $review = ServiceProviderReview::findOrFail($reviewId);

                if ($review->status === 'approved') {
                    return [
                        'status' => false,
                        'message' => 'این نظر قبلاً تایید شده است.',
                    ];
                }

                $oldStatus = $review->status;
                $review->status = 'approved';
                $review->admin_id = $adminId;
                $review->save();

                // ثبت لاگ تغییر وضعیت
                $log = new ServiceProviderReviewLog();
                $log->review_id = $reviewId;
                $log->old_status = $oldStatus;
                $log->new_status = 'approved';
                $log->changed_by = $adminId;
                $log->changed_at = now();
                $log->save();

                // به‌روزرسانی امتیاز خدمات‌دهنده
                $this->updateServiceProviderRating($review->service_provider_id);

                Log::info("نظر {$reviewId} توسط ادمین {$adminId} تایید شد.");

                return [
                    'status' => true,
                    'message' => 'نظر با موفقیت تایید شد.',
                    'data' => $review,
                ];
            });

        } catch (Exception $e) {
            Log::error('خطا در تایید نظر: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'خطایی در تایید نظر رخ داد. لطفاً دوباره تلاش کنید.',
            ];
        }
    }

    /**
     * رد نظر توسط ادمین
     *
     * @param int $reviewId آیدی نظر
     * @param int $adminId آیدی ادمین
     * @param string|null $reason دلیل رد نظر
     * @return array
     */
    public function rejectReview(int $reviewId, int $adminId, ?string $reason = null): array
    {
        try {
            return DB::transaction(function () use ($reviewId, $adminId, $reason) {
                $review = ServiceProviderReview::findOrFail($reviewId);

                if ($review->status === 'rejected') {
                    return [
                        'status' => false,
                        'message' => 'این نظر قبلاً رد شده است.',
                    ];
                }

                $oldStatus = $review->status;
                $review->status = 'rejected';
                $review->rejection_reason = $reason;
                $review->admin_id = $adminId;
                $review->save();

                // ثبت لاگ تغییر وضعیت
                $log = new ServiceProviderReviewLog();
                $log->review_id = $reviewId;
                $log->old_status = $oldStatus;
                $log->new_status = 'rejected';
                $log->changed_by = $adminId;
                $log->changed_at = now();
                $log->note = $reason;
                $log->save();

                Log::info("نظر {$reviewId} توسط ادمین {$adminId} رد شد.");

                return [
                    'status' => true,
                    'message' => 'نظر با موفقیت رد شد.',
                    'data' => $review,
                ];
            });

        } catch (Exception $e) {
            Log::error('خطا در رد نظر: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'خطایی در رد نظر رخ داد. لطفاً دوباره تلاش کنید.',
            ];
        }
    }

    /**
     * به‌روزرسانی امتیاز خدمات‌دهنده بر اساس نظرات تایید شده
     *
     * @param int $serviceProviderId آیدی خدمات‌دهنده
     * @return float
     */
    private function updateServiceProviderRating(int $serviceProviderId): float
    {
        try {
            $avgRating = ServiceProviderReview::where('service_provider_id', $serviceProviderId)
                ->where('status', 'approved')
                ->avg('rating') ?: 0;

            $serviceProvider = ServiceProvider::findOrFail($serviceProviderId);
            $serviceProvider->rating = round($avgRating, 1);
            $serviceProvider->save();

            return $serviceProvider->rating;
        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی امتیاز خدمات‌دهنده: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * دریافت نظرات تایید شده یک خدمات‌دهنده
     * 
     * @param int $serviceProviderId آیدی خدمات‌دهنده
     * @param array $options گزینه‌های اضافی
     * @return LengthAwarePaginator
     */
    public function getProviderApprovedReviews(int $serviceProviderId, array $options = []): LengthAwarePaginator
    {
        $perPage = $options['per_page'] ?? 10;
        $sortBy = $options['sort_by'] ?? 'created_at';
        $sortOrder = $options['sort_order'] ?? 'desc';

        return ServiceProviderReview::with(['user'])
            ->where('service_provider_id', $serviceProviderId)
            ->where('status', 'approved')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }
} 