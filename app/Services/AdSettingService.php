<?php

namespace App\Services;

use App\Models\AdSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AdSettingService
{
    // زمان ماندگاری تنظیمات در کش (به ثانیه)
    const CACHE_TTL = 3600; // یک ساعت

    /**
     * دریافت تمام تنظیمات تبلیغات با فیلتر اختیاری
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSettings(array $filters = [])
    {
        try {
            $cacheKey = 'ad_settings_all';
            
            // اگر فیلتر وجود دارد، از کش استفاده نمی‌کنیم
            if (!empty($filters)) {
                return $this->getFilteredSettings($filters);
            }
            
            // ابتدا بررسی کنیم آیا در کش موجود است
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // اگر در کش نبود، از دیتابیس می‌خوانیم
            $settings = AdSetting::orderBy('order')->get();
            
            // ذخیره در کش برای دفعات بعدی
            Cache::put($cacheKey, $settings, self::CACHE_TTL);

            return $settings;
        } catch (Exception $e) {
            Log::error('خطا در دریافت تنظیمات تبلیغات: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * دریافت تنظیمات تبلیغات با فیلتر
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFilteredSettings(array $filters)
    {
        try {
            $query = AdSetting::query();

            // فیلتر بر اساس محل نمایش
            if (isset($filters['placement'])) {
                $query->byPlacement($filters['placement']);
            }

            // فیلتر بر اساس سرویس
            if (isset($filters['service'])) {
                $query->byService($filters['service']);
            }

            // فیلتر فقط آیتم‌های فعال
            if (isset($filters['active_only']) && $filters['active_only']) {
                $query->active();
            }

            // مرتب‌سازی بر اساس ترتیب نمایش
            $query->ordered();

            return $query->get();
        } catch (Exception $e) {
            Log::error('خطا در دریافت تنظیمات تبلیغات با فیلتر: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * دریافت یک تنظیم با شناسه
     *
     * @param int $id
     * @return AdSetting|null
     */
    public function getById(int $id)
    {
        return AdSetting::find($id);
    }

    /**
     * ایجاد تنظیم جدید
     *
     * @param array $data
     * @param int $adminId
     * @return AdSetting
     * @throws \Exception
     */
    public function create(array $data, int $adminId)
    {
        try {
            // ایجاد تنظیم جدید
            $adSetting = new AdSetting();
            $adSetting->service = $data['service'];
            $adSetting->placement = $data['placement'];
            $adSetting->position_id = $data['position_id'];
            $adSetting->order = $data['order'];
            $adSetting->is_active = $data['is_active'] ?? true;
            $adSetting->config = $data['config'] ?? null;
            $adSetting->created_by = $adminId;
            $adSetting->save();

            // پاک کردن کش
            $this->forgetCache();

            return $adSetting;
        } catch (Exception $e) {
            Log::error('خطا در ایجاد تنظیم تبلیغات: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * به‌روزرسانی تنظیم تبلیغات
     *
     * @param int $id
     * @param array $data
     * @param int $adminId
     * @return AdSetting
     * @throws \Exception
     */
    public function update(int $id, array $data, int $adminId)
    {
        try {
            $adSetting = AdSetting::findOrFail($id);
            
            // ذخیره مقادیر قبلی برای لاگ تغییرات
            $oldValues = [
                'service' => $adSetting->service,
                'placement' => $adSetting->placement,
                'position_id' => $adSetting->position_id,
                'order' => $adSetting->order,
                'is_active' => $adSetting->is_active,
                'config' => $adSetting->config,
            ];

            // به‌روزرسانی فیلدها
            $adSetting->placement = $data['placement'] ?? $adSetting->placement;
            $adSetting->position_id = $data['position_id'] ?? $adSetting->position_id;
            $adSetting->order = $data['order'] ?? $adSetting->order;
            
            // بررسی اینکه آیا وضعیت فعال/غیرفعال ارسال شده است
            if (isset($data['is_active'])) {
                $adSetting->is_active = (bool) $data['is_active'];
            }
            
            // به‌روزرسانی تنظیمات اضافی
            if (isset($data['config'])) {
                $adSetting->config = $data['config'];
            }
            
            $adSetting->save();

            // ثبت لاگ تغییرات
            $newValues = [
                'service' => $adSetting->service,
                'placement' => $adSetting->placement,
                'position_id' => $adSetting->position_id,
                'order' => $adSetting->order,
                'is_active' => $adSetting->is_active,
                'config' => $adSetting->config,
            ];
            
            $adSetting->logChanges($oldValues, $newValues, $adminId);

            // پاک کردن کش
            $this->forgetCache();

            return $adSetting;
        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی تنظیم تبلیغات: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * حذف تنظیم تبلیغات
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id)
    {
        try {
            $adSetting = AdSetting::findOrFail($id);
            $adSetting->delete();

            // پاک کردن کش
            $this->forgetCache();

            return true;
        } catch (Exception $e) {
            Log::error('خطا در حذف تنظیم تبلیغات: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     *
     * @param int $id
     * @param bool $isActive
     * @param int $adminId
     * @return AdSetting
     * @throws \Exception
     */
    public function toggleStatus(int $id, bool $isActive, int $adminId)
    {
        try {
            $adSetting = AdSetting::findOrFail($id);
            
            // ذخیره مقدار قبلی
            $oldValues = ['is_active' => $adSetting->is_active];
            
            // تغییر وضعیت
            $adSetting->is_active = $isActive;
            $adSetting->save();

            // ثبت لاگ تغییرات
            $newValues = ['is_active' => $adSetting->is_active];
            $adSetting->logChanges($oldValues, $newValues, $adminId);

            // پاک کردن کش
            $this->forgetCache();

            return $adSetting;
        } catch (Exception $e) {
            Log::error('خطا در تغییر وضعیت تنظیم تبلیغات: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * پاک کردن کش
     *
     * @return void
     */
    private function forgetCache()
    {
        Cache::forget('ad_settings_all');
    }
} 