<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderFile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'download_count',
        'is_active',
        'expires_at',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the order that owns the file.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the order item that owns the file.
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the user (seller) that uploaded the file.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Check if file has expired
     */
    public function hasExpired()
    {
        if (empty($this->expires_at)) {
            return false;
        }
        
        return now()->greaterThan($this->expires_at);
    }
    
    /**
     * Check if file can be downloaded
     */
    public function canBeDownloaded()
    {
        return $this->is_active && !$this->hasExpired();
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloadCount()
    {
        $this->download_count++;
        $this->save();
        
        return $this;
    }
} 