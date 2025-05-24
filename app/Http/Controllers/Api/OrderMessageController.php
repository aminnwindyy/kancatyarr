<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Events\OrderMessageSent;
use App\Notifications\NewOrderMessage;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceProvider;

class OrderMessageController extends Controller
{
    /**
     * دریافت گفتگوهای یک سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversation(Request $request, $orderId)
    {
        $user = Auth::user();
        $order = Order::findOrFail($orderId);
        
        // بررسی دسترسی - کاربر فقط می‌تواند به گفتگوهای سفارشات خودش یا سفارشاتی که به عنوان 
        // خدمات‌دهنده به آن متصل است، دسترسی داشته باشد
        if ($order->user_id != $user->user_id && 
            $order->service_provider_id != $user->service_provider_id && 
            !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این گفتگو را ندارید',
            ], 403);
        }
        
        // علامت‌گذاری همه پیام‌های دریافتی به عنوان خوانده شده
        if ($order->user_id == $user->user_id) {
            // اگر کاربر، صاحب سفارش است، پیام‌های دیگران را به عنوان خوانده شده علامت‌گذاری می‌کنیم
            OrderMessage::where('order_id', $orderId)
                ->where('sender_id', '!=', $user->user_id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            // اگر کاربر، خدمات‌دهنده یا مدیر است، پیام‌های کاربر را به عنوان خوانده شده علامت‌گذاری می‌کنیم
            OrderMessage::where('order_id', $orderId)
                ->where('sender_id', $order->user_id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }
        
        // دریافت پیام‌ها با اطلاعات فرستنده
        $messages = OrderMessage::with('sender:user_id,first_name,last_name,profile_image')
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($user) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender' => [
                        'id' => $message->sender->user_id,
                        'name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                        'profile_image' => $message->sender->profile_image,
                    ],
                    'is_mine' => $message->sender_id == $user->user_id,
                    'has_file' => $message->hasFile(),
                    'file_info' => $message->hasFile() ? [
                        'file_name' => $message->file_name,
                        'file_type' => $message->file_type,
                        'download_url' => url('/api/orders/messages/' . $message->id . '/download-file'),
                    ] : null,
                    'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'is_read' => $message->is_read,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->order_id,
                'order_status' => $order->status,
                'messages' => $messages,
                'can_send_message' => !in_array($order->status, ['completed', 'cancelled', 'rejected']),
            ],
        ]);
    }
    
    /**
     * ارسال پیام جدید به گفتگوی سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, $orderId)
    {
        $user = Auth::user();
        $order = Order::findOrFail($orderId);
        
        // بررسی دسترسی
        if ($order->user_id != $user->user_id && 
            $order->service_provider_id != $user->service_provider_id && 
            !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این گفتگو را ندارید',
            ], 403);
        }
        
        // بررسی وضعیت سفارش - اگر سفارش تکمیل شده باشد، امکان ارسال پیام جدید وجود ندارد
        if (in_array($order->status, ['completed', 'cancelled', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'امکان ارسال پیام برای این سفارش وجود ندارد',
            ], 400);
        }
        
        // اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'content' => 'required_without:file|string|max:1000',
            'file' => 'nullable|file|max:10240|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar', // فرمت‌های مجاز
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ارسالی معتبر نیست',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // ایجاد پیام جدید
            $message = new OrderMessage();
            $message->order_id = $order->order_id;
            $message->sender_id = $user->user_id;
            $message->content = $request->content ?? '';
            
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
            
            // ارسال رویداد برای اطلاع‌رسانی زمان واقعی
            if (class_exists('App\Events\OrderMessageSent')) {
                broadcast(new OrderMessageSent($message, $order))->toOthers();
            }
            
            // ارسال نوتیفیکیشن به گیرنده پیام
            if ($order->user_id != $user->user_id) {
                // اگر فرستنده پیام، مالک سفارش نیست، پس به مالک سفارش نوتیفیکیشن می‌فرستیم
                $recipient = User::find($order->user_id);
                if ($recipient) {
                    $recipient->notify(new NewOrderMessage($message));
                }
            } else if ($order->service_provider_id) {
                // اگر فرستنده پیام، مالک سفارش است و سرویس‌دهنده وجود دارد، به سرویس‌دهنده نوتیفیکیشن می‌فرستیم
                $serviceProvider = ServiceProvider::find($order->service_provider_id);
                if ($serviceProvider && $serviceProvider->user_id) {
                    $recipient = User::find($serviceProvider->user_id);
                    if ($recipient) {
                        $recipient->notify(new NewOrderMessage($message));
                    }
                }
            }
            
            DB::commit();
            
            // لاگ کردن ارسال پیام
            \Log::channel('orders')->info('New message sent', [
                'message_id' => $message->id,
                'order_id' => $order->order_id,
                'sender_id' => $user->user_id,
                'has_file' => $message->hasFile(),
                'sent_at' => now()->format('Y-m-d H:i:s')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'پیام با موفقیت ارسال شد',
                'data' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender' => [
                        'id' => $user->user_id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'profile_image' => $user->profile_image,
                    ],
                    'is_mine' => true,
                    'has_file' => $message->hasFile(),
                    'file_info' => $message->hasFile() ? [
                        'file_name' => $message->file_name,
                        'file_type' => $message->file_type,
                        'download_url' => url('/api/orders/messages/' . $message->id . '/download-file'),
                    ] : null,
                    'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'is_read' => $message->is_read,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // لاگ کردن خطا
            \Log::channel('errors')->error('Error sending message', [
                'order_id' => $order->order_id,
                'sender_id' => $user->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیام: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * دانلود فایل پیوست پیام
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $messageId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadFile(Request $request, $messageId)
    {
        $user = Auth::user();
        $message = OrderMessage::with('order')->findOrFail($messageId);
        $order = $message->order;
        
        // بررسی دسترسی
        if ($order->user_id != $user->user_id && 
            $order->service_provider_id != $user->service_provider_id && 
            !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این فایل را ندارید',
            ], 403);
        }
        
        // بررسی وجود فایل
        if (!$message->hasFile() || !Storage::disk('public')->exists($message->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'فایل مورد نظر یافت نشد',
            ], 404);
        }
        
        return response()->download(
            Storage::disk('public')->path($message->file_path),
            $message->file_name
        );
    }
    
    /**
     * علامت‌گذاری پیام به عنوان خوانده شده
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $messageId)
    {
        $user = Auth::user();
        $message = OrderMessage::with('order')->findOrFail($messageId);
        $order = $message->order;
        
        // بررسی دسترسی
        if ($order->user_id != $user->user_id && 
            $order->service_provider_id != $user->service_provider_id && 
            !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این پیام را ندارید',
            ], 403);
        }
        
        // علامت‌گذاری پیام به عنوان خوانده شده
        $message->is_read = true;
        $message->save();
        
        return response()->json([
            'success' => true,
            'message' => 'پیام به عنوان خوانده شده علامت‌گذاری شد',
        ]);
    }
    
    /**
     * دریافت تعداد پیام‌های خوانده نشده برای هر سفارش
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadCounts(Request $request)
    {
        $user = Auth::user();
        
        try {
            // یافتن همه سفارش‌هایی که کاربر مرتبط با آن‌هاست
            $userOrders = Order::where('user_id', $user->user_id)->pluck('order_id')->toArray();
            $providerOrders = Order::where('service_provider_id', $user->service_provider_id)->pluck('order_id')->toArray();
            
            $unreadCounts = [];
            
            // بهینه‌سازی کوئری برای سفارشات کاربر - یک کوئری به جای چندین کوئری
            if (!empty($userOrders)) {
                $userUnreadMessages = OrderMessage::whereIn('order_id', $userOrders)
                    ->where('sender_id', '!=', $user->user_id)
                    ->where('is_read', false)
                    ->select('order_id', DB::raw('count(*) as count'))
                    ->groupBy('order_id')
                    ->get();
                
                foreach ($userUnreadMessages as $message) {
                    $unreadCounts[$message->order_id] = $message->count;
                }
            }
            
            // بهینه‌سازی کوئری برای سفارشات سرویس‌دهنده - یک کوئری به جای چندین کوئری
            if (!empty($providerOrders)) {
                $providerOrders = Order::whereIn('order_id', $providerOrders)->get();
                
                $orderUserIds = $providerOrders->pluck('user_id')->toArray();
                $orderIds = $providerOrders->pluck('order_id')->toArray();
                
                $providerUnreadMessages = OrderMessage::whereIn('order_id', $orderIds)
                    ->whereIn('sender_id', $orderUserIds)
                    ->where('is_read', false)
                    ->select('order_id', DB::raw('count(*) as count'))
                    ->groupBy('order_id')
                    ->get();
                
                foreach ($providerUnreadMessages as $message) {
                    $unreadCounts[$message->order_id] = $message->count;
                }
            }
            
            // لاگ کردن اطلاعات آماری
            \Log::channel('orders')->info('Unread messages count fetched', [
                'user_id' => $user->user_id,
                'total_orders' => count($userOrders) + count($providerOrders),
                'orders_with_unread' => count($unreadCounts),
                'total_unread' => array_sum($unreadCounts),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $unreadCounts,
            ]);
            
        } catch (\Exception $e) {
            // لاگ کردن خطا
            \Log::channel('errors')->error('Error fetching unread counts', [
                'user_id' => $user->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تعداد پیام‌های خوانده نشده: ' . $e->getMessage(),
            ], 500);
        }
    }
} 