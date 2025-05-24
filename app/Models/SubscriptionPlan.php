<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'plan_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'features',
        'is_featured',
        'is_active',
        'max_users',
        'discount_percentage',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:0',
        'duration_days' => 'integer',
        'features' => 'json',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'max_users' => 'integer',
        'discount_percentage' => 'integer',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Calculate discounted price.
     *
     * @return float
     */
    public function getDiscountedPriceAttribute(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->price * (1 - $this->discount_percentage / 100);
        }
        
        return $this->price;
    }
}
