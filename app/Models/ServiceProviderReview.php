<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProviderReview extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'service_provider_id',
        'rating',
        'comment',
        'status',
        'rejection_reason',
        'admin_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the service provider that owns the review.
     */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id', 'id');
    }

    /**
     * Get the order that owns the review.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    /**
     * Get the admin that approved/rejected the review.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }

    /**
     * Get the status history logs for this review.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ServiceProviderReviewLog::class, 'review_id', 'id');
    }

    /**
     * Scope a query to only include pending reviews.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved reviews.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected reviews.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to filter by service provider.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $serviceProviderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfServiceProvider($query, $serviceProviderId)
    {
        return $query->where('service_provider_id', $serviceProviderId);
    }

    /**
     * Check if the review can be submitted for this order.
     *
     * @param  int  $orderId
     * @param  int  $userId
     * @return bool
     */
    public static function canSubmitReview($orderId, $userId): bool
    {
        // Check if the order exists and is completed
        $order = Order::where('order_id', $orderId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        if (!$order) {
            return false;
        }

        // Check if a review already exists for this order
        $existingReview = self::where('order_id', $orderId)
            ->where('user_id', $userId)
            ->exists();

        return !$existingReview;
    }
}
