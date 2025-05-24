<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referral_program_id',
        'referrer_user_id',
        'referred_user_id',
        'status',
        'completed_at',
        'cancelled_at',
        'purchase_amount',
        'referrer_reward',
        'referred_reward',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'purchase_amount' => 'float',
        'referrer_reward' => 'float',
        'referred_reward' => 'float',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * رابطه با جدول برنامه‌های دعوت
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referralProgram()
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    /**
     * رابطه با کاربر دعوت‌کننده
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referrerUser()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * رابطه با کاربر دعوت‌شده
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * تکمیل دعوت
     *
     * @param float $purchaseAmount
     * @return void
     */
    public function complete($purchaseAmount = 0)
    {
        if ($this->status !== 'pending') {
            return;
        }

        $program = $this->referralProgram;
        
        if (!$program) {
            return;
        }

        $referrerReward = $program->calculateReferrerReward($purchaseAmount);
        $referredReward = $program->calculateReferredReward($purchaseAmount);

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'purchase_amount' => $purchaseAmount,
            'referrer_reward' => $referrerReward,
            'referred_reward' => $referredReward,
        ]);

        // اینجا می‌توان منطق اعطای پاداش را پیاده‌سازی کرد
    }

    /**
     * لغو دعوت
     *
     * @param string $reason
     * @return void
     */
    public function cancel($reason = 'دیگر')
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * فیلتر دعوت‌های در انتظار
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * فیلتر دعوت‌های تکمیل شده
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * فیلتر دعوت‌های لغو شده
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
} 