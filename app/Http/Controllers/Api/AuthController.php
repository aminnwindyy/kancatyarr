<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\OtpCode;

class AuthController extends Controller
{
    protected $otpService;
    protected $securityService;

    public function __construct(OtpService $otpService, SecurityService $securityService)
    {
        $this->otpService = $otpService;
        $this->securityService = $securityService;
    }

    /**
     * ورود کاربر به سیستم و دریافت توکن
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required_if:login_type,password',
            'otp' => 'required_if:login_type,email_otp,phone_otp',
            'login_type' => [
                'sometimes',
                Rule::in(['password', 'email_otp', 'phone_otp']),
            ],
        ]);

        // بررسی قفل شدن حساب کاربری
        $email = $request->email;
        if ($this->securityService->isAccountLocked($email)) {
            $remainingSeconds = $this->securityService->getLockoutRemainingSeconds($email);
            $remainingMinutes = ceil($remainingSeconds / 60);

            return response()->json([
                'message' => "حساب کاربری شما به دلیل تلاش‌های ناموفق زیاد، به مدت {$remainingMinutes} دقیقه قفل شده است."
            ], 429);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            // ثبت تلاش ناموفق برای این ایمیل
            $this->securityService->recordFailedAttempt($email);

            throw ValidationException::withMessages([
                'email' => ['اطلاعات ورود نادرست است.'],
            ]);
        }

        // تعیین نوع ورود براساس ترجیحات کاربر یا پارامتر ورودی
        $loginType = $request->login_type ?? $user->login_preference;

        $authenticated = false;

        // احراز هویت براساس نوع ورود انتخاب شده
        if ($loginType === 'password') {
            // احراز هویت با رمز عبور
            if (Hash::check($request->password, $user->password)) {
                $authenticated = true;
            }
        } elseif ($loginType === 'email_otp') {
            // احراز هویت با کد یکبار مصرف ایمیل
            // بررسی اینکه ایمیل کاربر تایید شده باشد
            if (empty($user->email_verified_at)) {
                return response()->json([
                    'message' => 'ایمیل شما تایید نشده است. امکان ورود با این روش وجود ندارد.'
                ], 403);
            }

            // تایید کد OTP با استفاده از سرویس OTP
            $authenticated = $this->otpService->verifyOtp($user, $request->otp, 'email');

        } elseif ($loginType === 'phone_otp') {
            // احراز هویت با کد یکبار مصرف پیامک
            // بررسی اینکه شماره موبایل کاربر تایید شده باشد
            if (!$user->is_phone_verified) {
                return response()->json([
                    'message' => 'شماره موبایل شما تایید نشده است. امکان ورود با این روش وجود ندارد.'
                ], 403);
            }

            // تایید کد OTP با استفاده از سرویس OTP
            $authenticated = $this->otpService->verifyOtp($user, $request->otp, 'sms');
        }

        if (!$authenticated) {
            // ثبت تلاش ناموفق برای این ایمیل
            $this->securityService->recordFailedAttempt($email);

            throw ValidationException::withMessages([
                'email' => ['اطلاعات ورود نادرست است.'],
            ]);
        }

        // پاک کردن رکوردهای تلاش ناموفق پس از موفقیت در ورود
        $this->securityService->clearFailedAttempts($email);

        // بررسی فعال بودن کاربر
        if (!$user->is_active) {
            return response()->json([
                'message' => 'حساب کاربری شما غیرفعال شده است.'
            ], 403);
        }

        // بررسی دسترسی ادمین
        if (!$user->is_admin) {
            return response()->json([
                'message' => 'شما دسترسی به پنل مدیریت را ندارید.'
            ], 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'login_preference' => $user->login_preference,
            ],
            'token' => $token
        ]);
    }

    /**
     * ارسال کد یکبار مصرف به ایمیل کاربر
     */
    public function sendEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->email;

        // بررسی قفل شدن حساب کاربری
        if ($this->securityService->isAccountLocked($email)) {
            $remainingSeconds = $this->securityService->getLockoutRemainingSeconds($email);
            $remainingMinutes = ceil($remainingSeconds / 60);

            return response()->json([
                'success' => false,
                'message' => "حساب کاربری شما به دلیل تلاش‌های ناموفق زیاد، به مدت {$remainingMinutes} دقیقه قفل شده است."
            ], 429);
        }

