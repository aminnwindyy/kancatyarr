<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * دریافت لیست گفتگوهای کاربر
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getChats(Request $request)
    {
        $user = Auth::user();
        
        $chats = Chat::where('user_id', $user->user_id)
            ->with(['lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($chat) {
                return [
                    'chat_id' => $chat->chat_id,
                    'title' => $chat->title,
                    'status' => $chat->status,
                    'created_at' => $chat->created_at->format('Y-m-d H:i:s'),
                    'unread_count' => $chat->unreadCount(),
                    'last_message' => $chat->lastMessage ? [
                        'content' => $chat->lastMessage->content,
                        'sender_type' => $chat->lastMessage->sender_type,
                        'created_at' => $chat->lastMessage->created_at->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            });
        
        return response()->json([
            'status' => 'success',
            'chats' => $chats,
            'has_new_messages' => $chats->sum('unread_count') > 0
        ]);
    }
    
    /**
     * ایجاد گفتگوی جدید
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function createChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات نامعتبر',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = Auth::user();
        
        DB::beginTransaction();
        
        try {
            // ایجاد گفتگوی جدید
            $chat = new Chat();
            $chat->user_id = $user->user_id;
            $chat->title = $request->title;
            $chat->status = 'open';
            $chat->save();
            
            // ایجاد اولین پیام
            $message = new ChatMessage();
            $message->chat_id = $chat->chat_id;
            $message->sender_id = $user->user_id;
            $message->sender_type = 'user';
            $message->content = $request->message;
            $message->is_read = false;
            $message->save();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'گفتگوی جدید با موفقیت ایجاد شد',
                'chat' => [
                    'chat_id' => $chat->chat_id,
                    'title' => $chat->title,
                    'status' => $chat->status,
                    'created_at' => $chat->created_at->format('Y-m-d H:i:s'),
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد گفتگو: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * دریافت پیام‌های یک گفتگو
     *
     * @param int $chatId
     * @return \Illuminate\Http\Response
     */
    public function getMessages($chatId)
    {
        $user = Auth::user();
        
        // بررسی دسترسی کاربر به این گفتگو
        $chat = Chat::where('chat_id', $chatId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();
        
        // دریافت پیام‌ها
        $messages = ChatMessage::where('chat_id', $chatId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'message_id' => $message->message_id,
                    'sender_type' => $message->sender_type,
                    'content' => $message->content,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'is_read' => $message->is_read,
                ];
            });
        
        // علامت‌گذاری پیام‌های پشتیبانی به عنوان خوانده‌شده
        ChatMessage::where('chat_id', $chatId)
            ->where('sender_type', 'support')
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json([
            'status' => 'success',
            'chat' => [
                'chat_id' => $chat->chat_id,
                'title' => $chat->title,
                'status' => $chat->status,
                'created_at' => $chat->created_at->format('Y-m-d H:i:s'),
            ],
            'messages' => $messages
        ]);
    }
    
    /**
     * ارسال پیام جدید در یک گفتگو
     *
     * @param Request $request
     * @param int $chatId
     * @return \Illuminate\Http\Response
     */
    public function sendMessage(Request $request, $chatId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات نامعتبر',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = Auth::user();
        
        // بررسی دسترسی کاربر به این گفتگو
        $chat = Chat::where('chat_id', $chatId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();
            
        // اگر گفتگو بسته شده باشد، امکان ارسال پیام وجود ندارد
        if ($chat->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'این گفتگو بسته شده است و امکان ارسال پیام وجود ندارد',
            ], 403);
        }
        
        // ایجاد پیام جدید
        $message = new ChatMessage();
        $message->chat_id = $chatId;
        $message->sender_id = $user->user_id;
        $message->sender_type = 'user';
        $message->content = $request->message;
        $message->is_read = false;
        $message->save();
        
        // بروزرسانی زمان آخرین فعالیت گفتگو
        $chat->touch();
        
        // اگر گفتگو در وضعیت حل شده بود، به وضعیت باز تغییر دهید
        if ($chat->status === 'resolved') {
            $chat->status = 'open';
            $chat->save();
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'پیام با موفقیت ارسال شد',
            'chat_message' => [
                'message_id' => $message->message_id,
                'sender_type' => $message->sender_type,
                'content' => $message->content,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'is_read' => $message->is_read,
            ]
        ]);
    }
    
    /**
     * بررسی وجود پیام جدید
     *
     * @return \Illuminate\Http\Response
     */
    public function checkNewMessages()
    {
        $user = Auth::user();
        
        $unreadCount = ChatMessage::whereHas('chat', function ($query) use ($user) {
                $query->where('user_id', $user->user_id);
            })
            ->where('sender_type', 'support')
            ->where('is_read', false)
            ->count();
            
        return response()->json([
            'status' => 'success',
            'has_new_messages' => $unreadCount > 0,
            'unread_count' => $unreadCount
        ]);
    }
    
    /**
     * بستن گفتگو توسط کاربر
     *
     * @param int $chatId
     * @return \Illuminate\Http\Response
     */
    public function closeChat($chatId)
    {
        $user = Auth::user();
        
        // بررسی دسترسی کاربر به این گفتگو
        $chat = Chat::where('chat_id', $chatId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();
            
        $chat->status = 'closed';
        $chat->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'گفتگو با موفقیت بسته شد',
        ]);
    }
}
