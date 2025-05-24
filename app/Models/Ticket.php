<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subject',
        'status',
        'is_read_by_admin',
        'is_read_by_user',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read_by_admin' => 'boolean',
        'is_read_by_user' => 'boolean',
    ];

    /**
     * Get the user that owns the ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the ticket.
     */
    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Get the latest message for the ticket.
     */
    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class)->latest('sent_at');
    }

    /**
     * Scope a query to only include open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include closed tickets.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope a query to only include pending tickets.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeFilterByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope a query to only include unread tickets for admin.
     */
    public function scopeUnreadByAdmin($query)
    {
        return $query->where('is_read_by_admin', false);
    }

    /**
     * Scope a query to only include unread tickets for user.
     */
    public function scopeUnreadByUser($query)
    {
        return $query->where('is_read_by_user', false);
    }
} 