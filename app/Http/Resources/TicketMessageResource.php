<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'is_admin' => $this->sender->hasRole('admin') || $this->sender->hasRole('support'),
            ],
            'sent_at' => $this->sent_at->format('Y-m-d H:i:s'),
            'has_attachment' => $this->hasAttachment(),
            'file_url' => $this->when($this->hasAttachment(), $this->getFileUrl()),
            'file_name' => $this->when($this->hasAttachment(), $this->file_name),
            'file_type' => $this->when($this->hasAttachment(), $this->file_type),
        ];
    }
} 