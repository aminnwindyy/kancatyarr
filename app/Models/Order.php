<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'total_price',
        'status',
        'payment_method',
        'payment_status',
        'payment_id',
        'admin_approved_at',
        'admin_approved_by',
        'seller_delivered_at',
        'delivered_at',
        'tracking_code',
        'discount_code',
        'discount_amount',
        'final_price',
        'notes',
        'admin_notes',
        'seller_notes',
        'reject_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'admin_approved_at' => 'datetime',
        'seller_delivered_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Statuses for the order
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_ADMIN_APPROVED = 'admin_approved';
    const STATUS_SENT_TO_SELLER = 'sent_to_seller';
    const STATUS_SELLER_UPLOADED = 'seller_uploaded';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Payment methods for the order
     */
    const PAYMENT_METHOD_ONLINE = 'online';
    const PAYMENT_METHOD_WALLET = 'wallet';

    /**
     * Payment statuses for the order
     */
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin that approved the order.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payment for the order.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    
    /**
     * Get the files for the order.
     */
    public function files()
    {
        return $this->hasMany(OrderFile::class);
    }
    
    /**
     * Check if order is paid
     */
    public function isPaid()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }
    
    /**
     * Check if order is admin approved
     */
    public function isAdminApproved()
    {
        return !empty($this->admin_approved_at);
    }
    
    /**
     * Check if order is seller delivered
     */
    public function isSellerDelivered()
    {
        return !empty($this->seller_delivered_at);
    }
    
    /**
     * Get status text in Persian
     */
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'در انتظار پرداخت';
            case self::STATUS_PAID:
                return 'پرداخت شده - در انتظار تایید';
            case self::STATUS_ADMIN_APPROVED:
                return 'تایید شده - در انتظار ارسال به فروشنده';
            case self::STATUS_SENT_TO_SELLER:
                return 'ارسال شده به فروشنده';
            case self::STATUS_SELLER_UPLOADED:
                return 'آپلود شده توسط فروشنده';
            case self::STATUS_COMPLETED:
                return 'تکمیل شده';
            case self::STATUS_REJECTED:
                return 'رد شده';
            case self::STATUS_CANCELED:
                return 'لغو شده';
            case self::STATUS_REFUNDED:
                return 'مسترد شده';
            default:
                return 'نامشخص';
        }
    }
    
    /**
     * Generate a new order number
     */
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymdHis');
        $random = mt_rand(100, 999);
        
        return $prefix . $timestamp . $random;
    }

    /**
     * Get the service provider that owns the order.
     */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id', 'id');
    }

    /**
     * Get the product that belongs to the order.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Get the messages for the order.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'order_id', 'order_id');
    }

    /**
     * Get the invoice associated with the order.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'order_id', 'order_id');
    }

    /**
     * Get the wallet transactions for the order.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'order_id', 'order_id');
    }

    /**
     * Get the review associated with the order.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'order_id', 'order_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'order_id', 'order_id');
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(OrderShipping::class, 'order_id', 'order_id');
    }

    public function status_history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'order_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get the category that belongs to the order.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    /**
     * Scope a query to only include orders created today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Scope a query to only include orders of a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }
    
    /**
     * Scope a query to only include orders of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $orderType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $orderType)
    {
        if ($orderType && $orderType != 'all') {
            return $query->where('order_type', $orderType);
        }
        return $query;
    }
    
    /**
     * Scope a query to only include orders of a specific delivery type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $deliveryType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfDeliveryType($query, $deliveryType)
    {
        if ($deliveryType) {
            return $query->where('delivery_type', $deliveryType);
        }
        return $query;
    }

    /**
     * Scope a query to only include orders of a specific service provider type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $providerType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfProviderType($query, $providerType)
    {
        if ($providerType) {
            return $query->where('service_provider_type', $providerType);
        }
        return $query;
    }

    /**
     * Scope a query to only include orders of a specific category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        }
        return $query;
    }

    /**
     * Get the payment transactions for the order.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id', 'order_id');
    }

    /**
     * Get the order messages.
     */
    public function orderMessages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_id', 'order_id')
                    ->orderBy('created_at', 'asc');
    }
}
