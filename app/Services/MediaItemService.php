<?php

namespace App\Services;

use App\Models\MediaItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class MediaItemService
{
    /**
     * زمان کش (به ثانیه)
     */
    const CACHE_TTL = 3600; // یک ساعت

    /**
     * دریافت فهرست آیتم‌ها با فیلتر نوع
     *
     * @param string|null $type
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function list(?string $type = null, array $filters = [])
    {
        $cacheKey = "media_items" . ($type ? "_{$type}" : "_all");
        
        if (isset($filters['position'])) {
            $cacheKey .= "_{$filters['position']}";
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type, $filters) {
            $query = MediaItem::query();

            // فیلتر بر اساس نوع
            if ($type) {
                $query->ofType($type);
            }

            // فیلتر فقط آیتم‌های فعال
            if (isset($filters['active_only']) && $filters['active_only']) {
                $query->currentlyActive();  // از اسکوپ جدید استفاده می‌کنیم
            }
            
            // فیلتر براساس موقعیت
            if (isset($filters['position'])) {
                $query->ofPosition($filters['position']);
            }

            // مرتب‌سازی بر اساس ترتیب نمایش
            $query->ordered();

            return $query->get();
        });
    }

    /**
     * دریافت بنرهای فعال براساس موقعیت
     *
     * @param string $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveByPosition(string $position)
    {
        $cacheKey = "media_items_banner_{$position}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($position) {
            return MediaItem::ofType('banner')
                ->ofPosition($position)
                ->currentlyActive()
                ->ordered()
                ->get();
        });
    }

    /**
     * دریافت یک آیتم با شناسه
     *
     * @param int $id
     * @return MediaItem|null
     */
    public function getById(int $id)
    {
        return MediaItem::find($id);
    }

    /**
     * ایجاد آیتم جدید
     *
     * @param array $data
     * @param UploadedFile|null $file
     * @return MediaItem
     * @throws \Exception
     */
    public function create(array $data, ?UploadedFile $file)
    {
        try {
            // آپلود تصویر اگر وجود داشته باشد
            $imagePath = null;
            if ($file) {
                $imagePath = $this->uploadImage($file, $data['type']);
            }

            // ایجاد آیتم جدید
            $mediaItem = new MediaItem();
            $mediaItem->type = $data['type'];
            $mediaItem->title = $data['title'];
            $mediaItem->image_path = $imagePath;
            $mediaItem->link = $data['link'] ?? null;
            $mediaItem->order = $data['order'];
            $mediaItem->is_active = $data['is_active'] ?? true;
            $mediaItem->position = $data['position'] ?? 'top';
            $mediaItem->provider = $data['provider'] ?? 'custom';
            $mediaItem->script_code = $data['script_code'] ?? null;
            $mediaItem->start_date = $data['start_date'] ?? null;
            $mediaItem->end_date = $data['end_date'] ?? null;
            $mediaItem->created_by = Auth::id();
            $mediaItem->save();

            // پاک کردن کش
            $this->forgetCache($data['type'], $data['position'] ?? null);

            return $mediaItem;
        } catch (Exception $e) {
            Log::error('خطا در ایجاد آیتم رسانه: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * به‌روزرسانی آیتم
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $file
     * @return MediaItem
     * @throws \Exception
     */
    public function update(int $id, array $data, ?UploadedFile $file = null)
    {
        try {
            $mediaItem = MediaItem::findOrFail($id);
            $oldPosition = $mediaItem->position;

            // آپلود تصویر جدید اگر وجود داشته باشد
            if ($file) {
                // حذف تصویر قبلی
                $this->deleteImage($mediaItem->image_path);

                // آپلود تصویر جدید
                $imagePath = $this->uploadImage($file, $mediaItem->type);
                $mediaItem->image_path = $imagePath;
            }

            // به‌روزرسانی سایر فیلدها
            $mediaItem->title = $data['title'];
            $mediaItem->link = $data['link'] ?? $mediaItem->link;
            $mediaItem->order = $data['order'];
            
            // بررسی اینکه آیا وضعیت فعال/غیرفعال ارسال شده است
            if (isset($data['is_active'])) {
                $mediaItem->is_active = (bool) $data['is_active'];
            }
            
            // بروزرسانی فیلدهای جدید
            if (isset($data['position'])) {
                $mediaItem->position = $data['position'];
            }
            
            if (isset($data['provider'])) {
                $mediaItem->provider = $data['provider'];
            }
            
            if (isset($data['script_code'])) {
                $mediaItem->script_code = $data['script_code'];
            }
            
            if (isset($data['start_date'])) {
                $mediaItem->start_date = $data['start_date'];
            }
            
            if (isset($data['end_date'])) {
                $mediaItem->end_date = $data['end_date'];
            }
            
            $mediaItem->save();

            // پاک کردن کش
            $this->forgetCache($mediaItem->type, $mediaItem->position);
            if ($oldPosition !== $mediaItem->position) {
                $this->forgetCache($mediaItem->type, $oldPosition);
            }

            return $mediaItem;
        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی آیتم رسانه: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * حذف آیتم
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id)
    {
        try {
            $mediaItem = MediaItem::findOrFail($id);
            
            // حذف تصویر
            $this->deleteImage($mediaItem->image_path);
            
            // حذف آیتم
            $type = $mediaItem->type;
            $position = $mediaItem->position;
            $mediaItem->delete();

            // پاک کردن کش
            $this->forgetCache($type, $position);

            return true;
        } catch (Exception $e) {
            Log::error('خطا در حذف آیتم رسانه: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     *
     * @param int $id
     * @param bool $isActive
     * @return MediaItem
     * @throws \Exception
     */
    public function toggleStatus(int $id, bool $isActive)
    {
        try {
            $mediaItem = MediaItem::findOrFail($id);
            $mediaItem->is_active = $isActive;
            $mediaItem->save();

            // پاک کردن کش
            $this->forgetCache($mediaItem->type, $mediaItem->position);

            return $mediaItem;
        } catch (Exception $e) {
            Log::error('خطا در تغییر وضعیت آیتم رسانه: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * آپلود تصویر
     *
     * @param UploadedFile $file
     * @param string $type
     * @return string
     */
    private function uploadImage(UploadedFile $file, string $type)
    {
        // ایجاد نام یکتا برای فایل
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // مسیر ذخیره
        $path = "media/{$type}s";
        
        // آپلود فایل
        return $file->storeAs($path, $fileName, 'public');
    }

    /**
     * حذف تصویر
     *
     * @param string|null $path
     * @return bool
     */
    private function deleteImage(?string $path)
    {
        // اگر مسیر تهی باشد یا تصویر خارجی است، نیازی به حذف نیست
        if (!$path || strpos($path, 'http') === 0) {
            return true;
        }
        
        // حذف فایل از استوریج
        return Storage::disk('public')->delete($path);
    }

    /**
     * پاک کردن کش
     *
     * @param string|null $type
     * @param string|null $position
     * @return void
     */
    private function forgetCache(?string $type = null, ?string $position = null)
    {
        Cache::forget("media_items" . ($type ? "_{$type}" : "_all"));
        
        if ($position) {
            Cache::forget("media_items" . ($type ? "_{$type}" : "_all") . "_{$position}");
            Cache::forget("media_items_{$type}_{$position}");
        }
        
        Cache::forget("media_items");
    }
} 