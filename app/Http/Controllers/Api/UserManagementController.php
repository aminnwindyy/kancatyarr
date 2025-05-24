<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    /**
     * دریافت لیست کاربران
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

        $query = User::query();

        // جستجو بر اساس نام، ایمیل یا شماره موبایل
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%');
            });
        }

        // فیلتر بر اساس نقش
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس تاریخ ثبت‌نام
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // مرتب‌سازی
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSortFields = ['id', 'name', 'email', 'created_at', 'status'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        // اضافه کردن اطلاعات اشتراک
        $query->with(['subscriptions' => function($q) {
            $q->where('status', 'active')
              ->where('expires_at', '>', now())
              ->latest();
        }]);

        // صفحه‌بندی
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * ایجاد کاربر جدید
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
            'email' => 'required|string|email|max:255|unique:users,email',
            'mobile' => 'required|string|unique:users,mobile',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'status' => 'nullable|in:active,inactive,banned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'status' => $request->input('status', 'active'),
        ]);

        // تخصیص نقش
        $user->assignRole($request->role);

        return response()->json([
            'message' => 'کاربر با موفقیت ایجاد شد',
            'data' => $user
        ], 201);
    }

    /**
     * نمایش اطلاعات یک کاربر
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // بررسی دسترسی ادمین یا خود کاربر
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('admin') && $currentUser->id != $id) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $user = User::with([
            'roles',
            'subscriptions' => function($q) {
                $q->with('plan')->latest();
            }
        ])->findOrFail($id);

        return response()->json([
            'data' => $user
        ]);
    }

    /**
     * به‌روزرسانی اطلاعات کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // بررسی دسترسی ادمین یا خود کاربر
        $currentUser = Auth::user();
        if (!$currentUser->hasRole('admin') && $currentUser->id != $id) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'mobile' => 'sometimes|required|string|unique:users,mobile,' . $id,
            'password' => 'nullable|string|min:8',
            'status' => 'nullable|in:active,inactive,banned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        // تهیه آرایه داده‌های به‌روزرسانی
        $updateData = $request->only(['name', 'email', 'mobile', 'status']);
        
        // به‌روزرسانی رمز عبور در صورت وجود
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // اگر ادمین است، وضعیت را به‌روزرسانی کن
        if ($currentUser->hasRole('admin') && $request->filled('status')) {
            $updateData['status'] = $request->status;
        }

        $user->update($updateData);

        // فقط ادمین می‌تواند نقش‌ها را به‌روزرسانی کند
        if ($currentUser->hasRole('admin') && $request->filled('role')) {
            // ابتدا تمام نقش‌های موجود را حذف کن
            $user->roles()->detach();
            // سپس نقش جدید را اضافه کن
            $user->assignRole($request->role);
        }

        return response()->json([
            'message' => 'اطلاعات کاربر با موفقیت به‌روزرسانی شد',
            'data' => $user->fresh(['roles'])
        ]);
    }

    /**
     * حذف کاربر
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

        $user = User::findOrFail($id);

        // بررسی اینکه کاربر خود را نمی‌تواند حذف کند
        if (Auth::id() === $user->id) {
            return response()->json([
                'message' => 'شما نمی‌توانید حساب کاربری خود را حذف کنید'
            ], 400);
        }

        // غیرفعال کردن به جای حذف کامل
        $user->update([
            'status' => 'banned',
            'email' => $user->email . '_deleted_' . time(),
            'mobile' => $user->mobile . '_deleted_' . time(),
        ]);

        // آزادسازی نقش‌ها
        $user->roles()->detach();

        return response()->json([
            'message' => 'کاربر با موفقیت غیرفعال شد'
        ]);
    }

    /**
     * دریافت لیست نقش‌ها
     *
     * @return \Illuminate\Http\Response
     */
    public function getRoles()
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $roles = Role::with('permissions')->get();

        return response()->json([
            'data' => $roles
        ]);
    }

    /**
     * ایجاد نقش جدید
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeRole(Request $request)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::create(['name' => $request->name]);

        if ($request->filled('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return response()->json([
            'message' => 'نقش با موفقیت ایجاد شد',
            'data' => $role->load('permissions')
        ], 201);
    }

    /**
     * به‌روزرسانی نقش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateRole(Request $request, $id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اعتبارسنجی داده‌ها',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->filled('name')) {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'نقش با موفقیت به‌روزرسانی شد',
            'data' => $role->load('permissions')
        ]);
    }

    /**
     * دریافت لیست دسترسی‌ها
     *
     * @return \Illuminate\Http\Response
     */
    public function getPermissions()
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $permissions = Permission::all();

        return response()->json([
            'data' => $permissions
        ]);
    }

    /**
     * تغییر وضعیت کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changeStatus(Request $request, $id)
    {
        // بررسی دسترسی ادمین
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,banned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'وضعیت نامعتبر است',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);

        // بررسی اینکه کاربر خود را نمی‌تواند مسدود کند
        if (Auth::id() === $user->id && $request->status === 'banned') {
            return response()->json([
                'message' => 'شما نمی‌توانید حساب کاربری خود را مسدود کنید'
            ], 400);
        }

        $user->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'وضعیت کاربر با موفقیت تغییر کرد',
            'data' => $user
        ]);
    }

    /**
     * دریافت آمار کاربران
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

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();
        $bannedUsers = User::where('status', 'banned')->count();

        $newUsersToday = User::whereDate('created_at', today())->count();
        $newUsersThisWeek = User::whereBetween('created_at', [now()->startOfWeek(), now()])->count();
        $newUsersThisMonth = User::whereBetween('created_at', [now()->startOfMonth(), now()])->count();

        $activeSubscriptions = UserSubscription::where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'banned_users' => $bannedUsers,
                'new_users_today' => $newUsersToday,
                'new_users_this_week' => $newUsersThisWeek,
                'new_users_this_month' => $newUsersThisMonth,
                'active_subscriptions' => $activeSubscriptions,
            ]
        ]);
    }
} 