        $user = User::where('email', $email)->first();

        if (empty($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'ایمیل شما تایید نشده است. امکان ورود با این روش وجود ندارد.'
            ], 403);
        }

        // تولید کد OTP جدید
        $code = $this->otpService->generateOtp($user, 'email');

        // ارسال کد به ایمیل کاربر
        $this->otpService->sendEmailOtp($user, $code);

        return response()->json([
            'success' => true,
            'message' => 'کد یکبار مصرف به ایمیل شما ارسال شد.'
        ]);
    }

    /**
     * ارسال کد یکبار مصرف به شماره موبایل کاربر
     */
    public function sendSmsOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->email;

        // بررسی قفل شدن حساب کاربری
        if ($this->securityService->isAccountLocked($email)) {
            $remainingSeconds = $this->securityService->getLockoutRemainingSeconds($email);
            $remainingMinutes = ceil($remainingSeconds / 60);

            return response()->json([
                'success' => false,
                'message' => "حساب کاربری شما به دلیل تلاش‌های ناموفق زیاد، به مدت {$remainingMinutes} دقیقه قفل شده است."
            ], 429);
        }

        $user = User::where('email', $email)->first();

        if (!$user->is_phone_verified || empty($user->phone_number)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل شما تایید نشده است. امکان ورود با این روش وجود ندارد.'
            ], 403);
        }

        // تولید کد OTP جدید
        $code = $this->otpService->generateOtp($user, 'sms');

        // ارسال کد به شماره موبایل کاربر
        $this->otpService->sendSmsOtp($user, $code);

        return response()->json([
            'success' => true,
            'message' => 'کد یکبار مصرف به شماره موبایل شما ارسال شد.'
        ]);
    }

    /**
     * خروج کاربر از سیستم
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'خروج با موفقیت انجام شد.'
        ]);
    }

    /**
     * دریافت اطلاعات کاربر فعلی
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'login_preference' => $user->login_preference,
            ]
        ]);
    }

    /**
     * باز کردن قفل حساب کاربری (مخصوص ادمین)
     */
    public function unlockAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $this->securityService->unlockAccount($request->email);

        return response()->json([
            'success' => true,
            'message' => "قفل حساب کاربری {$request->email} با موفقیت برداشته شد."
        ]);
    }

    /**
     * درخواست کد یکبار مصرف (otp-send)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'شماره موبایل نامعتبر است',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        
        // بررسی محدودیت ارسال کد
        if (OtpCode::hasReachedRateLimit($phoneNumber)) {
            return response()->json([
                'status' => 'error',
                'message' => 'محدودیت ارسال کد. لطفا 5 دقیقه دیگر تلاش کنید.'
            ], 429);
        }
        
        // تولید و ذخیره کد OTP با استفاده از مدل OtpCode
        $otpRecord = OtpCode::createForPhone($phoneNumber, 2); // 2 دقیقه اعتبار
        $code = $otpRecord->code;
        
        // در محیط واقعی، ارسال کد از طریق پیامک
        // اینجا فقط لاگ می‌کنیم
        \Log::info("OTP code for $phoneNumber: $code");
        
        // در محیط تولید، نباید کد را برگردانیم
        return response()->json([
            'status' => 'success',
            'message' => 'کد تایید با موفقیت ارسال شد',
            'phone_number' => $phoneNumber,
            #'otp' => $code, // این خط را در محیط تولید حذف کنید
            'expires_at' => $otpRecord->expires_at->timestamp,
        ]);
    }

    /**
     * تایید کد یکبار مصرف (otp-verify)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|regex:/^09[0-9]{9}$/',
            'code' => 'required|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات نامعتبر',
                'errors' => $validator->errors(),
                'error_code' => 'INVALID_INPUT'
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $code = $request->code;
        
        try {
            // تأیید کد OTP - حالت 3: کد تأیید نامعتبر (اشتباه، منقضی یا استفاده شده)
            if (!OtpCode::validateCode($phoneNumber, $code)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد تایید نامعتبر یا منقضی شده است',
                    'error_code' => 'INVALID_OTP'
                ], 400);
            }

            // بررسی وجود کاربر با این شماره موبایل
            $user = User::where('phone_number', $phoneNumber)->first();
            
            if ($user) {
                // حالت 2: کاربر موجود ولی حساب مسدود شده
                if (!$user->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'حساب کاربری شما مسدود شده است. لطفاً با پشتیبانی تماس بگیرید.',
                        'error_code' => 'ACCOUNT_BLOCKED'
                    ], 403);
                }
                
                // حالت 1: کاربر موجود و ورود موفق
                $user->is_phone_verified = true;
                $user->save();
                
                // ایجاد توکن
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'status' => 'success',
                    'message' => 'ورود با موفقیت انجام شد',
                    'user' => [
                        'id' => $user->user_id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'phone_number' => $user->phone_number,
                        'username' => $user->username,
                        'referral_code' => $user->referral_code,
                    ],
                    'token' => $token,
                    'is_new_user' => false,
                ], 200);
            } else {
                // کاربر جدید - نیاز به ثبت‌نام
                return response()->json([
                    'status' => 'success',
                    'message' => 'شماره موبایل تایید شد. لطفاً ثبت‌نام را تکمیل کنید.',
                    'phone_number' => $phoneNumber,
                    'is_new_user' => true,
                ], 200);
            }
        } catch (\Exception $e) {
            // حالت 4: خطای سیستمی دیگر
            \Log::error('خطا در تأیید OTP: ' . $e->getMessage(), [
                'phone_number' => $phoneNumber,
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطای سیستمی رخ داده است. لطفاً مجدداً تلاش کنید.',
                'error_code' => 'SYSTEM_ERROR'
            ], 500);
        }
    }

    /**
     * ثبت‌نام کاربر جدید
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|regex:/^09[0-9]{9}$/|unique:users,phone_number',
            'full_name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|unique:users,username',
            'national_code' => 'nullable|string|size:10|unique:users,national_id',
            'referral_code' => 'nullable|string|exists:users,referral_code',
            'accept_terms' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات نامعتبر',
                'errors' => $validator->errors(),
            ], 422);
        }

        // شروع تراکنش دیتابیس
        DB::beginTransaction();

        try {
            // ایجاد کاربر جدید
            $nameParts = explode(' ', $request->full_name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $user = new User();
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->phone_number = $request->phone_number;
            $user->is_phone_verified = true;
            
            if ($request->has('username') && !empty($request->username)) {
                $user->username = $request->username;
            } else {
                $user->username = User::generateUniqueUsername($request->full_name);
            }
            
            if ($request->has('national_code') && !empty($request->national_code)) {
                $user->national_id = $request->national_code;
            }
            
            // دریافت نسخه فعال قوانین
            $termsVersion = \App\Models\TermsOfService::getActive()?->version ?? 1;
            $user->accepted_terms_version = $termsVersion;
            
            $user->save();
            
            // تولید کد معرف برای کاربر
            $referralCode = $user->generateReferralCode();
            
            // ایجاد کیف پول برای کاربر
            $wallet = \App\Models\Wallet::findOrCreateForUser($user->user_id);
            
            // ایجاد کارت هدیه برای کاربر
            $expiryMonths = 6; // 6 ماه اعتبار برای کارت هدیه
            $giftCard = \App\Models\GiftCard::createForUser(
                $user->user_id, 
                0, // مقدار اولیه صفر
                $user->user_id, // خود کاربر به عنوان ایجاد کننده
                $expiryMonths
            );
            
            // اعمال کد معرف (اگر وارد شده باشد)
            if ($request->has('referral_code') && !empty($request->referral_code)) {
                $user->applyReferralBonus($request->referral_code);
            }
            
            // کامیت تراکنش
            DB::commit();
            
            // ایجاد توکن احراز هویت
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'status' => 'success',
                'message' => 'ثبت‌نام با موفقیت انجام شد',
                'user' => [
                    'id' => $user->user_id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone_number' => $user->phone_number,
                    'username' => $user->username,
                    'referral_code' => $user->referral_code,
                ],
                'token' => $token,
            ]);
            
        } catch (\Exception $e) {
            // رولبک تراکنش در صورت بروز خطا
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ثبت‌نام: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت اطلاعات کاربر فعلی
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user' => $request->user(),
        ]);
    }
}
