<?php

namespace App\Events;

use App\Models\DiscountRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscountRequestUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $discountRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(DiscountRequest $discountRequest)
    {
        $this->discountRequest = $discountRequest;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('discount-requests'),
            new PrivateChannel('discount-request.'.$this->discountRequest->id),
            new PrivateChannel('service-provider.'.$this->discountRequest->service_provider_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'discount.request.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->discountRequest->id,
            'status' => $this->discountRequest->status,
            'updated_at' => $this->discountRequest->updated_at,
        ];
    }
}
