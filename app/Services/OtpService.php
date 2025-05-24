<?php

namespace App\Services;

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate a new OTP code for a user
     *
     * @param User $user
     * @param string $type email|sms
     * @param int $length Length of the OTP code
     * @param int $expiresInMinutes Minutes until the code expires
     * @return string The generated OTP code
     */
    public function generateOtp(User $user, string $type, int $length = 6, int $expiresInMinutes = 5): string
    {
        // Delete any existing OTP codes for this user and type
        DB::table('otp_codes')
            ->where('user_id', $user->user_id)
            ->where('type', $type)
            ->delete();

        // Generate a random numeric code
        $code = (string) random_int(100000, 999999);
        if ($length !== 6) {
            $code = Str::padLeft((string) random_int(0, pow(10, $length) - 1), $length, '0');
        }

        // Store the code in the database
        DB::table('otp_codes')->insert([
            'user_id' => $user->user_id,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $code;
    }

    /**
     * تولید کد OTP برای یک شماره موبایل (بدون نیاز به کاربر)
     *
     * @param string $phoneNumber
     * @param int $length
     * @param int $expiresInMinutes
     * @return string
     */
    public function generateOtpForPhone(string $phoneNumber, int $length = 6, int $expiresInMinutes = 2): string
    {
        // حذف کدهای قبلی برای این شماره موبایل
        DB::table('otp_codes')
            ->where('phone_number', $phoneNumber)
            ->delete();

        // تولید کد تصادفی
        $code = (string) random_int(100000, 999999);
        if ($length !== 6) {
            $code = Str::padLeft((string) random_int(0, pow(10, $length) - 1), $length, '0');
        }

        // ذخیره کد در دیتابیس
        DB::table('otp_codes')->insert([
            'phone_number' => $phoneNumber,
            'code' => $code,
            'type' => 'sms',
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $code;
    }

    /**
     * Verify an OTP code for a user
     *
     * @param User $user
     * @param string $code
     * @param string $type email|sms
     * @return bool
     */
    public function verifyOtp(User $user, string $code, string $type): bool
    {
        $otpRecord = DB::table('otp_codes')
            ->where('user_id', $user->user_id)
            ->where('type', $type)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if ($otpRecord) {
            // Delete the OTP code after successful verification
            DB::table('otp_codes')
                ->where('id', $otpRecord->id)
                ->delete();

            return true;
        }

        return false;
    }

    /**
     * بررسی صحت کد OTP برای یک شماره موبایل
     *
     * @param string $phoneNumber
     * @param string $code
     * @return bool
     */
    public function verifyOtpForPhone(string $phoneNumber, string $code): bool
    {
        $otpRecord = DB::table('otp_codes')
            ->where('phone_number', $phoneNumber)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if ($otpRecord) {
            // حذف کد OTP پس از تأیید موفق
            DB::table('otp_codes')
                ->where('id', $otpRecord->id)
                ->delete();
            return true;
        }

        return false;
    }

    /**
     * Send OTP code via email
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function sendEmailOtp(User $user, string $code): bool
    {
        // در اینجا می‌توانید کد ارسال ایمیل را پیاده‌سازی کنید
        // مثال: استفاده از Mail facade برای ارسال ایمیل

        // Mail::to($user->email)->send(new OtpMail($code));

        // برای تست، فقط true برمی‌گردانیم
        return true;
    }

    /**
     * Send OTP code via SMS
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function sendSmsOtp(User $user, string $code): bool
    {
        // در اینجا می‌توانید کد ارسال پیامک را پیاده‌سازی کنید
        // مثال: استفاده از یک سرویس پیامک

        // SmsService::send($user->phone_number, "کد تایید شما: {$code}");

        // برای تست، فقط true برمی‌گردانیم
        return true;
    }

    /**
     * ارسال کد OTP از طریق پیامک به یک شماره موبایل
     *
     * @param string $phoneNumber
     * @param string $code
     * @return bool
     */
    public function sendSmsOtpToPhone(string $phoneNumber, string $code): bool
    {
        // در اینجا می‌توانید کد ارسال پیامک را پیاده‌سازی کنید
        // مثال: استفاده از یک سرویس پیامک

        // SmsService::send($phoneNumber, "کد تایید شما: {$code}");

        // برای تست، فقط true برمی‌گردانیم
        return true;
    }
}
