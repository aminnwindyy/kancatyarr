<?php

namespace App\Http\Controllers\Api;

use App\Events\NewTicketMessage;
use App\Events\TicketStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketReplyRequest;
use App\Http\Requests\TicketStatusUpdateRequest;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketResource;
use App\Http\Resources\TicketMessageResource;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Notifications\NewTicketMessage as NewTicketMessageNotification;
use App\Notifications\TicketStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class TicketController extends Controller
{
    /**
     * نمایش لیست تیکت‌ها با فیلتر وضعیت
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('support');
        
        // استخراج پارامترهای فیلتر
        $filter = $request->input('filter');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        
        // تعریف کوئری پایه
        $query = Ticket::with('latestMessage', 'user')
                        ->latest('created_at');
        
        // اعمال فیلتر کاربر اگر ادمین نیست
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }
        
        // اعمال فیلتر وضعیت
        if ($filter) {
            $query->filterByStatus($filter);
        }
        
        // اجرای پرس و جو با صفحه‌بندی
        $tickets = $query->paginate($limit, ['*'], 'page', $page);
        
        return response()->json([
            'data' => TicketResource::collection($tickets),
            'total_pages' => $tickets->lastPage(),
            'current_page' => $tickets->currentPage(),
            'total' => $tickets->total(),
        ]);
    }

    /**
     * ایجاد تیکت جدید
     *
     * @param TicketStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TicketStoreRequest $request)
    {
        // ایجاد تیکت جدید
        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'status' => 'open',
            'is_read_by_admin' => false,
            'is_read_by_user' => true,
        ]);
        
        // ذخیره فایل اگر آپلود شده باشد
        $filePath = null;
        $fileName = null;
        $fileType = null;
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $filePath = $file->store('ticket-attachments/' . $ticket->id, 'public');
        }
        
        // ایجاد پیام اولیه
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'content' => $request->content,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'sent_at' => now(),
        ]);
        
        // ارسال اعلان به ادمین‌ها
        $admins = \App\Models\User::role(['admin', 'support'])->get();
        Notification::send($admins, new NewTicketMessageNotification($message));
        
        // ارسال رویداد به کانال برای بروزرسانی لحظه‌ای
        broadcast(new NewTicketMessage($message))->toOthers();
        
        return response()->json([
            'message' => 'تیکت با موفقیت ایجاد شد.',
            'ticket' => new TicketResource($ticket->load('messages'))
        ], 201);
    }

    /**
     * نمایش جزئیات تیکت
     *
     * @param int $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($ticketId)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('support');
        
        // بازیابی تیکت با پیام‌های آن
        $ticket = Ticket::with(['messages' => function ($query) {
                        $query->with('sender')->latest('sent_at');
                    }, 'user'])
                    ->findOrFail($ticketId);
        
        // بررسی دسترسی: فقط ادمین یا مالک تیکت می‌تواند آن را ببیند
        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'شما اجازه دسترسی به این تیکت را ندارید.'], 403);
        }
        
        // علامت‌گذاری به عنوان خوانده شده
        if ($isAdmin && !$ticket->is_read_by_admin) {
            $ticket->is_read_by_admin = true;
            $ticket->save();
        } elseif (!$isAdmin && !$ticket->is_read_by_user) {
            $ticket->is_read_by_user = true;
            $ticket->save();
        }
        
        return response()->json([
            'ticket' => new TicketResource($ticket)
        ]);
    }

    /**
     * پاسخ دادن به تیکت
     *
     * @param TicketReplyRequest $request
     * @param int $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(TicketReplyRequest $request, $ticketId)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('support');
        
        // بازیابی تیکت
        $ticket = Ticket::findOrFail($ticketId);
        
        // بررسی دسترسی: فقط ادمین یا مالک تیکت می‌تواند پاسخ دهد
        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'شما اجازه پاسخگویی به این تیکت را ندارید.'], 403);
        }
        
        // بررسی وضعیت: اگر تیکت بسته شده باشد، امکان پاسخگویی وجود ندارد
        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'این تیکت بسته شده و امکان پاسخگویی به آن وجود ندارد.'], 400);
        }
        
        // ذخیره فایل اگر آپلود شده باشد
        $filePath = null;
        $fileName = null;
        $fileType = null;
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $filePath = $file->store('ticket-attachments/' . $ticket->id, 'public');
        }
        
        // ایجاد پیام جدید
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $user->id,
            'content' => $request->content,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'sent_at' => now(),
        ]);
        
        // به‌روزرسانی وضعیت خوانده شدن
        if ($isAdmin) {
            $ticket->is_read_by_admin = true;
            $ticket->is_read_by_user = false;
            
            // اگر تیکت در وضعیت در انتظار بررسی باشد، به حالت باز تغییر می‌کند
            if ($ticket->status === 'pending') {
                $previousStatus = $ticket->status;
                $ticket->status = 'open';
                
                // ارسال رویداد تغییر وضعیت
                broadcast(new TicketStatusUpdated($ticket, $previousStatus))->toOthers();
                
                // ارسال اعلان به کاربر
                $ticket->user->notify(new TicketStatusChanged($ticket, $previousStatus));
            }
        } else {
            $ticket->is_read_by_user = true;
            $ticket->is_read_by_admin = false;
        }
        
        $ticket->save();
        
        // ارسال اعلان به طرف مقابل
        if ($isAdmin) {
            // اگر پاسخ دهنده ادمین است، اعلان به کاربر ارسال می‌شود
            $ticket->user->notify(new NewTicketMessageNotification($message));
        } else {
            // اگر پاسخ دهنده کاربر است، اعلان به همه ادمین‌ها ارسال می‌شود
            $admins = \App\Models\User::role(['admin', 'support'])->get();
            Notification::send($admins, new NewTicketMessageNotification($message));
        }
        
        // ارسال رویداد به کانال برای بروزرسانی لحظه‌ای
        broadcast(new NewTicketMessage($message))->toOthers();
        
        return response()->json([
            'message' => 'پاسخ با موفقیت ارسال شد.',
            'data' => new TicketMessageResource($message)
        ]);
    }

    /**
     * تغییر وضعیت تیکت
     *
     * @param TicketStatusUpdateRequest $request
     * @param int $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(TicketStatusUpdateRequest $request, $ticketId)
    {
        // فقط ادمین و پشتیبان می‌توانند وضعیت تیکت را تغییر دهند
        // این بررسی در فرم ریکوئست انجام می‌شود
        
        // بازیابی تیکت
        $ticket = Ticket::findOrFail($ticketId);
        
        // اگر وضعیت تیکت تغییر نکرده باشد
        if ($ticket->status === $request->status) {
            return response()->json(['message' => 'وضعیت تیکت قبلاً ' . $request->status . ' بوده است.']);
        }
        
        // ذخیره وضعیت قبلی
        $previousStatus = $ticket->status;
        
        // به‌روزرسانی وضعیت
        $ticket->status = $request->status;
        $ticket->save();
        
        // ارسال اعلان به کاربر
        $ticket->user->notify(new TicketStatusChanged($ticket, $previousStatus));
        
        // ارسال رویداد به کانال برای بروزرسانی لحظه‌ای
        broadcast(new TicketStatusUpdated($ticket, $previousStatus))->toOthers();
        
        return response()->json([
            'message' => 'وضعیت تیکت با موفقیت تغییر کرد.',
            'status' => $ticket->status
        ]);
    }

    /**
     * دانلود پیوست تیکت
     *
     * @param int $ticketId
     * @param int $messageId
     * @return \Illuminate\Http\Response
     */
    public function downloadAttachment($ticketId, $messageId)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('support');
        
        // بازیابی تیکت
        $ticket = Ticket::findOrFail($ticketId);
        
        // بررسی دسترسی: فقط ادمین یا مالک تیکت می‌تواند پیوست را دانلود کند
        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'شما اجازه دانلود این فایل را ندارید.'], 403);
        }
        
        // بازیابی پیام
        $message = TicketMessage::where('ticket_id', $ticketId)
                               ->where('id', $messageId)
                               ->firstOrFail();
        
        // بررسی وجود پیوست
        if (!$message->hasAttachment()) {
            return response()->json(['message' => 'این پیام فاقد پیوست است.'], 404);
        }
        
        // دانلود فایل
        return Storage::disk('public')->download(
            $message->file_path,
            $message->file_name ?: basename($message->file_path)
        );
    }
} 