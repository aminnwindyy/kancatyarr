<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $previousStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $ticket, $previousStatus)
    {
        $this->ticket = $ticket;
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->ticket->id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $statusTextMap = [
            'open' => 'باز',
            'closed' => 'بسته شده',
            'pending' => 'در انتظار بررسی'
        ];

        return [
            'ticket_id' => $this->ticket->id,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->ticket->status,
            'status_text' => $statusTextMap[$this->ticket->status] ?? $this->ticket->status,
            'updated_at' => now()->toDateTimeString(),
        ];
    }
} 