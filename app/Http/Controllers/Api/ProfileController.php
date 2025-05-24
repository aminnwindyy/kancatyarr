<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * دریافت اطلاعات پروفایل کاربر جاری
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'profile' => [
                'id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'profile_image' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }

    /**
     * به‌روزرسانی اطلاعات پروفایل
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users')->ignore($user->user_id, 'user_id'),
            ],
            'profile_image' => 'nullable|image|max:2048', // حداکثر 2MB
        ]);

        $data = $request->only(['first_name', 'last_name', 'email']);

        // فیلترکردن داده‌های خالی
        $data = array_filter($data, function ($value) {
            return !is_null($value);
        });

        // آپلود تصویر پروفایل در صورت وجود
        if ($request->hasFile('profile_image')) {
            // حذف تصویر قبلی در صورت وجود
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // ذخیره تصویر جدید
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $data['profile_image'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'اطلاعات پروفایل با موفقیت به‌روزرسانی شد.',
            'profile' => [
                'id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'profile_image' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }

    /**
     * تغییر رمز عبور
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        // بررسی رمز عبور فعلی
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'رمز عبور فعلی نادرست است.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'رمز عبور با موفقیت تغییر یافت.'
        ]);
    }

    /**
     * دریافت تنظیمات ورود کاربر
     */
    public function getLoginSettings(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'login_preference' => $user->login_preference,
                'has_verified_email' => !empty($user->email_verified_at),
                'has_verified_phone' => $user->is_phone_verified,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ]
        ]);
    }

    /**
     * به‌روزرسانی تنظیمات ورود کاربر
     */
    public function updateLoginSettings(Request $request)
    {
        $request->validate([
            'login_preference' => [
                'required',
                Rule::in(['password', 'email_otp', 'phone_otp']),
            ],
        ]);

        $user = $request->user();

        // اگر روش ورود مبتنی بر ایمیل یا شماره تلفن انتخاب شده، بررسی کنیم که آیا کاربر ایمیل/شماره تلفن تایید شده دارد یا خیر
        if ($request->login_preference === 'email_otp' && empty($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'برای انتخاب ورود با ایمیل، باید ابتدا ایمیل خود را تایید کنید.'
            ], 422);
        }

        if ($request->login_preference === 'phone_otp' && !$user->is_phone_verified) {
            return response()->json([
                'success' => false,
                'message' => 'برای انتخاب ورود با شماره تلفن، باید ابتدا شماره تلفن خود را تایید کنید.'
            ], 422);
        }

        $user->update([
            'login_preference' => $request->login_preference
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تنظیمات ورود با موفقیت به‌روزرسانی شد.',
            'data' => [
                'login_preference' => $user->login_preference
            ]
        ]);
    }

    /**
     * دریافت لیست دستگاه‌های اخیر ورود کاربر
     */
    public function getLoginDevices(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 10);

        $devices = $user->loginHistories()
            ->orderBy('login_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $devices
        ]);
    }

    /**
     * حذف یک دستگاه از لیست دستگاه‌های فعال و باطل کردن توکن‌های مربوطه
     */
    public function revokeDevice(Request $request, $deviceId)
    {
        $user = $request->user();

        // پیدا کردن رکورد دستگاه
        $device = $user->loginHistories()->findOrFail($deviceId);

        // حذف توکن‌های مرتبط با این دستگاه براساس user-agent
        if ($device->user_agent) {
            $user->tokens()->where('name', 'admin-token')
                ->where('last_used_at', '>=', $device->login_at->subMinutes(5))
                ->where('last_used_at', '<=', $device->login_at->addMinutes(5))
                ->delete();
        }

        // حذف رکورد دستگاه
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'دستگاه مورد نظر با موفقیت حذف و نشست‌های مربوط به آن باطل شد.'
        ]);
    }

    /**
     * باطل کردن همه توکن‌ها به جز توکن فعلی
     */
    public function revokeAllDevices(Request $request)
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // حذف همه توکن‌ها به جز توکن فعلی
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'همه دستگاه‌های دیگر با موفقیت خارج شدند.'
        ]);
    }
}
