<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('support');

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'status' => $this->status,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'is_read' => $isAdmin ? $this->is_read_by_admin : $this->is_read_by_user,
            'latest_message' => $this->whenLoaded('latestMessage', function () {
                return [
                    'content' => substr($this->latestMessage->content, 0, 100) . (strlen($this->latestMessage->content) > 100 ? '...' : ''),
                    'sent_at' => $this->latestMessage->sent_at->format('Y-m-d H:i:s'),
                    'sender_name' => $this->latestMessage->sender->name,
                ];
            }),
            'messages' => TicketMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
} 