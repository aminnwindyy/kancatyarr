<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProviderDocument;

class DocumentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;

    /**
     * Create a new event instance.
     *
     * @param ServiceProviderDocument $document
     * @return void
     */
    public function __construct(ServiceProviderDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('service-provider.' . $this->document->service_provider_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'document_id' => $this->document->id,
            'document_type' => $this->document->document_type,
            'status' => $this->document->status,
            'description' => $this->document->description,
            'updated_at' => $this->document->updated_at->toDateTimeString(),
        ];
    }
}
