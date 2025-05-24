<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\OrderMessage;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use App\Models\User;
use App\Models\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class OrderAdminController extends Controller
{
    /**
     * نمایش لیست سفارشات با امکان فیلتر
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }

        // دریافت پارامترهای فیلتر
        $filter = $request->input('filter', 'all'); // public, private, all
        $deliveryType = $request->input('delivery_type'); // national, local
        $status = $request->input('status'); // pending, accepted, rejected
        $categoryId = $request->input('category_id'); // شناسه دسته‌بندی
        $providerType = $request->input('provider_type'); // business, connectyar
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        // ایجاد کوئری با فیلترهای مختلف
        $query = Order::query();
        
        // اعمال فیلترها
        $query->ofType($filter)
            ->when($deliveryType, function ($q) use ($deliveryType) {
                return $q->ofDeliveryType($deliveryType);
            })
            ->when($status, function ($q) use ($status) {
                return $q->ofStatus($status);
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                return $q->ofCategory($categoryId);
            })
            ->when($providerType, function ($q) use ($providerType) {
                return $q->ofProviderType($providerType);
            });
            
        // بارگذاری روابط
        $query->with([
            'user:user_id,first_name,last_name', 
            'serviceProvider:id,name',
            'category:id,name'
        ]);
            
        // مرتب‌سازی و دریافت نتایج
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($limit);
        
        // تبدیل دیتا به فرمت مورد نیاز
        $data = $orders->items();
        $formattedData = [];
        
        foreach ($data as $order) {
            $formattedData[] = [
                'id' => $order->order_id,
                'user_name' => $order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'نامشخص',
                'service_provider_name' => $order->serviceProvider ? $order->serviceProvider->name : 'نامشخص',
                'service_provider_type' => $order->service_provider_type,
                'category_name' => $order->category ? $order->category->name : 'نامشخص',
                'order_type' => $order->order_type,
                'delivery_type' => $order->delivery_type,
                'status' => $order->status,
                'created_at' => $order->created_at->format('Y-m-d')
            ];
        }
        
        return response()->json([
            'data' => $formattedData,
            'total_pages' => $orders->lastPage(),
            'current_page' => $orders->currentPage()
        ]);
    }
    
    /**
     * ایجاد سفارش جدید
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.create')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'service_provider_id' => 'nullable|exists:service_providers,id',
            'order_type' => 'required|in:public,private',
            'delivery_type' => 'required|in:national,local',
            'description' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اطلاعات ورودی',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // ایجاد سفارش جدید
        $order = new Order();
        $order->user_id = $request->user_id;
        $order->service_provider_id = $request->service_provider_id;
        $order->order_type = $request->order_type;
        $order->delivery_type = $request->delivery_type;
        $order->description = $request->description;
        $order->status = 'pending';
        $order->save();
        
        return response()->json([
            'message' => 'سفارش با موفقیت ایجاد شد.',
            'order_id' => $order->order_id
        ], 201);
    }
    
    /**
     * نمایش جزئیات سفارش با پیام‌ها
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $orderId)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // یافتن سفارش
        $order = Order::with([
                'user:user_id,first_name,last_name',
                'serviceProvider:id,name',
                'status_history',
                'orderMessages.sender'
            ])
            ->findOrFail($orderId);
            
        // تبدیل به فرمت مورد نیاز
        $orderDetails = [
            'order' => [
                'id' => $order->order_id,
                'user_name' => $order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'نامشخص',
                'service_provider_name' => $order->serviceProvider ? $order->serviceProvider->name : 'نامشخص',
                'order_type' => $order->order_type,
                'delivery_type' => $order->delivery_type,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'description' => $order->description,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at->format('Y-m-d'),
                'status_history' => $order->status_history,
                'messages' => $order->orderMessages->map(function($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'sender' => $message->sender ? $message->sender->first_name . ' ' . $message->sender->last_name : 'نامشخص',
                        'sender_id' => $message->sender_id,
                        'has_file' => $message->hasFile(),
                        'file_path' => $message->file_path,
                        'file_name' => $message->file_name,
                        'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
                        'is_read' => $message->is_read,
                    ];
                })
            ]
        ];
        
        return response()->json($orderDetails);
    }
    
    /**
     * تأیید یا رد سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, $orderId)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
            'service_provider_id' => 'nullable|exists:service_providers,id',
            'service_provider_type' => 'nullable|in:business,connectyar',
            'rejection_reason' => 'nullable|required_if:status,rejected|string|max:500',
            'refund_method' => 'nullable|required_if:status,rejected|in:wallet,gift_card,bank_gateway',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اطلاعات ورودی',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // یافتن سفارش
        $order = Order::findOrFail($orderId);
        
        // به‌روزرسانی سفارش
        $order->status = $request->status;
        
        // اگر سرویس‌دهنده مشخص شده باشد آن را تنظیم می‌کنیم
        if ($request->has('service_provider_id')) {
            $order->service_provider_id = $request->service_provider_id;
        }
        
        // اگر نوع سرویس‌دهنده مشخص شده باشد آن را تنظیم می‌کنیم
        if ($request->has('service_provider_type')) {
            $order->service_provider_type = $request->service_provider_type;
        }
        
        // تنظیم دلیل رد در صورت وجود
        if ($request->status == 'rejected' && $request->has('rejection_reason')) {
            $order->rejection_reason = $request->rejection_reason;
        }
        
        // در صورت رد شدن سفارش و پرداخت شده بودن، برگشت وجه را انجام می‌دهیم
        if ($request->status == 'rejected' && $order->payment_status == 'paid' && $request->has('refund_method')) {
            try {
                DB::beginTransaction();
                
                // ایجاد تراکنش برگشت وجه
                PaymentTransaction::create([
                    'order_id' => $order->order_id,
                    'user_id' => $order->user_id,
                    'refund_method' => $request->refund_method,
                    'amount' => $order->total_amount,
                    'status' => 'pending',
                    'description' => 'برگشت وجه سفارش به دلیل: ' . $order->rejection_reason,
                ]);
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'خطا در ثبت تراکنش برگشت وجه: ' . $e->getMessage()
                ], 500);
            }
        }
        
        $order->save();
        
        // ثبت تاریخچه وضعیت
        $order->status_history()->create([
            'status' => $order->status,
            'notes' => $request->status == 'rejected' ? $request->rejection_reason : null,
            'user_id' => $request->user()->user_id,
        ]);
        
        // ارسال رویداد به‌روزرسانی وضعیت سفارش
        event(new OrderStatusUpdated($order));
        
        return response()->json([
            'message' => 'وضعیت سفارش با موفقیت تغییر کرد.'
        ]);
    }
    
    /**
     * صدور گزارش سفارشات
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportOrders(Request $request)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.export')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // دریافت پارامترهای فیلتر
        $filter = $request->input('filter');
        $deliveryType = $request->input('delivery_type');
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $categoryId = $request->input('category_id');
        $providerType = $request->input('provider_type');
        
        // ایجاد خروجی اکسل
        return Excel::download(
            new OrdersExport(
                $filter, 
                $deliveryType, 
                $status, 
                $fromDate, 
                $toDate, 
                $categoryId, 
                $providerType
            ), 
            'orders_export.xlsx'
        );
    }
    
    /**
     * ارسال پیام به سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $orderId)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.process')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'file' => 'nullable|file|max:10240', // حداکثر 10 مگابایت
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطا در اطلاعات ورودی',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // یافتن سفارش
        $order = Order::findOrFail($orderId);
        
        // ایجاد پیام جدید
        $message = new OrderMessage();
        $message->order_id = $order->order_id;
        $message->sender_id = $request->user()->user_id;
        $message->content = $request->content;
        
        // آپلود فایل در صورت وجود
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('order_messages/' . $order->order_id, $fileName, 'public');
            
            $message->file_path = $filePath;
            $message->file_name = $file->getClientOriginalName();
            $message->file_type = $file->getClientMimeType();
        }
        
        $message->save();
        
        return response()->json([
            'message' => 'پیام با موفقیت ارسال شد.',
            'data' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'has_file' => $message->hasFile(),
                'file_path' => $message->file_path,
                'file_name' => $message->file_name,
                'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }
    
    /**
     * دریافت پیام‌های سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, $orderId)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // یافتن سفارش
        $order = Order::findOrFail($orderId);
        
        // دریافت پیام‌ها
        $messages = OrderMessage::with('sender')
            ->where('order_id', $order->order_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender' => $message->sender ? $message->sender->first_name . ' ' . $message->sender->last_name : 'نامشخص',
                    'sender_id' => $message->sender_id,
                    'has_file' => $message->hasFile(),
                    'file_path' => $message->file_path,
                    'file_name' => $message->file_name,
                    'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'is_read' => $message->is_read,
                ];
            });
        
        return response()->json([
            'data' => $messages
        ]);
    }
    
    /**
     * دانلود فایل پیوست پیام
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $messageId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadMessageFile(Request $request, $messageId)
    {
        // بررسی دسترسی
        if (!$request->user()->can('orders.view')) {
            return response()->json(['message' => 'دسترسی غیر مجاز'], 403);
        }
        
        // یافتن پیام
        $message = OrderMessage::findOrFail($messageId);
        
        // بررسی وجود فایل
        if (!$message->hasFile() || !Storage::disk('public')->exists($message->file_path)) {
            return response()->json([
                'message' => 'فایل مورد نظر یافت نشد'
            ], 404);
        }
        
        return response()->download(
            Storage::disk('public')->path($message->file_path),
            $message->file_name
        );
    }
}
