<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    /**
     * دریافت تنظیمات فعلی سیستم دعوت دوستان
     *
     * @return \Illuminate\Http\Response
     */
    public function getSettings()
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $settings = ReferralProgram::where('is_active', true)->latest()->first();

        return response()->json([
            'data' => $settings
        ]);
    }

    /**
     * به‌روزرسانی تنظیمات سیستم دعوت دوستان
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSettings(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'reward_type' => 'required|in:percentage,fixed_amount,points',
            'reward_amount' => 'required|numeric|min:0',
            'referrer_reward' => 'required|numeric|min:0',
            'referee_reward' => 'required|numeric|min:0',
            'expiry_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // غیرفعال کردن تمام برنامه‌های فعلی
        if ($request->input('is_active', true)) {
            ReferralProgram::where('is_active', true)->update(['is_active' => false]);
        }

        // ایجاد برنامه جدید
        $program = ReferralProgram::create([
            'name' => $request->name,
            'reward_type' => $request->reward_type,
            'reward_amount' => $request->reward_amount,
            'referrer_reward' => $request->referrer_reward,
            'referee_reward' => $request->referee_reward,
            'expiry_days' => $request->expiry_days,
            'is_active' => $request->input('is_active', true),
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'تنظیمات سیستم دعوت دوستان با موفقیت به‌روزرسانی شد',
            'data' => $program
        ]);
    }

    /**
     * ایجاد کد دعوت برای کاربر فعلی
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateReferralCode(Request $request)
    {
        $user = Auth::user();
        
        // بررسی آیا کاربر قبلاً کد دعوت دارد
        if ($user->referral_code) {
            return response()->json([
                'message' => 'شما قبلاً کد دعوت دریافت کرده‌اید',
                'data' => [
                    'referral_code' => $user->referral_code,
                    'referral_url' => url('/register?ref=' . $user->referral_code)
                ]
            ]);
        }
        
        // بررسی برنامه دعوت فعال
        $program = ReferralProgram::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
            
        if (!$program) {
            return response()->json([
                'message' => 'در حال حاضر برنامه دعوت فعالی وجود ندارد'
            ], 404);
        }
        
        // تولید کد دعوت منحصر به فرد
        do {
            $referralCode = Str::upper(Str::random(8));
        } while (User::where('referral_code', $referralCode)->exists());
        
        // ذخیره کد دعوت برای کاربر
        $user->update([
            'referral_code' => $referralCode
        ]);
        
        return response()->json([
            'message' => 'کد دعوت با موفقیت ایجاد شد',
            'data' => [
                'referral_code' => $referralCode,
                'referral_url' => url('/register?ref=' . $referralCode),
                'program' => $program
            ]
        ]);
    }

    /**
     * پذیرش دعوت توسط کاربر جدید
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function acceptInvitation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|exists:referrals,referral_code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'کد دعوت نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $referral = Referral::where('referral_code', $request->referral_code)
            ->where('status', 'pending')
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$referral) {
            return response()->json([
                'message' => 'کد دعوت منقضی شده یا نامعتبر است'
            ], 400);
        }

        // بررسی خودارجاعی
        if ($referral->referrer_id === $user->id) {
            return response()->json([
                'message' => 'شما نمی‌توانید از کد دعوت خودتان استفاده کنید'
            ], 400);
        }

        // بررسی تکراری نبودن
        if (Referral::where('referee_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'شما قبلاً از یک کد دعوت استفاده کرده‌اید'
            ], 400);
        }

        // تکمیل فرآیند دعوت
        $referral->update([
            'referee_id' => $user->id,
            'status' => 'completed'
        ]);

        // اینجا می‌توان پاداش‌ها را اعطا کرد
        // برای مثال، افزودن اعتبار به کیف پول یا امتیاز وفاداری

        return response()->json([
            'message' => 'کد دعوت با موفقیت اعمال شد',
            'data' => $referral
        ]);
    }

    /**
     * دریافت لیست دعوت‌های کاربر
     *
     * @return \Illuminate\Http\Response
     */
    public function myReferrals()
    {
        $user = Auth::user();
        $referrals = Referral::with('referee')
            ->where('referrer_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $referrals->items(),
            'meta' => [
                'current_page' => $referrals->currentPage(),
                'last_page' => $referrals->lastPage(),
                'per_page' => $referrals->perPage(),
                'total' => $referrals->total(),
            ]
        ]);
    }

    /**
     * پرداخت پاداش به کاربر دعوت‌کننده
     *
     * @param  int  $referralId
     * @return \Illuminate\Http\Response
     */
    public function payReferrerReward($referralId)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $referral = Referral::findOrFail($referralId);

        if ($referral->status !== 'completed') {
            return response()->json([
                'message' => 'این دعوت هنوز تکمیل نشده است'
            ], 400);
        }

        if ($referral->referrer_reward_paid) {
            return response()->json([
                'message' => 'پاداش دعوت‌کننده قبلاً پرداخت شده است'
            ], 400);
        }

        // پرداخت پاداش (مثلاً افزودن به کیف پول)
        // ...

        // بروزرسانی وضعیت پرداخت
        $referral->update([
            'referrer_reward_paid' => true
        ]);

        return response()->json([
            'message' => 'پاداش دعوت‌کننده با موفقیت پرداخت شد',
            'data' => $referral
        ]);
    }

    /**
     * پرداخت پاداش به کاربر دعوت‌شونده
     *
     * @param  int  $referralId
     * @return \Illuminate\Http\Response
     */
    public function payRefereeReward($referralId)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $referral = Referral::findOrFail($referralId);

        if ($referral->status !== 'completed') {
            return response()->json([
                'message' => 'این دعوت هنوز تکمیل نشده است'
            ], 400);
        }

        if ($referral->referee_reward_paid) {
            return response()->json([
                'message' => 'پاداش دعوت‌شونده قبلاً پرداخت شده است'
            ], 400);
        }

        // پرداخت پاداش (مثلاً افزودن به کیف پول)
        // ...

        // بروزرسانی وضعیت پرداخت
        $referral->update([
            'referee_reward_paid' => true
        ]);

        return response()->json([
            'message' => 'پاداش دعوت‌شونده با موفقیت پرداخت شد',
            'data' => $referral
        ]);
    }

    /**
     * دریافت آمار سیستم دعوت دوستان
     *
     * @return \Illuminate\Http\Response
     */
    public function getStats()
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $totalReferrals = Referral::count();
        $completedReferrals = Referral::where('status', 'completed')->count();
        $pendingReferrals = Referral::where('status', 'pending')->count();
        $expiredReferrals = Referral::where('status', 'expired')->count();

        $totalReferrerReward = Referral::where('status', 'completed')->sum('referrer_reward');
        $totalRefereeReward = Referral::where('status', 'completed')->sum('referee_reward');

        $paidReferrerReward = Referral::where('status', 'completed')
            ->where('referrer_reward_paid', true)
            ->sum('referrer_reward');
            
        $paidRefereeReward = Referral::where('status', 'completed')
            ->where('referee_reward_paid', true)
            ->sum('referee_reward');

        return response()->json([
            'data' => [
                'total_referrals' => $totalReferrals,
                'completed_referrals' => $completedReferrals,
                'pending_referrals' => $pendingReferrals,
                'expired_referrals' => $expiredReferrals,
                'total_referrer_reward' => $totalReferrerReward,
                'total_referee_reward' => $totalRefereeReward,
                'paid_referrer_reward' => $paidReferrerReward,
                'paid_referee_reward' => $paidRefereeReward
            ]
        ]);
    }

    /**
     * دریافت اطلاعات برنامه دعوت فعال
     *
     * @return \Illuminate\Http\Response
     */
    public function getActiveProgram()
    {
        $program = ReferralProgram::where('is_active', true)->first();
        
        if (!$program) {
            return response()->json([
                'message' => 'در حال حاضر برنامه دعوت فعالی وجود ندارد'
            ], 404);
        }
        
        return response()->json([
            'data' => $program
        ]);
    }
    
    /**
     * دریافت لیست برنامه‌های دعوت
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $query = ReferralProgram::query();

        // فیلتر بر اساس وضعیت فعال/غیرفعال
        if ($request->has('is_active')) {
            $isActive = $request->is_active === 'true' || $request->is_active === '1';
            $query->where('is_active', $isActive);
        }

        // جستجو
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSortFields = ['name', 'created_at', 'starts_at', 'expires_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        // صفحه‌بندی
        $perPage = $request->input('per_page', 15);
        $programs = $query->paginate($perPage);

        return response()->json([
            'data' => $programs->items(),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ]
        ]);
    }

    /**
     * ایجاد برنامه دعوت جدید
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'referrer_reward_type' => 'required|in:fixed,percentage,subscription',
            'referrer_reward_value' => 'required|numeric|min:0',
            'referred_reward_type' => 'required|in:fixed,percentage,subscription',
            'referred_reward_value' => 'required|numeric|min:0',
            'referral_limit' => 'nullable|integer|min:1',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'subscription_plan_id' => 'nullable|required_if:referrer_reward_type,subscription,referred_reward_type,subscription|exists:subscription_plans,id',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // اگر این برنامه فعال است، سایر برنامه‌های فعال را غیرفعال کن
        if ($request->input('is_active', true)) {
            ReferralProgram::where('is_active', true)->update(['is_active' => false]);
        }

        $program = ReferralProgram::create([
            'name' => $request->name,
            'description' => $request->description,
            'referrer_reward_type' => $request->referrer_reward_type,
            'referrer_reward_value' => $request->referrer_reward_value,
            'referred_reward_type' => $request->referred_reward_type,
            'referred_reward_value' => $request->referred_reward_value,
            'referral_limit' => $request->referral_limit,
            'minimum_purchase_amount' => $request->minimum_purchase_amount,
            'subscription_plan_id' => $request->subscription_plan_id,
            'is_active' => $request->input('is_active', true),
            'starts_at' => $request->starts_at ?? now(),
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'message' => 'برنامه دعوت با موفقیت ایجاد شد',
            'data' => $program
        ], 201);
    }

    /**
     * نمایش اطلاعات یک برنامه دعوت
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $program = ReferralProgram::findOrFail($id);

        // آمار استفاده
        $referralCount = Referral::where('referral_program_id', $id)->count();
        $successfulReferralCount = Referral::where('referral_program_id', $id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'data' => $program,
            'stats' => [
                'total_referrals' => $referralCount,
                'successful_referrals' => $successfulReferralCount,
                'conversion_rate' => $referralCount > 0 ? ($successfulReferralCount / $referralCount) * 100 : 0,
            ]
        ]);
    }

    /**
     * به‌روزرسانی برنامه دعوت
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $program = ReferralProgram::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'referrer_reward_type' => 'sometimes|required|in:fixed,percentage,subscription',
            'referrer_reward_value' => 'sometimes|required|numeric|min:0',
            'referred_reward_type' => 'sometimes|required|in:fixed,percentage,subscription',
            'referred_reward_value' => 'sometimes|required|numeric|min:0',
            'referral_limit' => 'nullable|integer|min:1',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'subscription_plan_id' => 'nullable|required_if:referrer_reward_type,subscription,referred_reward_type,subscription|exists:subscription_plans,id',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // اگر این برنامه فعال است، سایر برنامه‌های فعال را غیرفعال کن
        if ($request->filled('is_active') && $request->is_active) {
            ReferralProgram::where('id', '!=', $id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $program->update($request->only([
            'name',
            'description',
            'referrer_reward_type',
            'referrer_reward_value',
            'referred_reward_type',
            'referred_reward_value',
            'referral_limit',
            'minimum_purchase_amount',
            'subscription_plan_id',
            'is_active',
            'starts_at',
            'expires_at',
        ]));

        return response()->json([
            'message' => 'برنامه دعوت با موفقیت به‌روزرسانی شد',
            'data' => $program
        ]);
    }

    /**
     * حذف برنامه دعوت
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $program = ReferralProgram::findOrFail($id);
        
        // بررسی استفاده از برنامه دعوت
        $usageCount = Referral::where('referral_program_id', $id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'message' => 'این برنامه دعوت در حال استفاده است و نمی‌تواند حذف شود',
                'usage_count' => $usageCount
            ], 400);
        }

        $program->delete();

        return response()->json([
            'message' => 'برنامه دعوت با موفقیت حذف شد'
        ]);
    }

    /**
     * ثبت دعوت کاربر جدید
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerReferral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|exists:users,referral_code',
            'referred_user_id' => 'required|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $referrerUser = User::where('referral_code', $request->referral_code)->first();
        $referredUser = User::findOrFail($request->referred_user_id);
        
        // بررسی عدم تکراری بودن ثبت دعوت
        $existingReferral = Referral::where('referred_user_id', $referredUser->id)->first();
        if ($existingReferral) {
            return response()->json([
                'message' => 'این کاربر قبلاً توسط کد دعوت دیگری ثبت‌نام کرده است'
            ], 400);
        }
        
        // بررسی اینکه کاربر خودش را دعوت نکند
        if ($referrerUser->id === $referredUser->id) {
            return response()->json([
                'message' => 'شما نمی‌توانید خودتان را دعوت کنید'
            ], 400);
        }
        
        // بررسی برنامه دعوت فعال
        $program = ReferralProgram::where('is_active', true)
            ->where(function($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
            
        if (!$program) {
            return response()->json([
                'message' => 'در حال حاضر برنامه دعوت فعالی وجود ندارد'
            ], 404);
        }
        
        // بررسی محدودیت تعداد دعوت
        if ($program->referral_limit) {
            $referralCount = Referral::where('referrer_user_id', $referrerUser->id)
                ->where('referral_program_id', $program->id)
                ->count();
                
            if ($referralCount >= $program->referral_limit) {
                return response()->json([
                    'message' => 'کاربر دعوت‌کننده به حداکثر تعداد دعوت مجاز رسیده است'
                ], 400);
            }
        }
        
        // ثبت دعوت
        $referral = Referral::create([
            'referral_program_id' => $program->id,
            'referrer_user_id' => $referrerUser->id,
            'referred_user_id' => $referredUser->id,
            'status' => 'pending',
        ]);
        
        return response()->json([
            'message' => 'دعوت با موفقیت ثبت شد',
            'data' => $referral
        ], 201);
    }
    
    /**
     * تکمیل دعوت (پس از خرید)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function completeReferral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_id' => 'required|exists:referrals,id',
            'purchase_amount' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $referral = Referral::findOrFail($request->referral_id);
        
        // بررسی وضعیت دعوت
        if ($referral->status !== 'pending') {
            return response()->json([
                'message' => 'این دعوت قبلاً تکمیل یا لغو شده است'
            ], 400);
        }
        
        // بررسی برنامه دعوت
        $program = ReferralProgram::findOrFail($referral->referral_program_id);
        
        // بررسی حداقل مبلغ خرید
        if ($program->minimum_purchase_amount && $request->purchase_amount < $program->minimum_purchase_amount) {
            return response()->json([
                'message' => 'مبلغ خرید کمتر از حداقل مبلغ تعیین شده برای برنامه دعوت است',
                'minimum_amount' => $program->minimum_purchase_amount
            ], 400);
        }
        
        // محاسبه پاداش دعوت‌کننده
        $referrerReward = 0;
        if ($program->referrer_reward_type === 'fixed') {
            $referrerReward = $program->referrer_reward_value;
        } elseif ($program->referrer_reward_type === 'percentage') {
            $referrerReward = ($request->purchase_amount * $program->referrer_reward_value) / 100;
        }
        
        // محاسبه پاداش دعوت‌شونده
        $referredReward = 0;
        if ($program->referred_reward_type === 'fixed') {
            $referredReward = $program->referred_reward_value;
        } elseif ($program->referred_reward_type === 'percentage') {
            $referredReward = ($request->purchase_amount * $program->referred_reward_value) / 100;
        }
        
        // به‌روزرسانی دعوت
        $referral->update([
            'status' => 'completed',
            'completed_at' => now(),
            'purchase_amount' => $request->purchase_amount,
            'referrer_reward' => $referrerReward,
            'referred_reward' => $referredReward,
        ]);
        
        // پرداخت پاداش به کاربران
        // TODO: پیاده‌سازی پرداخت پاداش (اضافه کردن اعتبار، تخفیف یا اشتراک)
        
        return response()->json([
            'message' => 'دعوت با موفقیت تکمیل شد',
            'data' => $referral
        ]);
    }
    
    /**
     * لغو دعوت
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancelReferral(Request $request, $id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        $referral = Referral::findOrFail($id);
        
        // بررسی وضعیت دعوت
        if ($referral->status === 'completed') {
            return response()->json([
                'message' => 'دعوت‌های تکمیل شده قابل لغو نیستند'
            ], 400);
        }
        
        $referral->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason', 'لغو توسط ادمین'),
        ]);
        
        return response()->json([
            'message' => 'دعوت با موفقیت لغو شد',
            'data' => $referral
        ]);
    }
    
    /**
     * دریافت آمار دعوت کاربر فعلی
     *
     * @return \Illuminate\Http\Response
     */
    public function myReferralStats()
    {
        $user = Auth::user();
        
        // دعوت‌های ارسالی
        $sentReferrals = Referral::where('referrer_user_id', $user->id)->get();
        $sentReferralsCount = $sentReferrals->count();
        $completedSentReferrals = $sentReferrals->where('status', 'completed')->count();
        $totalReferrerReward = $sentReferrals->where('status', 'completed')->sum('referrer_reward');
        
        // دعوت دریافتی
        $receivedReferral = Referral::where('referred_user_id', $user->id)->first();
        
        return response()->json([
            'data' => [
                'referral_code' => $user->referral_code,
                'referral_url' => $user->referral_code ? url('/register?ref=' . $user->referral_code) : null,
                'sent_referrals' => [
                    'total' => $sentReferralsCount,
                    'completed' => $completedSentReferrals,
                    'pending' => $sentReferrals->where('status', 'pending')->count(),
                    'cancelled' => $sentReferrals->where('status', 'cancelled')->count(),
                    'conversion_rate' => $sentReferralsCount > 0 ? ($completedSentReferrals / $sentReferralsCount) * 100 : 0,
                ],
                'total_rewards' => $totalReferrerReward,
                'referred_by' => $receivedReferral ? [
                    'user' => $receivedReferral->referrerUser->name,
                    'status' => $receivedReferral->status,
                    'reward' => $receivedReferral->referred_reward,
                    'date' => $receivedReferral->created_at,
                ] : null,
            ]
        ]);
    }
    
    /**
     * دریافت لیست دعوت‌های ارسالی کاربر فعلی
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function myReferrals(Request $request)
    {
        $user = Auth::user();
        
        $query = Referral::with('referredUser')
            ->where('referrer_user_id', $user->id);
            
        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // مرتب‌سازی
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy('created_at', $sortDirection === 'asc' ? 'asc' : 'desc');
        
        // صفحه‌بندی
        $perPage = $request->input('per_page', 10);
        $referrals = $query->paginate($perPage);
        
        return response()->json([
            'data' => $referrals->items(),
            'meta' => [
                'current_page' => $referrals->currentPage(),
                'last_page' => $referrals->lastPage(),
                'per_page' => $referrals->perPage(),
                'total' => $referrals->total(),
            ]
        ]);
    }
    
    /**
     * دریافت گزارش کامل دعوت‌ها (ادمین)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function referralReport(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }
        
        $query = Referral::with(['referrerUser', 'referredUser', 'referralProgram']);
        
        // فیلتر بر اساس برنامه دعوت
        if ($request->filled('program_id')) {
            $query->where('referral_program_id', $request->program_id);
        }
        
        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // فیلتر بر اساس کاربر دعوت‌کننده
        if ($request->filled('referrer_id')) {
            $query->where('referrer_user_id', $request->referrer_id);
        }
        
        // فیلتر بر اساس کاربر دعوت‌شده
        if ($request->filled('referred_id')) {
            $query->where('referred_user_id', $request->referred_id);
        }
        
        // فیلتر بر اساس بازه زمانی
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSortFields = ['created_at', 'completed_at', 'purchase_amount', 'referrer_reward', 'referred_reward'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }
        
        // صفحه‌بندی
        $perPage = $request->input('per_page', 15);
        $referrals = $query->paginate($perPage);
        
        // محاسبه آمار کلی
        $totalCount = $query->count();
        $completedCount = $query->where('status', 'completed')->count();
        $totalReferrerRewards = $query->where('status', 'completed')->sum('referrer_reward');
        $totalReferredRewards = $query->where('status', 'completed')->sum('referred_reward');
        
        return response()->json([
            'data' => $referrals->items(),
            'meta' => [
                'current_page' => $referrals->currentPage(),
                'last_page' => $referrals->lastPage(),
                'per_page' => $referrals->perPage(),
                'total' => $referrals->total(),
                'stats' => [
                    'total_referrals' => $totalCount,
                    'completed_referrals' => $completedCount,
                    'conversion_rate' => $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0,
                    'total_referrer_rewards' => $totalReferrerRewards,
                    'total_referred_rewards' => $totalReferredRewards,
                    'total_rewards' => $totalReferrerRewards + $totalReferredRewards,
                ]
            ]
        ]);
    }
} 