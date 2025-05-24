<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'transaction_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'amount',
        'type',
        'description',
        'reference',
        'balance_after',
        'status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Transaction types
     */
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_GIFT = 'gift';

    /**
     * Transaction status
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';

    /**
     * منابع تراکنش‌های ممکن
     */
    const SOURCE_REFERRAL = 'referral_transaction';
    const SOURCE_ADMIN = 'admin_action';
    const SOURCE_ORDER = 'order_payment';

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'wallet_id');
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment associated with the transaction.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reference', 'id');
    }

    /**
     * Get the order that owns the transaction.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    /**
     * Get the gift card associated with the transaction.
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class, 'gift_card_id', 'gift_card_id');
    }

    /**
     * Get the referral transaction that led to this wallet transaction.
     */
    public function referrerReferralTransaction(): HasOne
    {
        return $this->hasOne(ReferralTransaction::class, 'referrer_wallet_transaction_id', 'transaction_id');
    }

    /**
     * Get the referral transaction for new user that led to this wallet transaction.
     */
    public function newUserReferralTransaction(): HasOne
    {
        return $this->hasOne(ReferralTransaction::class, 'new_user_wallet_transaction_id', 'transaction_id');
    }

    /**
     * Check if transaction is deposit
     */
    public function isDeposit()
    {
        return $this->type === self::TYPE_DEPOSIT;
    }

    /**
     * Check if transaction is withdrawal
     */
    public function isWithdrawal()
    {
        return $this->type === self::TYPE_WITHDRAWAL;
    }

    /**
     * Check if transaction is payment
     */
    public function isPayment()
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    /**
     * Check if transaction is refund
     */
    public function isRefund()
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get transaction type text
     */
    public function getTypeTextAttribute()
    {
        switch ($this->type) {
            case self::TYPE_DEPOSIT:
                return 'واریز';
            case self::TYPE_WITHDRAWAL:
                return 'برداشت';
            case self::TYPE_PAYMENT:
                return 'پرداخت';
            case self::TYPE_REFUND:
                return 'استرداد';
            case self::TYPE_ADJUSTMENT:
                return 'تنظیم';
            case self::TYPE_GIFT:
                return 'هدیه';
            default:
                return 'نامشخص';
        }
    }

    /**
     * ثبت تراکنش جدید در کیف پول
     *
     * @param int $walletId شناسه کیف پول
     * @param string $type نوع تراکنش
     * @param float $amount مبلغ تراکنش (مثبت یا منفی)
     * @param string|null $description توضیحات
     * @param string|null $sourceType نوع منبع تراکنش
     * @param int|null $sourceId شناسه منبع
     * @param int|null $orderId شناسه سفارش
     * @param int|null $giftCardId شناسه کارت هدیه
     * @return self
     */
    public static function createTransaction(
        int $walletId,
        string $type,
        float $amount,
        ?string $description = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $orderId = null,
        ?int $giftCardId = null
    ): self {
        return self::create([
            'wallet_id' => $walletId,
            'transaction_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => now(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'order_id' => $orderId,
            'gift_card_id' => $giftCardId
        ]);
    }

    /**
     * ایجاد تراکنش کارت هدیه برای دعوت
     *
     * @param int $walletId شناسه کیف پول
     * @param float $amount مبلغ پاداش
     * @param int $referralId شناسه دعوت
     * @return self
     */
    public static function createReferralGiftTransaction(int $walletId, float $amount, int $referralId): self
    {
        return self::createTransaction(
            $walletId,
            self::TYPE_GIFT_CARD_REFERRAL,
            $amount,
            'پاداش دعوت کاربر جدید',
            self::SOURCE_REFERRAL,
            $referralId
        );
    }
}
