<?php

namespace App\Services;

use App\Models\Notice;
use App\Models\NoticeView;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class NoticeService
{
    // زمان ماندگاری کش به ثانیه
    const CACHE_TTL = 3600; // یک ساعت
    
    /**
     * دریافت لیست اطلاعیه‌ها با فیلتر
     *
     * @param array $filters فیلترهای موردنظر
     * @param bool $forAdmin آیا برای ادمین است؟
     * @param int $perPage تعداد آیتم در هر صفحه
     * @return LengthAwarePaginator
     */
    public function listNotices(array $filters = [], bool $forAdmin = false, int $perPage = 15): LengthAwarePaginator
    {
        try {
            $query = Notice::query();
            
            // اعمال فیلتر نوع
            if (isset($filters['type'])) {
                $query->ofType($filters['type']);
            }
            
            // اعمال فیلتر وضعیت
            if (isset($filters['status'])) {
                if ($filters['status'] === Notice::STATUS_PUBLISHED) {
                    $query->published();
                } elseif ($filters['status'] === Notice::STATUS_DRAFT) {
                    $query->draft();
                } elseif ($filters['status'] === Notice::STATUS_ARCHIVED) {
                    $query->archived();
                }
            } elseif (!$forAdmin) {
                // اگر برای ادمین نیست، فقط موارد منتشرشده نمایش داده شود
                $query->published();
            }
            
            // اعمال فیلتر کاربر (فقط اطلاعیه‌هایی که برای این کاربر قابل نمایش است)
            if (!$forAdmin && isset($filters['user_id'])) {
                $query->visibleToUser($filters['user_id']);
            }
            
            // اعمال مرتب‌سازی
            $sortBy = $filters['sort_by'] ?? 'publish_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            
            if ($sortBy === 'publish_at') {
                $query->orderBy('publish_at', $sortOrder);
            } elseif ($sortBy === 'version') {
                $query->orderBy('version', $sortOrder);
            } elseif ($sortBy === 'created_at') {
                $query->orderBy('created_at', $sortOrder);
            }
            
            // برای ادمین همیشه همه‌ی اطلاعیه‌ها برگشت داده شود
            // برای کاربر عادی، فقط موارد منتشر شده و قابل نمایش
            return $query->paginate($perPage)->withQueryString();
        } catch (Exception $e) {
            Log::error('خطا در دریافت لیست اطلاعیه‌ها: ' . $e->getMessage());
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }
    
    /**
     * دریافت یک اطلاعیه با شناسه
     *
     * @param int $id شناسه اطلاعیه
     * @param bool $forAdmin آیا برای ادمین است؟
     * @param int|null $userId شناسه کاربر برای ثبت بازدید
     * @return array|null
     */
    public function getNotice(int $id, bool $forAdmin = false, ?int $userId = null): ?array
    {
        try {
            $notice = Notice::find($id);
            
            if (!$notice) {
                return null;
            }
            
            // اگر برای ادمین نیست، فقط اطلاعیه‌های منتشر شده و قابل نمایش را برگشت دهد
            if (!$forAdmin && !$notice->isVisible()) {
                return null;
            }
            
            // اگر کاربر مشخص شده است، بررسی کنیم که آیا اطلاعیه برای این کاربر قابل نمایش است
            if (!$forAdmin && $userId && !$notice->isVisibleToUser($userId)) {
                return null;
            }
            
            // اگر کاربر مشخص شده است، بازدید را ثبت کنیم
            if ($userId && $notice->isVisible()) {
                $this->recordView($notice->id, $userId);
            }
            
            // بارگذاری روابط مورد نیاز
            $notice->load('creator', 'editor');
            
            // تبدیل به آرایه
            $result = $notice->toArray();
            
            // اضافه کردن اطلاعات اضافی
            $result['is_visible'] = $notice->isVisible();
            $result['creator_name'] = optional($notice->creator)->first_name . ' ' . optional($notice->creator)->last_name;
            $result['editor_name'] = optional($notice->editor)->first_name . ' ' . optional($notice->editor)->last_name;
            
            if ($userId) {
                $result['is_viewed'] = $this->isViewedByUser($notice->id, $userId);
            }
            
            return $result;
        } catch (Exception $e) {
            Log::error('خطا در دریافت اطلاعیه: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ایجاد اطلاعیه جدید
     *
     * @param array $data داده‌های اطلاعیه
     * @return Notice|null
     */
    public function createNotice(array $data): ?Notice
    {
        try {
            // اگر از نوع policy است و قبلا policy با همین عنوان داشتیم، نسخه را افزایش دهیم
            if ($data['type'] === Notice::TYPE_POLICY) {
                $latestPolicy = Notice::where('type', Notice::TYPE_POLICY)
                    ->where('title', $data['title'])
                    ->orderBy('version', 'desc')
                    ->first();
                
                if ($latestPolicy) {
                    $data['version'] = $latestPolicy->version + 1;
                }
            }
            
            // تبدیل target به JSON اگر آرایه است
            if (isset($data['target']) && is_array($data['target'])) {
                $data['target'] = json_encode($data['target']);
            }
            
            // تنظیم کاربر ایجاد کننده
            $data['created_by'] = Auth::id();
            
            // اگر وضعیت published است و publish_at تعیین نشده، همین الان منتشر شود
            if (($data['status'] ?? '') === Notice::STATUS_PUBLISHED && empty($data['publish_at'])) {
                $data['publish_at'] = now();
            }
            
            // ایجاد اطلاعیه
            $notice = Notice::create($data);
            
            // پاک کردن کش
            $this->forgetCache();
            
            return $notice;
        } catch (Exception $e) {
            Log::error('خطا در ایجاد اطلاعیه: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * به‌روزرسانی اطلاعیه
     *
     * @param int $id شناسه اطلاعیه
     * @param array $data داده‌های جدید
     * @return Notice|null
     */
    public function updateNotice(int $id, array $data): ?Notice
    {
        try {
            $notice = Notice::find($id);
            
            if (!$notice) {
                return null;
            }
            
            // بررسی ویژه برای قوانین منتشر شده
            if ($notice->type === Notice::TYPE_POLICY && 
                $notice->status === Notice::STATUS_PUBLISHED &&
                !empty($data['body']) && 
                $data['body'] !== $notice->body) {
                
                // برای قوانین منتشر شده، به جای ویرایش، یک نسخه جدید ایجاد می‌کنیم
                $newVersion = $notice->version + 1;
                
                $newData = array_merge($notice->toArray(), $data);
                $newData['version'] = $newVersion;
                $newData['created_by'] = $notice->created_by;
                $newData['updated_by'] = Auth::id();
                
                // ایجاد نسخه جدید
                $newNotice = $this->createNotice($newData);
                
                // پاک کردن کش
                $this->forgetCache();
                
                return $newNotice;
            }
            
            // تبدیل target به JSON اگر آرایه است
            if (isset($data['target']) && is_array($data['target'])) {
                $data['target'] = json_encode($data['target']);
            }
            
            // تنظیم کاربر ویرایش کننده
            $data['updated_by'] = Auth::id();
            
            // اگر وضعیت published است و publish_at تعیین نشده، همین الان منتشر شود
            if (isset($data['status']) && 
                $data['status'] === Notice::STATUS_PUBLISHED && 
                !isset($data['publish_at']) && 
                $notice->publish_at === null) {
                $data['publish_at'] = now();
            }
            
            // به‌روزرسانی اطلاعیه
            $notice->update($data);
            
            // پاک کردن کش
            $this->forgetCache();
            
            return $notice->fresh();
        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی اطلاعیه: ' . $e->getMessage(), [
                'notice_id' => $id,
                'data' => $data,
            ]);
            return null;
        }
    }
    
    /**
     * انتشار یک اطلاعیه
     *
     * @param int $id شناسه اطلاعیه
     * @param Carbon|null $publishAt زمان انتشار (اگر null باشد، همین الان منتشر می‌شود)
     * @return Notice|null
     */
    public function publishNotice(int $id, ?Carbon $publishAt = null): ?Notice
    {
        try {
            $notice = Notice::find($id);
            
            if (!$notice) {
                return null;
            }
            
            $data = [
                'status' => Notice::STATUS_PUBLISHED,
                'publish_at' => $publishAt ?? now(),
                'updated_by' => Auth::id(),
            ];
            
            // به‌روزرسانی اطلاعیه
            $notice->update($data);
            
            // پاک کردن کش
            $this->forgetCache();
            
            return $notice->fresh();
        } catch (Exception $e) {
            Log::error('خطا در انتشار اطلاعیه: ' . $e->getMessage(), [
                'notice_id' => $id,
            ]);
            return null;
        }
    }
    
    /**
     * آرشیو کردن یک اطلاعیه
     *
     * @param int $id شناسه اطلاعیه
     * @return Notice|null
     */
    public function archiveNotice(int $id): ?Notice
    {
        try {
            $notice = Notice::find($id);
            
            if (!$notice) {
                return null;
            }
            
            $data = [
                'status' => Notice::STATUS_ARCHIVED,
                'updated_by' => Auth::id(),
            ];
            
            // به‌روزرسانی اطلاعیه
            $notice->update($data);
            
            // پاک کردن کش
            $this->forgetCache();
            
            return $notice->fresh();
        } catch (Exception $e) {
            Log::error('خطا در آرشیو کردن اطلاعیه: ' . $e->getMessage(), [
                'notice_id' => $id,
            ]);
            return null;
        }
    }
    
    /**
     * حذف یک اطلاعیه
     *
     * @param int $id شناسه اطلاعیه
     * @return bool
     */
    public function deleteNotice(int $id): bool
    {
        try {
            $notice = Notice::find($id);
            
            if (!$notice) {
                return false;
            }
            
            // حذف بازدیدها
            NoticeView::where('notice_id', $id)->delete();
            
            // حذف اطلاعیه
            $notice->delete();
            
            // پاک کردن کش
            $this->forgetCache();
            
            return true;
        } catch (Exception $e) {
            Log::error('خطا در حذف اطلاعیه: ' . $e->getMessage(), [
                'notice_id' => $id,
            ]);
            return false;
        }
    }
    
    /**
     * ثبت بازدید یک اطلاعیه توسط کاربر
     *
     * @param int $noticeId شناسه اطلاعیه
     * @param int $userId شناسه کاربر
     * @return bool
     */
    public function recordView(int $noticeId, int $userId): bool
    {
        try {
            // بررسی اینکه آیا قبلا بازدید شده است
            $exists = NoticeView::where('notice_id', $noticeId)
                ->where('user_id', $userId)
                ->exists();
            
            if ($exists) {
                return true;
            }
            
            // ثبت بازدید
            NoticeView::create([
                'notice_id' => $noticeId,
                'user_id' => $userId,
                'viewed_at' => now(),
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('خطا در ثبت بازدید اطلاعیه: ' . $e->getMessage(), [
                'notice_id' => $noticeId,
                'user_id' => $userId,
            ]);
            return false;
        }
    }
    
    /**
     * بررسی اینکه آیا اطلاعیه توسط کاربر بازدید شده است
     *
     * @param int $noticeId شناسه اطلاعیه
     * @param int $userId شناسه کاربر
     * @return bool
     */
    public function isViewedByUser(int $noticeId, int $userId): bool
    {
        return NoticeView::where('notice_id', $noticeId)
            ->where('user_id', $userId)
            ->exists();
    }
    
    /**
     * دریافت تعداد اطلاعیه‌های خوانده نشده برای یک کاربر
     *
     * @param int $userId شناسه کاربر
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            // دریافت اطلاعیه‌های قابل نمایش برای کاربر
            $visibleNotices = Notice::published()
                ->visibleToUser($userId)
                ->get();
            
            if ($visibleNotices->isEmpty()) {
                return 0;
            }
            
            // شناسه‌های اطلاعیه‌های قابل نمایش
            $noticeIds = $visibleNotices->pluck('id')->toArray();
            
            // شناسه‌های اطلاعیه‌های خوانده شده توسط کاربر
            $viewedIds = NoticeView::where('user_id', $userId)
                ->whereIn('notice_id', $noticeIds)
                ->pluck('notice_id')
                ->toArray();
            
            // تعداد اطلاعیه‌های خوانده نشده
            return count(array_diff($noticeIds, $viewedIds));
        } catch (Exception $e) {
            Log::error('خطا در دریافت تعداد اطلاعیه‌های خوانده نشده: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);
            return 0;
        }
    }
    
    /**
     * پاک کردن کش
     */
    private function forgetCache(): void
    {
        Cache::forget('published_notices');
    }
} 