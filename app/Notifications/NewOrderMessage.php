<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\OrderMessage;

class NewOrderMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * پیام سفارش
     *
     * @var \App\Models\OrderMessage
     */
    protected $message;

    /**
     * ایجاد یک نمونه جدید از نوتیفیکیشن.
     *
     * @param  \App\Models\OrderMessage  $message
     * @return void
     */
    public function __construct(OrderMessage $message)
    {
        $this->message = $message;
    }

    /**
     * تعیین کانال‌های ارسال نوتیفیکیشن.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * قالب پیام ایمیل برای نوتیفیکیشن.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('پیام جدید در سفارش #' . $this->message->order_id)
            ->line('شما یک پیام جدید در سفارش خود دارید.')
            ->action('مشاهده پیام', url('/dashboard/orders/' . $this->message->order_id))
            ->line('با تشکر از همراهی شما');
    }

    /**
     * داده‌های نوتیفیکیشن برای کانال دیتابیس.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'order_id' => $this->message->order_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->first_name . ' ' . $this->message->sender->last_name,
            'title' => 'پیام جدید در سفارش',
            'content' => mb_substr($this->message->content, 0, 50) . (mb_strlen($this->message->content) > 50 ? '...' : ''),
            'time' => now()->format('Y-m-d H:i:s'),
            'has_file' => $this->message->hasFile(),
        ];
    }
} 