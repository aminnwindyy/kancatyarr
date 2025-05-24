<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\DiscountCodeUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DiscountCodeController extends Controller
{
    /**
     * کدهای تخفیف ثابت (به جای استفاده از دیتابیس)
     */
    private $discountCodes = [
        'WELCOME1403' => [
            'code' => 'WELCOME1403',
            'discount' => 20,
            'description' => 'کد تخفیف خوش‌آمدگویی',
            'expiry_date' => '1403/06/31'
        ],
        'SUMMER1403' => [
            'code' => 'SUMMER1403',
            'discount' => 15,
            'description' => 'تخفیف ویژه تابستان',
            'expiry_date' => '1403/06/31'
        ],
        'SPECIAL50' => [
            'code' => 'SPECIAL50',
            'discount' => 50,
            'description' => 'تخفیف ویژه مشتریان',
            'expiry_date' => '1403/12/29'
        ],
    ];

    /**
     * دریافت لیست کدهای تخفیف
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

        $query = DiscountCode::query();

        // جستجو بر اساس کد
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // فیلتر بر اساس نوع
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فیلتر بر اساس وضعیت فعال/غیرفعال
        if ($request->has('is_active')) {
            $isActive = $request->is_active === 'true' || $request->is_active === '1';
            $query->where('is_active', $isActive);
        }

        // فیلتر کدهای منقضی شده/نشده
        if ($request->has('expired')) {
            $expired = $request->expired === 'true' || $request->expired === '1';
            if ($expired) {
                $query->where('expires_at', '<', now());
            } else {
                $query->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            }
        }

        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSortFields = ['code', 'type', 'value', 'created_at', 'expires_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        // اضافه کردن تعداد استفاده‌ها
        $query->withCount('usages');

        // صفحه‌بندی
        $perPage = $request->input('per_page', 15);
        $discountCodes = $query->paginate($perPage);

        return response()->json([
            'data' => $discountCodes->items(),
            'meta' => [
                'current_page' => $discountCodes->currentPage(),
                'last_page' => $discountCodes->lastPage(),
                'per_page' => $discountCodes->perPage(),
                'total' => $discountCodes->total(),
            ]
        ]);
    }

    /**
     * ایجاد کد تخفیف جدید
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
            'code' => 'nullable|string|max:50|unique:discount_codes,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'plans' => 'nullable|array',
            'plans.*' => 'exists:subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // اگر کد تخفیف ارائه نشده، یک کد تصادفی تولید کن
        $code = $request->code ?? Str::upper(Str::random(8));

        // محدودیت مقدار برای کد تخفیف درصدی
        if ($request->type === 'percentage' && $request->value > 100) {
            return response()->json([
                'message' => 'مقدار تخفیف درصدی نمی‌تواند بیشتر از 100 باشد',
            ], 422);
        }

        $discountCode = DiscountCode::create([
            'code' => $code,
            'type' => $request->type,
            'value' => $request->value,
            'description' => $request->description,
            'max_uses' => $request->max_uses,
            'max_uses_per_user' => $request->max_uses_per_user,
            'min_order_amount' => $request->min_order_amount,
            'is_active' => $request->input('is_active', true),
            'expires_at' => $request->expires_at,
        ]);

        // اضافه کردن محصولات قابل استفاده (اگر وجود داشته باشند)
        if ($request->filled('products')) {
            $discountCode->products()->attach($request->products);
        }

        // اضافه کردن پلن‌های قابل استفاده (اگر وجود داشته باشند)
        if ($request->filled('plans')) {
            $discountCode->plans()->attach($request->plans);
        }

        return response()->json([
            'message' => 'کد تخفیف با موفقیت ایجاد شد',
            'data' => $discountCode
        ], 201);
    }

    /**
     * نمایش اطلاعات یک کد تخفیف
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

        $discountCode = DiscountCode::with(['products', 'plans'])
            ->withCount('usages')
            ->findOrFail($id);

        // بررسی وضعیت انقضا
        $discountCode->is_expired = $discountCode->isExpired();

        return response()->json([
            'data' => $discountCode
        ]);
    }

    /**
     * به‌روزرسانی کد تخفیف
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

        $discountCode = DiscountCode::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:discount_codes,code,' . $id,
            'type' => 'sometimes|required|in:percentage,fixed',
            'value' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'plans' => 'nullable|array',
            'plans.*' => 'exists:subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // محدودیت مقدار برای کد تخفیف درصدی
        if (
            ($request->filled('type') && $request->type === 'percentage') && 
            ($request->filled('value') && $request->value > 100)
        ) {
            return response()->json([
                'message' => 'مقدار تخفیف درصدی نمی‌تواند بیشتر از 100 باشد',
            ], 422);
        }

        $discountCode->update($request->only([
            'code',
            'type',
            'value',
            'description',
            'max_uses',
            'max_uses_per_user',
            'min_order_amount',
            'is_active',
            'expires_at',
        ]));

        // به‌روزرسانی محصولات قابل استفاده (اگر ارائه شده باشند)
        if ($request->has('products')) {
            $discountCode->products()->sync($request->products);
        }

        // به‌روزرسانی پلن‌های قابل استفاده (اگر ارائه شده باشند)
        if ($request->has('plans')) {
            $discountCode->plans()->sync($request->plans);
        }

        return response()->json([
            'message' => 'کد تخفیف با موفقیت به‌روزرسانی شد',
            'data' => $discountCode->fresh(['products', 'plans'])
        ]);
    }

    /**
     * حذف کد تخفیف
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

        $discountCode = DiscountCode::findOrFail($id);
        $discountCode->delete();

        return response()->json([
            'message' => 'کد تخفیف با موفقیت حذف شد'
        ]);
    }

    /**
     * اعتبارسنجی کد تخفیف
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:discount_codes,code',
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'product_id' => 'nullable|exists:products,id',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'کد تخفیف نامعتبر است',
                'errors' => $validator->errors(),
                'valid' => false
            ], 422);
        }

        $code = $request->code;
        $userId = Auth::id();
        
        // دریافت کد تخفیف
        $discountCode = DiscountCode::where('code', $code)->first();

        // بررسی وضعیت فعال بودن کد
        if (!$discountCode->is_active) {
            return response()->json([
                'message' => 'کد تخفیف غیرفعال است',
                'valid' => false
            ], 422);
        }

        // بررسی تاریخ انقضا
        if ($discountCode->isExpired()) {
            return response()->json([
                'message' => 'کد تخفیف منقضی شده است',
                'valid' => false
            ], 422);
        }

        // بررسی حداکثر استفاده کلی
        if ($discountCode->max_uses && $discountCode->usages()->count() >= $discountCode->max_uses) {
            return response()->json([
                'message' => 'کد تخفیف به حداکثر استفاده مجاز رسیده است',
                'valid' => false
            ], 422);
        }

        // بررسی حداکثر استفاده توسط کاربر
        if ($discountCode->max_uses_per_user) {
            $userUsageCount = $discountCode->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $discountCode->max_uses_per_user) {
                return response()->json([
                    'message' => 'شما قبلاً از این کد تخفیف استفاده کرده‌اید',
                    'valid' => false
                ], 422);
            }
        }

        // بررسی حداقل مبلغ سفارش
        if ($discountCode->min_order_amount && $request->filled('amount')) {
            if ($request->amount < $discountCode->min_order_amount) {
                return response()->json([
                    'message' => 'مبلغ سفارش کمتر از حداقل مبلغ تعیین شده برای استفاده از این کد تخفیف است',
                    'valid' => false,
                    'min_amount' => $discountCode->min_order_amount
                ], 422);
            }
        }

        // بررسی محدودیت محصول
        if ($request->filled('product_id') && $discountCode->products()->count() > 0) {
            if (!$discountCode->products()->where('product_id', $request->product_id)->exists()) {
                return response()->json([
                    'message' => 'این کد تخفیف برای محصول انتخاب شده قابل استفاده نیست',
                    'valid' => false
                ], 422);
            }
        }

        // بررسی محدودیت پلن
        if ($request->filled('plan_id') && $discountCode->plans()->count() > 0) {
            if (!$discountCode->plans()->where('plan_id', $request->plan_id)->exists()) {
                return response()->json([
                    'message' => 'این کد تخفیف برای پلن انتخاب شده قابل استفاده نیست',
                    'valid' => false
                ], 422);
            }
        }

        // محاسبه مبلغ تخفیف
        $discountAmount = 0;
        if ($request->filled('amount')) {
            if ($discountCode->type === 'percentage') {
                $discountAmount = ($request->amount * $discountCode->value) / 100;
            } else {
                $discountAmount = $discountCode->value;
                // تخفیف ثابت نباید از مبلغ کل بیشتر باشد
                if ($discountAmount > $request->amount) {
                    $discountAmount = $request->amount;
                }
            }
        }

        return response()->json([
            'message' => 'کد تخفیف معتبر است',
            'valid' => true,
            'discount' => [
                'type' => $discountCode->type,
                'value' => $discountCode->value,
                'amount' => $discountAmount
            ],
            'discount_code' => $discountCode
        ]);
    }

    /**
     * استفاده از کد تخفیف
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:discount_codes,code',
            'order_id' => 'nullable|string',
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'product_id' => 'nullable|exists:products,id',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $code = $request->code;
        $userId = Auth::id();
        
        // دریافت کد تخفیف
        $discountCode = DiscountCode::where('code', $code)->first();

        // بررسی اعتبار کد (همان بررسی‌های متد validate)
        // ... تکرار کدهای validate برای بررسی

        // اگر اعتبارسنجی موفق بود، کد استفاده از تخفیف را ثبت کن
        $discountAmount = 0;
        if ($discountCode->type === 'percentage') {
            $discountAmount = ($request->amount * $discountCode->value) / 100;
        } else {
            $discountAmount = $discountCode->value;
            // تخفیف ثابت نباید از مبلغ کل بیشتر باشد
            if ($discountAmount > $request->amount) {
                $discountAmount = $request->amount;
            }
        }

        // ثبت استفاده از کد تخفیف
        $usage = DiscountCodeUsage::create([
            'discount_code_id' => $discountCode->id,
            'user_id' => $userId,
            'order_id' => $request->order_id,
            'plan_id' => $request->plan_id,
            'product_id' => $request->product_id,
            'amount' => $request->amount,
            'discount_amount' => $discountAmount,
        ]);

        return response()->json([
            'message' => 'کد تخفیف با موفقیت اعمال شد',
            'data' => [
                'original_amount' => $request->amount,
                'discount_amount' => $discountAmount,
                'final_amount' => $request->amount - $discountAmount,
                'usage' => $usage
            ]
        ]);
    }

    /**
     * گزارش استفاده از کدهای تخفیف
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function usageReport(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $query = DiscountCodeUsage::with(['discountCode', 'user']);

        // فیلتر بر اساس کد تخفیف
        if ($request->filled('discount_code_id')) {
            $query->where('discount_code_id', $request->discount_code_id);
        }

        // فیلتر بر اساس کاربر
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // فیلتر بر اساس بازه زمانی
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // صفحه‌بندی
        $perPage = $request->input('per_page', 15);
        $usages = $query->latest()->paginate($perPage);

        // محاسبه آمار کلی
        $totalDiscountAmount = $query->sum('discount_amount');
        $totalUsageCount = $query->count();

        return response()->json([
            'data' => $usages->items(),
            'meta' => [
                'current_page' => $usages->currentPage(),
                'last_page' => $usages->lastPage(),
                'per_page' => $usages->perPage(),
                'total' => $usages->total(),
                'stats' => [
                    'total_discount_amount' => $totalDiscountAmount,
                    'total_usage_count' => $totalUsageCount
                ]
            ]
        ]);
    }

    /**
     * دریافت لیست کدهای تخفیف فعال
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveCodes()
    {
        return response()->json([
            'status' => 'success',
            'data' => array_values($this->discountCodes)
        ]);
    }

    /**
     * بررسی اعتبار کد تخفیف
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');
        
        if (array_key_exists($code, $this->discountCodes)) {
            return response()->json([
                'status' => 'success',
                'data' => $this->discountCodes[$code]
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'کد تخفیف نامعتبر است'
        ], 404);
    }

    /**
     * ثبت رویداد کپی کد تخفیف (بدون ذخیره در دیتابیس)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackCopy(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');
        $userId = $request->user()->id ?? 'guest';
        
        // ثبت در لاگ به جای دیتابیس
        Log::info('Discount code copied', [
            'code' => $code,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString()
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'رویداد با موفقیت ثبت شد'
        ]);
    }

    /**
     * دریافت کد تخفیف فعال برای نمایش در بنر
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveBannerCode()
    {
        // انتخاب یک کد تخفیف برای نمایش در بنر
        // در اینجا می‌توانید شرایط مختلفی را بررسی کنید، مثلاً فصل سال، کمپین‌های فعال و غیره
        
        // برای مثال: انتخاب یک کد تخفیف تصادفی
        $randomCode = array_rand($this->discountCodes);
        
        return response()->json([
            'status' => 'success',
            'data' => $this->discountCodes[$randomCode]
        ]);
    }
} 