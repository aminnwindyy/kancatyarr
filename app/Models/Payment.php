<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'method',
        'status',
        'transaction_id',
        'reference_id',
        'gateway',
        'gateway_response',
        'card_number',
        'tracking_code',
        'ip_address',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'gateway_response' => 'array',
    ];

    /**
     * Payment methods
     */
    const METHOD_ONLINE = 'online';
    const METHOD_WALLET = 'wallet';

    /**
     * Payment statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful()
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if payment is refunded
     */
    public function isRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Generate tracking code for payment
     */
    public static function generateTrackingCode()
    {
        $prefix = 'PAY';
        $timestamp = now()->format('ymdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
} 