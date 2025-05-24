<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\OrderFile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /**
     * دریافت لیست سفارشات کاربر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $perPage = $request->get('per_page', 10);
        $status = $request->get('status', null);
        $type = $request->get('type', null); // سفارش عمومی یا خصوصی

        $orders = Order::where('user_id', $user->user_id)
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($type, function ($query, $type) {
                return $query->where('order_type', $type);
            })
            ->with(['seller:seller_id,shop_name,user_id'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // تبدیل وضعیت‌ها به فرمت فارسی برای نمایش
        $orders->getCollection()->transform(function ($order) {
            $statusMap = [
                'pending' => 'در انتظار پذیرش',
                'processing' => 'در حال انجام',
                'completed' => 'انجام شده',
                'cancelled' => 'لغو شده',
                'rejected' => 'رد شده',
            ];

            $order->status_fa = $statusMap[$order->status] ?? $order->status;

            // افزودن اطلاعات آخرین پیام
            $order->last_message = $order->messages()->latest()->first();

            return $order;
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * دریافت جزئیات یک سفارش
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderDetails($orderId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $order = Order::where('order_id', $orderId)
            ->with([
                'items.product:product_id,name,image',
                'seller:seller_id,user_id,shop_name',
                'status_history',
                'shipping'
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد',
            ], 404);
        }

        // بررسی دسترسی - کاربر فقط می‌تواند سفارشات خودش را ببیند
        if ($order->user_id != $user->user_id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سفارش را ندارید',
            ], 403);
        }

        // اضافه کردن ترجمه وضعیت به فارسی
        $statusMap = [
            'pending' => 'در انتظار پذیرش',
            'processing' => 'در حال انجام',
            'completed' => 'انجام شده',
            'cancelled' => 'لغو شده',
            'rejected' => 'رد شده',
        ];

        $order->status_fa = $statusMap[$order->status] ?? $order->status;

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * دریافت گفتگوهای مربوط به یک سفارش
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderConversation($orderId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد',
            ], 404);
        }

        // بررسی دسترسی - کاربر فقط می‌تواند گفتگوهای سفارشات خودش را ببیند
        if ($order->user_id != $user->user_id && !$user->is_admin && $order->seller_id != $user->seller->seller_id) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این گفتگو را ندارید',
            ], 403);
        }

        // اگر سفارش بیش از 15 روز پیش تکمیل شده باشد، گفتگو باید حذف شده باشد
        if ($order->status == 'completed' && Carbon::parse($order->updated_at)->addDays(15)->isPast()) {
            return response()->json([
                'success' => true,
                'message' => 'گفتگوهای این سفارش به دلیل گذشت زمان حذف شده‌اند',
                'data' => [],
            ]);
        }

        $messages = $order->messages()->with('user:user_id,first_name,last_name,profile_image')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * ارسال پیام جدید در گفتگوی سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $orderId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است',
            ], 401);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد',
            ], 404);
        }

        // بررسی دسترسی - کاربر فقط می‌تواند به گفتگوهای سفارشات خودش پیام بفرستد
        // یا اگر فروشنده همان سفارش باشد
        if ($order->user_id != $user->user_id && !$user->is_admin &&
            (!$user->seller || $order->seller_id != $user->seller->seller_id)) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این گفتگو را ندارید',
            ], 403);
        }

        // بررسی وضعیت سفارش - اگر سفارش تکمیل شده باشد، امکان ارسال پیام جدید وجود ندارد
        if ($order->status == 'completed' || $order->status == 'cancelled' || $order->status == 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'امکان ارسال پیام برای این سفارش وجود ندارد',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:attachment|string|max:1000',
            'attachment' => 'nullable|file|max:10240', // حداکثر 10 مگابایت
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attachmentPath = null;

        // آپلود فایل پیوست (در صورت وجود)
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $attachmentPath = $file->storeAs('order_attachments/' . $orderId, $fileName, 'public');
        }

        // ایجاد پیام جدید
        $message = new Message([
            'user_id' => $user->user_id,
            'message' => $request->message ?? '',
            'attachment_url' => $attachmentPath ? Storage::url($attachmentPath) : null,
        ]);

        $order->messages()->save($message);

        return response()->json([
            'success' => true,
            'message' => 'پیام با موفقیت ارسال شد',
            'data' => $message,
        ]);
    }

    /**
     * حذف گفتگوهای قدیمی (برای اجرا توسط کرون جاب)
     *
     * @return void
     */
    public function cleanupOldConversations()
    {
        // پیدا کردن سفارشات تکمیل شده که بیش از 15 روز از تکمیل آنها گذشته
        $oldCompletedOrders = Order::where('status', 'completed')
            ->where('updated_at', '<', Carbon::now()->subDays(15))
            ->get();

        foreach ($oldCompletedOrders as $order) {
            // حذف فایل‌های پیوست
            $attachmentPath = 'public/order_attachments/' . $order->order_id;
            if (Storage::exists($attachmentPath)) {
                Storage::deleteDirectory($attachmentPath);
            }

            // حذف پیام‌ها
            $order->messages()->delete();
        }
    }

    /**
     * دریافت لیست تمام سفارشات (برای ادمین)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllOrders(Request $request)
    {
        $user = Auth::user();

        // فقط ادمین دسترسی دارد
        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $perPage = $request->get('per_page', 15);
        $status = $request->get('status', null);
        $userId = $request->get('user_id', null);
        $sellerId = $request->get('seller_id', null);
        $search = $request->get('search', '');

        $orders = Order::when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($userId, function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->with(['user:user_id,first_name,last_name,email,phone_number', 'seller:seller_id,shop_name'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * به‌روزرسانی وضعیت سفارش (برای ادمین)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $user = Auth::user();

        // فقط ادمین دسترسی دارد
        if (!$user || !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید',
            ], 403);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,cancelled,rejected',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ذخیره تاریخچه وضعیت
        $order->status_history()->create([
            'status' => $request->status,
            'notes' => $request->notes,
            'created_by' => $user->user_id,
        ]);

        // به‌روزرسانی وضعیت سفارش
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'وضعیت سفارش با موفقیت به‌روزرسانی شد',
            'data' => $order,
        ]);
    }

    /**
     * نمایش جزئیات سفارش کاربر
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserOrderDetails(Request $request, $orderId)
    {
        $user = $request->user();
        
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['items.product', 'files'])
            ->first();
        
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'سفارش مورد نظر یافت نشد'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    /**
     * دانلود فایل محصول
     *
     * @param Request $request
     * @param int $fileId
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadFile(Request $request, $fileId)
    {
        $user = $request->user();
        
        $file = OrderFile::where('id', $fileId)
            ->whereHas('order', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();
        
        if (!$file) {
            return response()->json([
                'status' => 'error',
                'message' => 'فایل مورد نظر یافت نشد'
            ], 404);
        }
        
        if (!$file->canBeDownloaded()) {
            return response()->json([
                'status' => 'error',
                'message' => 'این فایل قابل دانلود نیست یا منقضی شده است'
            ], 403);
        }
        
        if (!Storage::exists($file->file_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'فایل در سرور یافت نشد'
            ], 404);
        }
        
        // افزایش تعداد دانلود
        $file->incrementDownloadCount();
        
        return Storage::download($file->file_path, $file->file_name);
    }

    /**
     * مدیر: نمایش جزئیات سفارش
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminOrderDetails(Request $request, $orderId)
    {
        // اعتبارسنجی دسترسی کاربر
        if (!$this->isAdmin($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این بخش را ندارید'
            ], 403);
        }
        
        $order = Order::with(['items.product', 'items.seller', 'user', 'payment', 'files'])
            ->findOrFail($orderId);
        
        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    /**
     * مدیر: تایید سفارش و ارسال به فروشنده
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveOrder(Request $request, $orderId)
    {
        // اعتبارسنجی دسترسی کاربر
        if (!$this->isAdmin($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این بخش را ندارید'
            ], 403);
        }
        
        $request->validate([
            'admin_notes' => 'nullable|string|max:500',
        ]);
        
        $order = Order::findOrFail($orderId);
        
        // بررسی وضعیت سفارش
        if ($order->status !== Order::STATUS_PAID) {
            return response()->json([
                'status' => 'error',
                'message' => 'این سفارش قابل تایید نیست. وضعیت فعلی: ' . $order->status_text
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // به‌روزرسانی وضعیت سفارش
            $order->status = Order::STATUS_ADMIN_APPROVED;
            $order->admin_approved_at = now();
            $order->admin_approved_by = $request->user()->id;
            $order->admin_notes = $request->input('admin_notes');
            $order->save();
            
            // به‌روزرسانی وضعیت آیتم‌های سفارش
            foreach ($order->items as $item) {
                $item->status = Order::STATUS_ADMIN_APPROVED;
                $item->save();
            }
            
            // ارسال اطلاعیه به فروشندگان (در یک کار پس‌زمینه)
            $this->notifySellers($order);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'سفارش با موفقیت تایید و به فروشندگان ارسال شد',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در تایید سفارش', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تایید سفارش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * مدیر: رد سفارش
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectOrder(Request $request, $orderId)
    {
        // اعتبارسنجی دسترسی کاربر
        if (!$this->isAdmin($request->user())) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این بخش را ندارید'
            ], 403);
        }
        
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);
        
        $order = Order::findOrFail($orderId);
        
        // بررسی وضعیت سفارش
        if ($order->status !== Order::STATUS_PAID && $order->status !== Order::STATUS_ADMIN_APPROVED) {
            return response()->json([
                'status' => 'error',
                'message' => 'این سفارش قابل رد نیست. وضعیت فعلی: ' . $order->status_text
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // به‌روزرسانی وضعیت سفارش
            $order->status = Order::STATUS_REJECTED;
            $order->reject_reason = $request->input('reject_reason');
            $order->save();
            
            // به‌روزرسانی وضعیت آیتم‌های سفارش
            foreach ($order->items as $item) {
                $item->status = Order::STATUS_REJECTED;
                $item->save();
            }
            
            // ارسال اطلاعیه به کاربر (در یک کار پس‌زمینه)
            $this->notifyUser($order, 'reject');
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'سفارش با موفقیت رد شد',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('خطا در رد سفارش', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در رد سفارش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * فروشنده: نمایش لیست سفارشات
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSellerOrders(Request $request)
    {
        $user = $request->user();
        
        $status = $request->input('status', null);
        
        $query = OrderItem::where('seller_id', $user->id);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $orderItems = $query->with(['order', 'product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json([
            'status' => 'success',
            'data' => $orderItems
        ]);
    }

    /**
     * فروشنده: نمایش جزئیات سفارش
     *
     * @param Request $request
     * @param int $orderItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSellerOrderDetails(Request $request, $orderItemId)
    {
        $user = $request->user();
        
        $orderItem = OrderItem::where('id', $orderItemId)
            ->where('seller_id', $user->id)
            ->with(['order', 'product', 'files'])
            ->first();
        
        if (!$orderItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'آیتم سفارش مورد نظر یافت نشد'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $orderItem
        ]);
    }

    /**
     * فروشنده: آپلود فایل محصول برای سفارش
     *
     * @param Request $request
     * @param int $orderItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFile(Request $request, $orderItemId)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // حداکثر 100 مگابایت
            'description' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after:now',
        ]);
        
        $user = $request->user();
        
        $orderItem = OrderItem::where('id', $orderItemId)
            ->where('seller_id', $user->id)
            ->with('order')
            ->first();
        
        if (!$orderItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'آیتم سفارش مورد نظر یافت نشد'
            ], 404);
        }
        
        // بررسی وضعیت سفارش
        if ($orderItem->order->status !== Order::STATUS_ADMIN_APPROVED &&
            $orderItem->order->status !== Order::STATUS_SENT_TO_SELLER &&
            $orderItem->order->status !== Order::STATUS_SELLER_UPLOADED) {
            return response()->json([
                'status' => 'error',
                'message' => 'این سفارش در وضعیت مناسب برای آپلود فایل نیست'
            ], 400);
        }
        
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileType = $file->getMimeType();
        
        // تعیین مسیر ذخیره فایل
        $filePath = 'order_files/' . $orderItem->order->id . '/' . $orderItemId . '/' . time() . '_' . $fileName;
        
        try {
            // ذخیره فایل
            Storage::put($filePath, file_get_contents($file));
            
            // ایجاد رکورد فایل
            $orderFile = new OrderFile([
                'order_id' => $orderItem->order->id,
                'order_item_id' => $orderItemId,
                'user_id' => $user->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'file_type' => $fileType,
                'download_count' => 0,
                'is_active' => true,
                'expires_at' => $request->input('expires_at'),
                'description' => $request->input('description'),
            ]);
            
            $orderFile->save();
            
            // اگر اولین فایل آپلود شده است، وضعیت سفارش را به‌روزرسانی کنید
            if ($orderItem->order->status === Order::STATUS_ADMIN_APPROVED || 
                $orderItem->order->status === Order::STATUS_SENT_TO_SELLER) {
                $orderItem->order->status = Order::STATUS_SELLER_UPLOADED;
                $orderItem->order->seller_delivered_at = now();
                $orderItem->order->save();
                
                $orderItem->status = Order::STATUS_SELLER_UPLOADED;
                $orderItem->save();
            }
            
            // اطلاع‌رسانی به کاربر
            $this->notifyUser($orderItem->order, 'file_uploaded');
            
            return response()->json([
                'status' => 'success',
                'message' => 'فایل با موفقیت آپلود شد',
                'data' => $orderFile
            ]);
            
        } catch (\Exception $e) {
            Log::error('خطا در آپلود فایل', [
                'order_item_id' => $orderItemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در آپلود فایل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * بررسی دسترسی مدیر
     *
     * @param User $user
     * @return bool
     */
    protected function isAdmin($user)
    {
        // بررسی نقش کاربر
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }

    /**
     * اطلاع‌رسانی به فروشندگان
     *
     * @param Order $order
     * @return void
     */
    protected function notifySellers($order)
    {
        foreach ($order->items as $item) {
            if ($item->seller) {
                // ارسال اطلاعیه به فروشنده
                // این قسمت باید با سیستم اطلاع‌رسانی شما هماهنگ شود
                
                try {
                    // ارسال ایمیل به فروشنده
                    /*
                    Mail::to($item->seller->email)->send(new \App\Mail\NewOrderNotification([
                        'seller_name' => $item->seller->name,
                        'order_number' => $order->order_number,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'total_price' => $item->total_price,
                    ]));
                    */
                    
                    // ثبت رکورد اطلاعیه
                    // $item->seller->notifications()->create([...]);
                    
                } catch (\Exception $e) {
                    Log::error('خطا در ارسال اطلاعیه به فروشنده', [
                        'seller_id' => $item->seller->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * اطلاع‌رسانی به کاربر
     *
     * @param Order $order
     * @param string $event
     * @return void
     */
    protected function notifyUser($order, $event)
    {
        // ارسال اطلاعیه به کاربر
        // این قسمت باید با سیستم اطلاع‌رسانی شما هماهنگ شود
        
        try {
            $user = $order->user;
            
            if (!$user) {
                return;
            }
            
            // ارسال ایمیل به کاربر
            /*
            switch ($event) {
                case 'file_uploaded':
                    Mail::to($user->email)->send(new \App\Mail\OrderFileUploaded([
                        'user_name' => $user->name,
                        'order_number' => $order->order_number,
                    ]));
                    break;
                
                case 'reject':
                    Mail::to($user->email)->send(new \App\Mail\OrderRejected([
                        'user_name' => $user->name,
                        'order_number' => $order->order_number,
                        'reject_reason' => $order->reject_reason,
                    ]));
                    break;
            }
            */
            
            // ثبت رکورد اطلاعیه
            // $user->notifications()->create([...]);
            
        } catch (\Exception $e) {
            Log::error('خطا در ارسال اطلاعیه به کاربر', [
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
