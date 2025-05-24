<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'sender_id',
        'content',
        'file_path',
        'file_name',
        'file_type',
        'sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the ticket that owns the message.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user that sent the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Check if the message has an attachment.
     */
    public function hasAttachment()
    {
        return !is_null($this->file_path);
    }

    /**
     * Get file URL if an attachment exists.
     */
    public function getFileUrl()
    {
        if ($this->hasAttachment()) {
            return url('storage/' . $this->file_path);
        }
        return null;
    }
} 