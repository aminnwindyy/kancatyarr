<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * دریافت لیست پلن‌های اشتراک
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SubscriptionPlan::query();

        // فیلتر بر اساس وضعیت فعال/غیرفعال
        if ($request->has('is_active')) {
            $isActive = $request->is_active === 'true' || $request->is_active === '1';
            $query->where('is_active', $isActive);
        } else {
            // به طور پیش‌فرض فقط پلن‌های فعال نمایش داده می‌شوند
            $query->where('is_active', true);
        }

        // فیلتر برای پلن‌های ویژه
        if ($request->has('is_featured')) {
            $isFeatured = $request->is_featured === 'true' || $request->is_featured === '1';
            $query->where('is_featured', $isFeatured);
        }

        // جستجو
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'sort_order');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSortFields = ['name', 'price', 'duration_days', 'created_at', 'sort_order'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('sort_order', 'asc');
        }

        // صفحه‌بندی
        $perPage = $request->input('per_page', 10);
        $plans = $query->paginate($perPage);

        return response()->json([
            'data' => $plans->items(),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ]
        ]);
    }

    /**
     * ذخیره‌سازی پلن اشتراک جدید
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
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'max_users' => 'nullable|integer|min:1',
            'discount_percentage' => 'integer|min:0|max:100',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $plan = SubscriptionPlan::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'features' => $request->features,
            'is_featured' => $request->input('is_featured', false),
            'is_active' => $request->input('is_active', true),
            'max_users' => $request->max_users,
            'discount_percentage' => $request->input('discount_percentage', 0),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return response()->json([
            'message' => 'پلن اشتراک با موفقیت ایجاد شد',
            'data' => $plan
        ], 201);
    }

    /**
     * نمایش اطلاعات یک پلن اشتراک
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        return response()->json([
            'data' => $plan
        ]);
    }

    /**
     * به‌روزرسانی یک پلن اشتراک
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

        $plan = SubscriptionPlan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_days' => 'sometimes|required|integer|min:1',
            'features' => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'max_users' => 'nullable|integer|min:1',
            'discount_percentage' => 'integer|min:0|max:100',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $plan->update($request->only([
            'name',
            'description',
            'price',
            'duration_days',
            'features',
            'is_featured',
            'is_active',
            'max_users',
            'discount_percentage',
            'sort_order',
        ]));

        return response()->json([
            'message' => 'پلن اشتراک با موفقیت به‌روزرسانی شد',
            'data' => $plan
        ]);
    }

    /**
     * حذف یک پلن اشتراک
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

        $plan = SubscriptionPlan::findOrFail($id);
        
        // بررسی استفاده از پلن
        $usageCount = UserSubscription::where('plan_id', $id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'message' => 'این پلن اشتراک در حال استفاده است و نمی‌تواند حذف شود',
                'usage_count' => $usageCount
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'message' => 'پلن اشتراک با موفقیت حذف شد'
        ]);
    }

    /**
     * خرید اشتراک توسط کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,plan_id,is_active,1',
            'payment_id' => 'nullable|string',
            'auto_renew' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // محاسبه قیمت نهایی با اعمال تخفیف
        $finalPrice = $plan->getDiscountedPriceAttribute();

        // محاسبه تاریخ شروع و پایان
        $startsAt = now();
        $expiresAt = now()->addDays($plan->duration_days);
        $nextBillingDate = $request->input('auto_renew', false) ? $expiresAt : null;

        // ایجاد اشتراک جدید
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->plan_id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'price_paid' => $finalPrice,
            'payment_id' => $request->payment_id,
            'status' => 'active',
            'auto_renew' => $request->input('auto_renew', false),
            'next_billing_date' => $nextBillingDate,
        ]);

        // به‌روزرسانی اطلاعات کاربر (اختیاری)
        // $user->update(['subscription_status' => 'active']);

        return response()->json([
            'message' => 'اشتراک با موفقیت ایجاد شد',
            'data' => $subscription
        ], 201);
    }

    /**
     * دریافت اشتراک فعلی کاربر
     *
     * @return \Illuminate\Http\Response
     */
    public function currentSubscription()
    {
        $user = Auth::user();
        
        $subscription = UserSubscription::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('starts_at')
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'شما اشتراک فعالی ندارید'
            ], 404);
        }

        return response()->json([
            'data' => $subscription
        ]);
    }

    /**
     * لغو اشتراک توسط کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancelSubscription(Request $request, $id)
    {
        $user = Auth::user();
        
        $subscription = UserSubscription::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $subscription->cancel();

        return response()->json([
            'message' => 'اشتراک با موفقیت لغو شد',
            'data' => $subscription
        ]);
    }

    /**
     * تمدید اشتراک
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function renewSubscription(Request $request, $id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $subscription = UserSubscription::findOrFail($id);
        $plan = SubscriptionPlan::findOrFail($subscription->plan_id);

        $subscription->renew($plan->duration_days);

        return response()->json([
            'message' => 'اشتراک با موفقیت تمدید شد',
            'data' => $subscription
        ]);
    }

    /**
     * دریافت لیست اشتراک‌های کاربر
     *
     * @param  int  $userId
     * @return \Illuminate\Http\Response
     */
    public function getUserSubscriptions($userId)
    {
        // بررسی دسترسی ادمین یا خود کاربر
        $user = Auth::user();
        if (!$user->hasRole('admin') && $user->id != $userId) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $subscriptions = UserSubscription::with('plan')
            ->where('user_id', $userId)
            ->latest('starts_at')
            ->paginate(10);

        return response()->json([
            'data' => $subscriptions->items(),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ]
        ]);
    }
} 