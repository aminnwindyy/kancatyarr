<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\OtpCode;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProviderAuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * ارسال کد تأیید به شماره موبایل ارائه دهنده خدمات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        $phone = $request->phone_number;

        // بررسی محدودیت ارسال OTP (3 بار در 5 دقیقه)
        $requestsCount = OtpCode::where('phone_number', $phone)
            ->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->count();

        if ($requestsCount >= 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً بعد از 5 دقیقه مجدداً تلاش کنید.',
            ], 429); // Too Many Requests
        }

        // بررسی وجود ارائه‌دهنده در سیستم
        $serviceProvider = ServiceProvider::where('phone', $phone)->first();
        $user = null;

        if ($serviceProvider) {
            // اگر ارائه‌دهنده موجود است، کاربر مرتبط را بررسی می‌کنیم
            $user = User::find($serviceProvider->user_id);
            
            // اگر کاربر غیرفعال است، اجازه ورود نمی‌دهیم
            if ($user && !$user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حساب کاربری شما غیرفعال است. لطفاً با پشتیبانی تماس بگیرید.',
                ], 403);
            }
            
            // اگر وضعیت ارائه‌دهنده تأیید نشده است، اجازه ورود نمی‌دهیم
            if ($serviceProvider->status !== 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'حساب ارائه‌دهنده خدمات شما هنوز تأیید نشده است. لطفاً با پشتیبانی تماس بگیرید.',
                ], 403);
            }
        }

        // ایجاد کد تأیید با استفاده از سرویس OTP
        $code = $this->otpService->generateOtpForPhone($phone, 6, 2);

        // در محیط واقعی، اینجا کد را به شماره موبایل ارسال می‌کنیم
        // TODO: ارسال پیامک حاوی کد به شماره موبایل

        return response()->json([
            'status' => 'success',
            'message' => 'کد تأیید به شماره موبایل شما ارسال شد. این کد تا 2 دقیقه معتبر است.',
            'user_exists' => $serviceProvider ? true : false,
        ]);
    }

    /**
     * تأیید کد OTP و ورود ارائه دهنده خدمات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|min:6|max:6',
        ]);

        $phone = $request->phone_number;
        $code = $request->code;

        // بررسی کد وارد شده با استفاده از سرویس OTP
        $isValidOtp = $this->otpService->verifyOtpForPhone($phone, $code);

        if (!$isValidOtp) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد تأیید نامعتبر یا منقضی شده است.',
            ], 401);
        }

        // بررسی وجود ارائه‌دهنده در سیستم
        $serviceProvider = ServiceProvider::where('phone', $phone)->first();
        
        if (!$serviceProvider) {
            // اگر ارائه‌دهنده وجود ندارد، به صفحه ثبت‌نام هدایت می‌کنیم
            return response()->json([
                'status' => 'error',
                'message' => 'شما هنوز ثبت‌نام نکرده‌اید. لطفاً ابتدا ثبت‌نام کنید.',
                'require_registration' => true,
            ], 403);
        }

        // بررسی وضعیت ارائه‌دهنده
        if ($serviceProvider->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'حساب ارائه‌دهنده خدمات شما هنوز تأیید نشده است. لطفاً با پشتیبانی تماس بگیرید.',
            ], 403);
        }

        // دریافت کاربر مرتبط با ارائه‌دهنده
        $user = User::find($serviceProvider->user_id);
        
        if (!$user || !$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'حساب کاربری شما غیرفعال است. لطفاً با پشتیبانی تماس بگیرید.',
            ], 403);
        }

        // به‌روزرسانی آخرین فعالیت ارائه‌دهنده
        $serviceProvider->updateLastActivity();
        
        // ایجاد توکن دسترسی
        $token = $user->createToken('provider-token', ['provider'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'provider' => [
                    'id' => $serviceProvider->id,
                    'name' => $serviceProvider->name,
                    'phone' => $serviceProvider->phone,
                    'avatar' => null, // می‌توان در آینده اضافه کرد
                    'category' => $serviceProvider->category,
                ],
            ],
        ]);
    }

    /**
     * ثبت‌نام ارائه دهنده خدمات جدید
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|min:6|max:6',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'category' => 'required|in:commercial,connectyar',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'national_code' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
            'business_license' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->phone_number;
        $code = $request->code;

        // بررسی کد وارد شده با استفاده از سرویس OTP
        $isValidOtp = $this->otpService->verifyOtpForPhone($phone, $code);

        if (!$isValidOtp) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد تأیید نامعتبر یا منقضی شده است.',
            ], 401);
        }

        // بررسی وجود ارائه‌دهنده در سیستم
        $existingProvider = ServiceProvider::where('phone', $phone)->first();
        
        if ($existingProvider) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما قبلاً ثبت‌نام کرده‌اید. لطفاً وارد شوید.',
            ], 409);
        }

        DB::beginTransaction();
        try {
            // ایجاد کاربر جدید
            $user = new User();
            $user->first_name = $request->name;
            $user->last_name = '';
            $user->email = $request->email;
            $user->password = Hash::make(Str::random(16)); // رمز عبور تصادفی
            $user->phone_number = $phone;
            $user->is_active = true;
            $user->is_phone_verified = true;
            $user->login_preference = 'phone_otp';
            $user->save();

            // ایجاد ارائه‌دهنده خدمات جدید
            $serviceProvider = new ServiceProvider();
            $serviceProvider->user_id = $user->user_id;
            $serviceProvider->name = $request->name;
            $serviceProvider->email = $request->email;
            $serviceProvider->phone = $phone;
            $serviceProvider->category = $request->category;
            $serviceProvider->status = 'pending'; // وضعیت اولیه: در انتظار تأیید
            $serviceProvider->description = $request->description;
            $serviceProvider->address = $request->address;
            $serviceProvider->national_code = $request->national_code;
            $serviceProvider->business_license = $request->business_license;
            $serviceProvider->save();

            DB::commit();

            // ایجاد توکن دسترسی
            $token = $user->createToken('provider-token', ['provider'])->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'ثبت‌نام شما با موفقیت انجام شد. حساب شما در انتظار تأیید است.',
                'data' => [
                    'token' => $token,
                    'provider' => [
                        'id' => $serviceProvider->id,
                        'name' => $serviceProvider->name,
                        'phone' => $serviceProvider->phone,
                        'avatar' => null,
                        'category' => $serviceProvider->category,
                        'status' => $serviceProvider->status,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ثبت‌نام. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت اطلاعات پروفایل ارائه دهنده خدمات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $serviceProvider = ServiceProvider::where('user_id', $user->user_id)->first();

        if (!$serviceProvider) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات ارائه دهنده خدمات یافت نشد.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'provider' => [
                    'id' => $serviceProvider->id,
                    'name' => $serviceProvider->name,
                    'email' => $serviceProvider->email,
                    'phone' => $serviceProvider->phone,
                    'category' => $serviceProvider->category,
                    'status' => $serviceProvider->status,
                    'address' => $serviceProvider->address,
                    'description' => $serviceProvider->description,
                    'national_code' => $serviceProvider->national_code,
                    'business_license' => $serviceProvider->business_license,
                    'rating' => $serviceProvider->rating,
                    'website' => $serviceProvider->website,
                    'created_at' => $serviceProvider->created_at,
                    'last_activity_at' => $serviceProvider->last_activity_at,
                ],
            ],
        ]);
    }
} 