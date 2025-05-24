<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ReferralProgram extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'referrer_reward_type',
        'referrer_reward_value',
        'referred_reward_type',
        'referred_reward_value',
        'referral_limit',
        'minimum_purchase_amount',
        'subscription_plan_id',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'referrer_reward_value' => 'float',
        'referred_reward_value' => 'float',
        'referral_limit' => 'integer',
        'minimum_purchase_amount' => 'float',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * رابطه با جدول دعوت‌ها
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * رابطه با جدول پلن‌های اشتراک
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * بررسی اینکه آیا برنامه دعوت فعال است
     *
     * @return bool
     */
    public function isActive()
    {
        if (!$this->is_active) {
            return false;
        }

        // بررسی تاریخ شروع
        if ($this->starts_at && $this->starts_at > Carbon::now()) {
            return false;
        }

        // بررسی تاریخ انقضا
        if ($this->expires_at && $this->expires_at < Carbon::now()) {
            return false;
        }

        return true;
    }

    /**
     * محاسبه پاداش دعوت‌کننده بر اساس مبلغ خرید
     *
     * @param float $purchaseAmount
     * @return float
     */
    public function calculateReferrerReward($purchaseAmount = 0)
    {
        if ($this->referrer_reward_type === 'fixed') {
            return $this->referrer_reward_value;
        } elseif ($this->referrer_reward_type === 'percentage') {
            return ($purchaseAmount * $this->referrer_reward_value) / 100;
        } else {
            // نوع پاداش اشتراک است
            return 0; // منطق پیچیده‌تر پاداش اشتراک می‌تواند اینجا پیاده‌سازی شود
        }
    }

    /**
     * محاسبه پاداش دعوت‌شونده بر اساس مبلغ خرید
     *
     * @param float $purchaseAmount
     * @return float
     */
    public function calculateReferredReward($purchaseAmount = 0)
    {
        if ($this->referred_reward_type === 'fixed') {
            return $this->referred_reward_value;
        } elseif ($this->referred_reward_type === 'percentage') {
            return ($purchaseAmount * $this->referred_reward_value) / 100;
        } else {
            // نوع پاداش اشتراک است
            return 0; // منطق پیچیده‌تر پاداش اشتراک می‌تواند اینجا پیاده‌سازی شود
        }
    }
} 