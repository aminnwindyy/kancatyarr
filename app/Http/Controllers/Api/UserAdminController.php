<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Message;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\OrderShipping;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;

class UserAdminController extends Controller
{
    /**
     * Obtener estadísticas generales de usuarios
     */
    public function getStats(Request $request)
    {
        // Verificar permiso
        if (!$request->user()->can('users.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        $totalUsers = User::where('is_admin', false)->count();
        $activeUsers = User::where('is_admin', false)->where('is_active', true)->count();
        $inactiveUsers = User::where('is_admin', false)->where('is_active', false)->count();

        $withSubscription = User::whereHas('subscriptions', function($query) {
            $query->where('end_date', '>=', now());
        })->count();

        $withoutSubscription = $totalUsers - $withSubscription;

        $expiredSubscription = User::whereHas('subscriptions', function($query) {
            $query->where('end_date', '<', now());
        })->count();

        $registeredToday = User::where('is_admin', false)
            ->whereDate('created_at', Carbon::today())
            ->count();

        return response()->json([
            'total' => $totalUsers,
            'active' => $activeUsers,
            'inactive' => $inactiveUsers,
            'with_subscription' => $withSubscription,
            'without_subscription' => $withoutSubscription,
            'expired_subscription' => $expiredSubscription,
            'registered_today' => $registeredToday
        ]);
    }

    /**
     * Obtener lista de usuarios con filtros
     */
    public function index(Request $request)
    {
        // Verificar permiso
        if (!$request->user()->can('users.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        $query = User::where('is_admin', false);

        // Aplicar filtros
        $filter = $request->input('filter');

        if ($filter) {
            switch ($filter) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'with_subscription':
                    $query->whereHas('subscriptions', function($q) {
                        $q->where('end_date', '>=', now());
                    });
                    break;
                case 'without_subscription':
                    $query->whereDoesntHave('subscriptions', function($q) {
                        $q->where('end_date', '>=', now());
                    });
                    break;
                case 'expired_subscription':
                    $query->whereHas('subscriptions', function($q) {
                        $q->where('end_date', '<', now());
                    });
                    break;
                case 'registered_today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
            }
        }

        // Buscar por término
        $search = $request->input('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Obtener usuarios paginados
        $users = $query->select('user_id', 'first_name', 'last_name', 'email', 'phone', 'is_active', 'created_at', 'profile_image')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($users);
    }

    /**
     * Obtener detalles de un usuario específico
     */
    public function show(Request $request, $id)
    {
        // Verificar permiso
        if (!$request->user()->can('users.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Obtener usuario con relaciones
        $user = User::with(['subscriptions', 'orders', 'wallet'])
            ->findOrFail($id);

        if ($user->is_admin) {
            return response()->json(['message' => 'این کاربر ادمین است و نمایش داده نمی‌شود'], 403);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request)
    {
        // Verificar permiso
        if (!$request->user()->can('users.create')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'is_active' => 'boolean',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        // Crear usuario
        $user = new User();
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = Hash::make($validated['password']);
        $user->is_active = $validated['is_active'] ?? true;
        $user->is_admin = false;

        // Manejar imagen de perfil
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'کاربر با موفقیت ایجاد شد',
            'user' => $user
        ], 201);
    }

    /**
     * Actualizar un usuario existente
     */
    public function update(Request $request, $id)
    {
        // Verificar permiso
        if (!$request->user()->can('users.edit')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Encontrar usuario
        $user = User::findOrFail($id);

        if ($user->is_admin) {
            return response()->json(['message' => 'امکان ویرایش کاربر ادمین وجود ندارد'], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,'.$id.',user_id',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'is_active' => 'sometimes|boolean',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        // Actualizar usuario
        if (isset($validated['first_name'])) $user->first_name = $validated['first_name'];
        if (isset($validated['last_name'])) $user->last_name = $validated['last_name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (isset($validated['phone'])) $user->phone = $validated['phone'];
        if (isset($validated['is_active'])) $user->is_active = $validated['is_active'];
        if (isset($validated['password'])) $user->password = Hash::make($validated['password']);

        // Manejar imagen de perfil
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'کاربر با موفقیت بروزرسانی شد',
            'user' => $user
        ]);
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(Request $request, $id)
    {
        // Verificar permiso
        if (!$request->user()->can('users.delete')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Encontrar usuario
        $user = User::findOrFail($id);

        if ($user->is_admin) {
            return response()->json(['message' => 'امکان حذف کاربر ادمین وجود ندارد'], 403);
        }

        // Eliminar usuario
        $user->delete();

        return response()->json([
            'message' => 'کاربر با موفقیت حذف شد'
        ]);
    }

    /**
     * Enviar mensaje a un usuario
     */
    public function sendMessage(Request $request, $id)
    {
        // Verificar permiso
        if (!$request->user()->can('users.message')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Encontrar usuario
        $user = User::findOrFail($id);

        // Crear mensaje
        $message = new Message();
        $message->sender_id = $request->user()->user_id;
        $message->sender_type = 'admin';
        $message->recipient_id = $user->user_id;
        $message->recipient_type = 'user';
        $message->subject = $validated['subject'];
        $message->content = $validated['content'];
        $message->is_read = false;
        $message->save();

        return response()->json([
            'message' => 'پیام با موفقیت ارسال شد',
            'sent_message' => $message
        ]);
    }

    /**
     * Exportar datos de usuarios a Excel
     */
    public function export(Request $request)
    {
        // Verificar permiso
        if (!$request->user()->can('users.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Aplicar los mismos filtros que en el método index
        $query = User::where('is_admin', false);

        // Aplicar filtros
        $filter = $request->input('filter');

        if ($filter) {
            switch ($filter) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'with_subscription':
                    $query->whereHas('subscriptions', function($q) {
                        $q->where('end_date', '>=', now());
                    });
                    break;
                case 'without_subscription':
                    $query->whereDoesntHave('subscriptions', function($q) {
                        $q->where('end_date', '>=', now());
                    });
                    break;
                case 'expired_subscription':
                    $query->whereHas('subscriptions', function($q) {
                        $q->where('end_date', '<', now());
                    });
                    break;
                case 'registered_today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
            }
        }

        // Buscar por término
        $search = $request->input('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Exportar a Excel (esta clase UsersExport debe crearse por separado)
        return Excel::download(new UsersExport($query), 'users.xlsx');
    }

    /**
     * مدیریت اشتراک کاربر (تمدید یا تغییر)
     */
    public function manageSubscription(Request $request, $id)
    {
        // Verificar permiso
        if (!$request->user()->can('users.edit')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'subscription_id' => 'nullable|integer|exists:subscriptions,subscription_id',
            'plan_id' => 'required|integer|exists:subscription_plans,plan_id',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s|after:start_date',
            'notes' => 'nullable|string|max:500',
            'is_custom_schedule' => 'nullable|boolean',
            'schedule_details' => 'nullable|json|required_if:is_custom_schedule,true',
        ]);

        // Encontrar usuario
        $user = User::findOrFail($id);

        if ($request->has('subscription_id')) {
            // Update existing subscription
            $subscription = Subscription::findOrFail($validated['subscription_id']);

            // Check if this subscription belongs to this user
            if ($subscription->user_id != $user->user_id) {
                return response()->json([
                    'message' => 'این اشتراک متعلق به این کاربر نیست'
                ], 403);
            }

            $subscription->plan_id = $validated['plan_id'];
            $subscription->start_date = $validated['start_date'];
            $subscription->end_date = $validated['end_date'];
            $subscription->notes = $validated['notes'] ?? $subscription->notes;
            $subscription->is_custom_schedule = $validated['is_custom_schedule'] ?? false;

            if (isset($validated['is_custom_schedule']) && $validated['is_custom_schedule']) {
                $subscription->schedule_details = $validated['schedule_details'];
            } else {
                $subscription->schedule_details = null;
            }

            $subscription->save();

            $action = 'تغییر';
        } else {
            // Create new subscription
            $subscription = new Subscription();
            $subscription->user_id = $user->user_id;
            $subscription->plan_id = $validated['plan_id'];
            $subscription->start_date = $validated['start_date'];
            $subscription->end_date = $validated['end_date'];
            $subscription->notes = $validated['notes'] ?? null;
            $subscription->is_custom_schedule = $validated['is_custom_schedule'] ?? false;

            if (isset($validated['is_custom_schedule']) && $validated['is_custom_schedule']) {
                $subscription->schedule_details = $validated['schedule_details'];
            }

            $subscription->save();

            $action = 'افزودن';
        }

        return response()->json([
            'message' => "اشتراک کاربر با موفقیت {$action} شد",
            'subscription' => $subscription
        ]);
    }

    /**
     * مشاهده جزئیات سفارش کاربر
     */
    public function getOrderDetails(Request $request, $userId, $orderId)
    {
        // Verificar permiso
        if (!$request->user()->can('users.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Encontrar usuario
        $user = User::findOrFail($userId);

        // Encontrar orden y verificar que pertenezca al usuario
        $order = Order::with(['items.product', 'transactions', 'shipping', 'status_history'])
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->firstOrFail();

        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * دریافت لیست طرح‌های اشتراک
     */
    public function getSubscriptionPlans(Request $request)
    {
        // Verificar permiso
        if (!$request->user()->can('users.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'plans' => $plans
        ]);
    }

    /**
     * دریافت جزئیات اشتراک‌های کاربر
     */
    public function getUserSubscriptions(Request $request, $id)
    {
        // Verificar permiso
        if (!$request->user()->can('users.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // Encontrar usuario
        $user = User::findOrFail($id);

        // Get all user subscriptions with plan details
        $subscriptions = Subscription::with('plan')
            ->where('user_id', $id)
            ->orderBy('end_date', 'desc')
            ->get();

        // Get active subscription
        $activeSubscription = $subscriptions->first(function ($subscription) {
            return $subscription->isActive();
        });

        return response()->json([
            'subscriptions' => $subscriptions,
            'active_subscription' => $activeSubscription,
            'has_active_subscription' => !is_null($activeSubscription)
        ]);
    }
}
