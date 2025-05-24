<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class DiscountCode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'max_uses_per_user',
        'min_order_amount',
        'is_active',
        'expires_at',
        'description',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'float',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'min_order_amount' => 'float',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the users who have used this discount code.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(DiscountCodeUsage::class);
    }

    /**
     * Get the products associated with this discount code.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_code_products');
    }

    /**
     * Get the subscription plans associated with this discount code.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'discount_code_plans', 'discount_code_id', 'plan_id');
    }

    /**
     * Check if discount code is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < Carbon::now();
    }

    /**
     * Check if discount code is valid.
     *
     * @param int|null $userId
     * @return bool
     */
    public function isValid($userId = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->max_uses && $this->usages()->count() >= $this->max_uses) {
            return false;
        }

        if ($userId && $this->max_uses_per_user && $this->usages()->where('user_id', $userId)->count() >= $this->max_uses_per_user) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for a given amount.
     *
     * @param float $amount
     * @return float
     */
    public function calculateDiscount($amount): float
    {
        if ($this->type === 'percentage') {
            $discountAmount = ($amount * $this->value) / 100;
        } else {
            $discountAmount = $this->value;
            // تخفیف ثابت نباید از مبلغ کل بیشتر باشد
            if ($discountAmount > $amount) {
                $discountAmount = $amount;
            }
        }

        return $discountAmount;
    }

    /**
     * Increment the used count.
     *
     * @return void
     */
    public function incrementUsedCount(): void
    {
        $this->increment('used_count');
    }
} 