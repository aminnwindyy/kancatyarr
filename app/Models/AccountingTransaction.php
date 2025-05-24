<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTransaction extends Model
{
    use HasFactory;
    
    /**
     * نوع تراکنش: برداشت کاربر
     */
    const TYPE_WITHDRAW_USER = 'withdraw_user';
    
    /**
     * نوع تراکنش: برداشت خدمات‌دهنده
     */
    const TYPE_WITHDRAW_PROVIDER = 'withdraw_provider';
    
    /**
     * نوع تراکنش: واریز
     */
    const TYPE_DEPOSIT = 'deposit';
    
    /**
     * نوع تراکنش: کارمزد
     */
    const TYPE_FEE = 'fee';
    
    /**
     * نوع تراکنش: استرداد
     */
    const TYPE_REFUND = 'refund';
    
    /**
     * نوع تراکنش: تسویه
     */
    const TYPE_SETTLEMENT = 'settlement';
    
    /**
     * وضعیت تراکنش: در انتظار
     */
    const STATUS_PENDING = 'pending';
    
    /**
     * وضعیت تراکنش: تایید شده
     */
    const STATUS_APPROVED = 'approved';
    
    /**
     * وضعیت تراکنش: رد شده
     */
    const STATUS_REJECTED = 'rejected';
    
    /**
     * وضعیت تراکنش: تسویه شده
     */
    const STATUS_SETTLED = 'settled';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'provider_id',
        'type',
        'amount',
        'status',
        'reference_id',
        'metadata',
        'bank_account',
        'tracking_code',
        'admin_id',
        'reject_reason',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:0',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * رابطه با کاربر
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    
    /**
     * رابطه با خدمات‌دهنده
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }
    
    /**
     * رابطه با ادمین تایید کننده
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های از نوع خاص
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های با وضعیت خاص
     */
    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های کاربر خاص
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های خدمات‌دهنده خاص
     */
    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های در انتظار
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های تایید شده
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های رد شده
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
    
    /**
     * محدود کردن کوئری به تراکنش‌های تسویه شده
     */
    public function scopeSettled($query)
    {
        return $query->where('status', self::STATUS_SETTLED);
    }
    
    /**
     * محدود کردن کوئری به برداشت‌ها
     */
    public function scopeWithdrawals($query)
    {
        return $query->whereIn('type', [self::TYPE_WITHDRAW_USER, self::TYPE_WITHDRAW_PROVIDER]);
    }
    
    /**
     * محدود کردن کوئری به واریزها
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', self::TYPE_DEPOSIT);
    }
    
    /**
     * آیا تراکنش برداشت است؟
     */
    public function isWithdrawal(): bool
    {
        return in_array($this->type, [self::TYPE_WITHDRAW_USER, self::TYPE_WITHDRAW_PROVIDER]);
    }
    
    /**
     * آیا تراکنش واریز است؟
     */
    public function isDeposit(): bool
    {
        return $this->type === self::TYPE_DEPOSIT;
    }
    
    /**
     * آیا تراکنش در انتظار است؟
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    /**
     * آیا تراکنش تایید شده است؟
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
    
    /**
     * آیا تراکنش رد شده است؟
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
    
    /**
     * آیا تراکنش تسویه شده است؟
     */
    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }
    
    /**
     * تایید تراکنش
     * 
     * @param int $adminId شناسه ادمین
     * @param string|null $trackingCode کد پیگیری
     * @return bool
     */
    public function approve(int $adminId, ?string $trackingCode = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        
        $this->status = self::STATUS_APPROVED;
        $this->admin_id = $adminId;
        $this->tracking_code = $trackingCode;
        $this->metadata = array_merge((array)$this->metadata, [
            'approved_at' => now()->toIso8601String(),
            'approved_by' => $adminId
        ]);
        
        return $this->save();
    }
    
    /**
     * رد تراکنش
     * 
     * @param int $adminId شناسه ادمین
     * @param string $reason دلیل رد
     * @return bool
     */
    public function reject(int $adminId, string $reason): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }
        
        $this->status = self::STATUS_REJECTED;
        $this->admin_id = $adminId;
        $this->metadata = array_merge((array)$this->metadata, [
            'rejected_at' => now()->toIso8601String(),
            'rejected_by' => $adminId,
            'rejection_reason' => $reason
        ]);
        
        return $this->save();
    }
    
    /**
     * تسویه تراکنش
     * 
     * @param int $adminId شناسه ادمین
     * @param string|null $trackingCode کد پیگیری
     * @return bool
     */
    public function settle(int $adminId, ?string $trackingCode = null): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }
        
        $this->status = self::STATUS_SETTLED;
        $this->admin_id = $adminId;
        $this->tracking_code = $trackingCode ?? $this->tracking_code;
        $this->metadata = array_merge((array)$this->metadata, [
            'settled_at' => now()->toIso8601String(),
            'settled_by' => $adminId
        ]);
        
        return $this->save();
    }
}
