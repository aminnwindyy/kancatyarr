<?php

namespace App\Services;

use App\Models\ChatSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatSettingService
{
    // زمان ماندگاری تنظیمات در کش (به ثانیه)
    const CACHE_TTL = 3600; // یک ساعت

    /**
     * دریافت تمام تنظیمات چت
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        try {
            // ابتدا بررسی کنیم آیا در کش موجود است
            if (Cache::has('chat_settings')) {
                return Cache::get('chat_settings');
            }

            // اگر در کش نبود، از دیتابیس می‌خوانیم
            $settings = ChatSetting::all();
            $formattedSettings = [];

            foreach ($settings as $setting) {
                $formattedSettings[$setting->key] = $setting->typed_value;
            }

            // ذخیره در کش برای دفعات بعدی
            Cache::put('chat_settings', $formattedSettings, self::CACHE_TTL);

            return $formattedSettings;
        } catch (Exception $e) {
            Log::error('خطا در دریافت تنظیمات چت: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * به‌روزرسانی تنظیمات چت
     *
     * @param array $settings
     * @param int $adminId
     * @return array
     */
    public function updateSettings(array $settings, int $adminId): array
    {
        try {
            $updated = [];
            $errors = [];

            foreach ($settings as $key => $value) {
                $result = ChatSetting::setValueByKey($key, $value, $adminId);
                if ($result) {
                    $updated[] = $key;
                } else {
                    $errors[] = "تنظیم '{$key}' یافت نشد.";
                }
            }

            // پاک کردن کش برای به‌روزرسانی
            Cache::forget('chat_settings');

            return [
                'status' => true,
                'message' => 'تنظیمات با موفقیت به‌روزرسانی شدند.',
                'updated' => $updated,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            Log::error('خطا در به‌روزرسانی تنظیمات چت: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'خطا در به‌روزرسانی تنظیمات: ' . $e->getMessage(),
                'updated' => [],
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * بازنشانی تنظیمات چت به مقادیر پیش‌فرض
     *
     * @param int $adminId
     * @return array
     */
    public function resetToDefaults(int $adminId): array
    {
        try {
            $defaultKeys = [
                'allow_chat_after_72_hours',
                'allow_chat_download',
                'allow_photo_only',
                'allow_view_names_only',
                'prevent_bad_words',
                'prevent_repeat_comments',
                'prevent_frequent_reviews',
                'limit_reviews_per_user',
                'prevent_low_char_messages',
            ];

            $updated = [];
            $errors = [];

            foreach ($defaultKeys as $key) {
                $result = ChatSetting::resetToDefault($key, $adminId);
                if ($result) {
                    $updated[] = $key;
                } else {
                    $errors[] = "تنظیم '{$key}' یافت نشد.";
                }
            }

            // پاک کردن کش برای به‌روزرسانی
            Cache::forget('chat_settings');

            return [
                'status' => true,
                'message' => 'تنظیمات با موفقیت به مقادیر پیش‌فرض بازنشانی شدند.',
                'updated' => $updated,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            Log::error('خطا در بازنشانی تنظیمات چت: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'خطا در بازنشانی تنظیمات: ' . $e->getMessage(),
                'updated' => [],
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * بررسی و فیلتر اسپم در محتوای پیام
     *
     * @param string $message
     * @param int $userId
     * @param int|null $orderId
     * @return array
     */
    public function checkSpamFilters(string $message, int $userId, ?int $orderId = null): array
    {
        $settings = $this->getAllSettings();
        $errors = [];

        // بررسی طول پیام
        if (isset($settings['prevent_low_char_messages']) && $settings['prevent_low_char_messages']) {
            $minCharCount = 5; // حداقل تعداد کاراکتر قابل تنظیم
            if (mb_strlen(trim($message)) < $minCharCount) {
                $errors[] = "پیام شما باید حداقل {$minCharCount} کاراکتر داشته باشد.";
            }
        }

        // بررسی کلمات نامناسب
        if (isset($settings['prevent_bad_words']) && $settings['prevent_bad_words']) {
            $badWords = $this->getBadWordsList();
            foreach ($badWords as $word) {
                if (stripos($message, $word) !== false) {
                    $errors[] = "پیام شما حاوی کلمات نامناسب است.";
                    break;
                }
            }
        }

        // بررسی تکراری بودن پیام (اگر orderId داشته باشیم)
        if (isset($settings['prevent_repeat_comments']) && $settings['prevent_repeat_comments'] && $orderId) {
            if ($this->isCommentRepeated($message, $userId, $orderId)) {
                $errors[] = "شما قبلاً پیام مشابهی ارسال کرده‌اید.";
            }
        }

        // بررسی تعداد نظرات کاربر در روز
        if (isset($settings['prevent_frequent_reviews']) && $settings['prevent_frequent_reviews']) {
            $limit = isset($settings['limit_reviews_per_user']) ? (int)$settings['limit_reviews_per_user'] : 5;
            
            if ($this->getUserReviewCountToday($userId) >= $limit) {
                $errors[] = "شما بیش از {$limit} نظر در روز نمی‌توانید ثبت کنید.";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * دریافت لیست کلمات نامناسب
     *
     * @return array
     */
    private function getBadWordsList(): array
    {
        // این لیست می‌تواند از فایل، دیتابیس یا سرویس خارجی خوانده شود
        $badWords = Cache::remember('bad_words_list', 86400, function () {
            // در محیط واقعی، این می‌تواند از دیتابیس یا فایل خوانده شود
            return ['کلمه_نامناسب_1', 'کلمه_نامناسب_2', 'کلمه_نامناسب_3'];
        });

        return $badWords;
    }

    /**
     * بررسی تکراری بودن نظر
     *
     * @param string $message
     * @param int $userId
     * @param int $orderId
     * @return bool
     */
    private function isCommentRepeated(string $message, int $userId, int $orderId): bool
    {
        // در اینجا باید بررسی کنیم آیا کاربر قبلاً پیام مشابهی برای این سفارش ارسال کرده است یا خیر
        // این متد باید با جدول‌های واقعی پیام‌ها و نظرات پروژه هماهنگ شود
        
        // به عنوان نمونه:
        /*
        return \DB::table('order_messages')
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->where('message', 'like', '%' . trim($message) . '%')
            ->exists();
        */
        
        // در این نمونه کد، فرض می‌کنیم پیام تکراری نیست
        return false;
    }

    /**
     * دریافت تعداد نظرات کاربر در روز جاری
     *
     * @param int $userId
     * @return int
     */
    private function getUserReviewCountToday(int $userId): int
    {
        // در اینجا باید تعداد نظرات ثبت شده توسط کاربر در روز جاری را بررسی کنیم
        // این متد باید با جدول‌های واقعی نظرات پروژه هماهنگ شود
        
        // به عنوان نمونه:
        /*
        return \DB::table('service_provider_reviews')
            ->where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        */
        
        // در این نمونه کد، فرض می‌کنیم کاربر کمتر از حد مجاز نظر داده است
        return 0;
    }

    /**
     * ایجاد تنظیمات پیش‌فرض در دیتابیس
     *
     * @return array
     */
    public function createDefaultSettings(): array
    {
        $defaultSettings = [
            [
                'key' => 'allow_chat_after_72_hours',
                'value' => '0',
                'description' => 'اجازه گفتگو پس از 72 ساعت',
                'type' => 'boolean'
            ],
            [
                'key' => 'allow_chat_download',
                'value' => '1',
                'description' => 'اجازه دانلود گفتگو',
                'type' => 'boolean'
            ],
            [
                'key' => 'allow_photo_only',
                'value' => '0',
                'description' => 'اجازه ارسال فقط عکس',
                'type' => 'boolean'
            ],
            [
                'key' => 'allow_view_names_only',
                'value' => '0',
                'description' => 'اجازه مشاهده فقط نام‌ها',
                'type' => 'boolean'
            ],
            [
                'key' => 'prevent_bad_words',
                'value' => '1',
                'description' => 'جلوگیری از ارسال کلمات نامناسب',
                'type' => 'boolean'
            ],
            [
                'key' => 'prevent_repeat_comments',
                'value' => '1',
                'description' => 'جلوگیری از ارسال نظرات تکراری',
                'type' => 'boolean'
            ],
            [
                'key' => 'prevent_frequent_reviews',
                'value' => '1',
                'description' => 'جلوگیری از ارسال نظرات مکرر',
                'type' => 'boolean'
            ],
            [
                'key' => 'limit_reviews_per_user',
                'value' => '5',
                'description' => 'محدودیت تعداد نظرات برای هر کاربر در روز',
                'type' => 'integer'
            ],
            [
                'key' => 'prevent_low_char_messages',
                'value' => '1',
                'description' => 'جلوگیری از ارسال پیام‌های کوتاه',
                'type' => 'boolean'
            ],
        ];

        $created = [];

        foreach ($defaultSettings as $setting) {
            // ایجاد تنظیم فقط اگر وجود نداشته باشد
            if (!ChatSetting::where('key', $setting['key'])->exists()) {
                ChatSetting::create($setting);
                $created[] = $setting['key'];
            }
        }

        // پاک کردن کش برای به‌روزرسانی
        Cache::forget('chat_settings');

        return [
            'status' => true,
            'message' => 'تنظیمات پیش‌فرض با موفقیت ایجاد شدند.',
            'created' => $created
        ];
    }
} 