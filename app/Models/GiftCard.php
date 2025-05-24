<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class GiftCard extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gift_cards';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'gift_card_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'card_number',
        'amount',
        'created_by',
        'expiry_date',
        'is_used',
        'used_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_used' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'used_at' => 'datetime',
            'expiry_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the gift card.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the user that created the gift card.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
    
    /**
     * بررسی منقضی شدن کارت هدیه
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return Carbon::parse($this->expiry_date)->isPast();
    }
    
    /**
     * بررسی فعال بودن کارت هدیه
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired() && !$this->is_used;
    }
    
    /**
     * ایجاد کارت هدیه جدید برای کاربر
     *
     * @param int $userId شناسه کاربر
     * @param float $amount مبلغ کارت هدیه
     * @param int $createdBy شناسه کاربر ایجاد کننده (معمولاً ادمین)
     * @param int $expiryMonths تعداد ماه‌های اعتبار (پیش‌فرض 6 ماه)
     * @return self
     */
    public static function createForUser(int $userId, float $amount, int $createdBy, int $expiryMonths = 6): self
    {
        return self::create([
            'user_id' => $userId,
            'card_number' => self::generateUniqueCardNumber(),
            'amount' => $amount,
            'created_by' => $createdBy,
            'expiry_date' => Carbon::now()->addMonths($expiryMonths),
            'is_used' => false,
            'status' => 'active',
        ]);
    }
    
    /**
     * تولید شماره کارت هدیه یکتا
     *
     * @return string
     */
    protected static function generateUniqueCardNumber(): string
    {
        $prefix = 'GIFT-';
        $randomPart = mt_rand(100000000, 999999999);
        $cardNumber = $prefix . $randomPart;
        
        // بررسی یکتا بودن شماره کارت
        while (self::where('card_number', $cardNumber)->exists()) {
            $randomPart = mt_rand(100000000, 999999999);
            $cardNumber = $prefix . $randomPart;
        }
        
        return $cardNumber;
    }
    
    /**
     * نشانه‌گذاری کارت هدیه به عنوان استفاده شده
     *
     * @return bool
     */
    public function markAsUsed(): bool
    {
        if ($this->is_used || $this->isExpired() || $this->status !== 'active') {
            return false;
        }
        
        $this->is_used = true;
        $this->used_at = Carbon::now();
        $this->status = 'used';
        
        return $this->save();
    }
}
