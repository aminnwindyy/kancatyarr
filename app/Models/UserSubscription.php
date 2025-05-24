<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'starts_at',
        'expires_at',
        'price_paid',
        'payment_id',
        'status',
        'auto_renew',
        'next_billing_date',
        'canceled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'price_paid' => 'decimal:0',
        'auto_renew' => 'boolean',
        'next_billing_date' => 'datetime',
        'canceled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id', 'plan_id');
    }

    /**
     * Check if the subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->expires_at > now();
    }

    /**
     * Check if the subscription is canceled.
     *
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->canceled_at !== null;
    }

    /**
     * Check if the subscription is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Cancel this subscription.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->update([
            'auto_renew' => false,
            'canceled_at' => now(),
        ]);
    }

    /**
     * Renew this subscription.
     *
     * @param int $days
     * @return void
     */
    public function renew(int $days): void
    {
        $this->update([
            'starts_at' => now(),
            'expires_at' => now()->addDays($days),
            'status' => 'active',
            'next_billing_date' => $this->auto_renew ? now()->addDays($days) : null,
        ]);
    }
} 