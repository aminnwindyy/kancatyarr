<?php

namespace App\Events;

use App\Models\TicketMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTicketMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticketMessage;
    public $formattedMessage;

    /**
     * Create a new event instance.
     */
    public function __construct(TicketMessage $ticketMessage)
    {
        $this->ticketMessage = $ticketMessage;
        
        // آماده‌سازی پیام برای ارسال به کلاینت
        $this->formattedMessage = [
            'id' => $ticketMessage->id,
            'ticket_id' => $ticketMessage->ticket_id,
            'sender_id' => $ticketMessage->sender_id,
            'sender_name' => $ticketMessage->sender->name,
            'content' => $ticketMessage->content,
            'sent_at' => $ticketMessage->sent_at->toDateTimeString(),
            'has_attachment' => $ticketMessage->hasAttachment(),
            'file_url' => $ticketMessage->getFileUrl(),
            'file_name' => $ticketMessage->file_name,
            'is_admin' => $ticketMessage->sender->hasRole('admin') || $ticketMessage->sender->hasRole('support'),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.' . $this->ticketMessage->ticket_id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'new.message';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->formattedMessage
        ];
    }
} 