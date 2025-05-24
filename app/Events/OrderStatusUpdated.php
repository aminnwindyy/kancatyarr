<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders'),
            new PrivateChannel('order.'.$this->order->order_id),
            new PrivateChannel('user.'.$this->order->user_id),
            new PrivateChannel('service-provider.'.$this->order->service_provider_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->order_id,
            'status' => $this->order->status,
            'order_type' => $this->order->order_type,
            'delivery_type' => $this->order->delivery_type,
            'service_provider_id' => $this->order->service_provider_id,
            'service_provider_type' => $this->order->service_provider_type,
            'category_id' => $this->order->category_id,
            'user_id' => $this->order->user_id,
            'updated_at' => $this->order->updated_at,
        ];
    }
}
