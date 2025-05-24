<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatSettingController extends Controller
{
    protected $chatSettingService;

    /**
     * سازنده کلاس
     * 
     * @param ChatSettingService $chatSettingService
     */
    public function __construct(ChatSettingService $chatSettingService)
    {
        $this->chatSettingService = $chatSettingService;
    }

    /**
     * دریافت تنظیمات چت و فیلترهای اسپم
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $settings = $this->chatSettingService->getAllSettings();

        return response()->json([
            'status' => true,
            'data' => $settings
        ]);
    }

    /**
     * به‌روزرسانی تنظیمات چت و فیلترهای اسپم
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'allow_chat_after_72_hours' => 'boolean',
            'allow_chat_download' => 'boolean',
            'allow_photo_only' => 'boolean',
            'allow_view_names_only' => 'boolean',
            'prevent_bad_words' => 'boolean',
            'prevent_repeat_comments' => 'boolean',
            'prevent_frequent_reviews' => 'boolean',
            'limit_reviews_per_user' => 'integer|min:1|max:100',
            'prevent_low_char_messages' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        // فیلتر کردن تنظیمات ارسال شده
        $settings = [];
        foreach ($validator->validated() as $key => $value) {
            $settings[$key] = $value;
        }

        $result = $this->chatSettingService->updateSettings($settings, $user->user_id);

        if (!$result['status']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * بازنشانی تنظیمات به مقادیر پیش‌فرض
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetSettings()
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $result = $this->chatSettingService->resetToDefaults($user->user_id);

        if (!$result['status']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * ایجاد تنظیمات پیش‌فرض (فقط برای استفاده در تنظیمات اولیه سیستم)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function initializeSettings()
    {
        $user = Auth::user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $result = $this->chatSettingService->createDefaultSettings();

        return response()->json($result);
    }

    /**
     * بررسی مطابقت یک پیام با فیلترهای اسپم
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMessage(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'order_id' => 'nullable|integer|exists:orders,order_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'اطلاعات وارد شده نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $checkResult = $this->chatSettingService->checkSpamFilters(
            $request->message,
            $user->user_id,
            $request->order_id
        );

        return response()->json([
            'status' => true,
            'is_valid' => $checkResult['is_valid'],
            'errors' => $checkResult['errors']
        ]);
    }
}
