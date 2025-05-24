<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'new_user_id',
        'referrer_user_id',
        'bonus_amount_per_user',
        'referral_date',
        'new_user_wallet_transaction_id',
        'referrer_wallet_transaction_id',
        'referral_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bonus_amount_per_user' => 'decimal:2',
        'referral_date' => 'datetime',
    ];

    /**
     * کاربر دعوت شده
     *
     * @return BelongsTo
     */
    public function newUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_user_id', 'user_id');
    }

    /**
     * کاربر دعوت کننده
     *
     * @return BelongsTo
     */
    public function referrerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id', 'user_id');
    }

    /**
     * تراکنش کیف پول کاربر جدید
     *
     * @return BelongsTo
     */
    public function newUserWalletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'new_user_wallet_transaction_id', 'transaction_id');
    }

    /**
     * تراکنش کیف پول کاربر دعوت کننده
     *
     * @return BelongsTo
     */
    public function referrerWalletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'referrer_wallet_transaction_id', 'transaction_id');
    }

    /**
     * رکورد دعوت
     *
     * @return BelongsTo
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id');
    }
}
