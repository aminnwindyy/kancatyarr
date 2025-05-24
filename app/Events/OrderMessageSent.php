<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderMessage;
use App\Models\Order;

class OrderMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * پیام ارسال شده
     *
     * @var \App\Models\OrderMessage
     */
    public $message;

    /**
     * سفارش مرتبط با پیام
     *
     * @var \App\Models\Order
     */
    public $order;

    /**
     * داده‌های پیام برای ارسال در کانال
     *
     * @var array
     */
    public $messageData;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\OrderMessage  $message
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct(OrderMessage $message, Order $order)
    {
        $this->message = $message;
        $this->order = $order;
        
        // آماده‌سازی داده‌های پیام برای ارسال
        $this->messageData = [
            'id' => $message->id,
            'order_id' => $message->order_id,
            'content' => $message->content,
            'sender' => [
                'id' => $message->sender->user_id,
                'name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                'profile_image' => $message->sender->profile_image,
            ],
            'has_file' => $message->hasFile(),
            'file_info' => $message->hasFile() ? [
                'file_name' => $message->file_name,
                'file_type' => $message->file_type,
                'download_url' => url('/api/orders/messages/' . $message->id . '/download-file'),
            ] : null,
            'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
            'is_read' => $message->is_read,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // ایجاد دو کانال خصوصی:
        // 1. برای کاربر صاحب سفارش
        // 2. برای سرویس‌دهنده مرتبط با سفارش
        
        $channels = [
            new PrivateChannel('order.' . $this->order->order_id),
        ];
        
        // کانال برای کاربر صاحب سفارش
        $channels[] = new PrivateChannel('user.' . $this->order->user_id);
        
        // کانال برای سرویس‌دهنده (اگر وجود داشته باشد)
        if ($this->order->service_provider_id) {
            $channels[] = new PrivateChannel('service-provider.' . $this->order->service_provider_id);
        }
        
        return $channels;
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'order.message.sent';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->messageData,
            'order_id' => $this->order->order_id
        ];
    }
} 