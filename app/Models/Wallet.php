<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallets';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'wallet_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_deposit',
        'total_withdrawal',
        'total_spent',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the transactions for the wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id', 'wallet_id');
    }
    
    /**
     * Get the gift cards for the wallet owner.
     */
    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'user_id', 'user_id');
    }
    
    /**
     * Check if wallet has enough balance for a payment
     */
    public function hasEnoughBalance($amount)
    {
        return $this->is_active && $this->balance >= $amount;
    }
    
    /**
     * Deposit amount to wallet
     */
    public function deposit($amount, $description = null, $reference = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $this->balance += $amount;
        $this->total_deposit += $amount;
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'amount' => $amount,
            'type' => WalletTransaction::TYPE_DEPOSIT,
            'description' => $description,
            'reference' => $reference,
            'balance_after' => $this->balance,
        ]);

        return $this;
    }
    
    /**
     * Withdraw amount from wallet
     */
    public function withdraw($amount, $description = null, $reference = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if (!$this->hasEnoughBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }

        $this->balance -= $amount;
        $this->total_withdrawal += $amount;
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'amount' => $amount,
            'type' => WalletTransaction::TYPE_WITHDRAWAL,
            'description' => $description,
            'reference' => $reference,
            'balance_after' => $this->balance,
        ]);

        return $this;
    }
    
    /**
     * Spend amount from wallet (for order payment)
     */
    public function spend($amount, $orderId, $description = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if (!$this->hasEnoughBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }

        $this->balance -= $amount;
        $this->total_spent += $amount;
        $this->save();

        // Create transaction record
        $this->transactions()->create([
            'amount' => $amount,
            'type' => WalletTransaction::TYPE_PAYMENT,
            'description' => $description ?: 'پرداخت سفارش #' . $orderId,
            'reference' => 'order:' . $orderId,
            'balance_after' => $this->balance,
        ]);

        return $this;
    }
    
    /**
     * افزایش موجودی کیف پول
     *
     * @param float $amount مبلغ افزایش
     * @param string $type نوع تراکنش
     * @param string|null $description توضیحات
     * @param string|null $sourceType نوع منبع
     * @param int|null $sourceId شناسه منبع
     * @return WalletTransaction
     */
    public function depositGift(float $amount, string $type, ?string $description = null, ?string $sourceType = null, ?int $sourceId = null): WalletTransaction
    {
        $this->balance += $amount;
        $this->save();
        
        return WalletTransaction::createTransaction(
            $this->wallet_id,
            $type,
            $amount,
            $description,
            $sourceType,
            $sourceId
        );
    }
    
    /**
     * کاهش موجودی کارت هدیه
     *
     * @param float $amount مبلغ کاهش
     * @param string $type نوع تراکنش
     * @param string|null $description توضیحات
     * @param string|null $sourceType نوع منبع
     * @param int|null $sourceId شناسه منبع
     * @param int|null $orderId شناسه سفارش
     * @return WalletTransaction|false
     */
    public function withdrawGift(float $amount, string $type, ?string $description = null, ?string $sourceType = null, ?int $sourceId = null, ?int $orderId = null)
    {
        // بررسی کافی بودن موجودی کارت هدیه
        if ($this->balance < $amount) {
            return false;
        }
        
        $this->balance -= $amount;
        $this->save();
        
        return WalletTransaction::createTransaction(
            $this->wallet_id,
            $type,
            -$amount,
            $description,
            $sourceType,
            $sourceId,
            $orderId
        );
    }
    
    /**
     * ایجاد یا بازیابی کیف پول برای کاربر
     *
     * @param int $userId شناسه کاربر
     * @return self
     */
    public static function findOrCreateForUser(int $userId): self
    {
        $wallet = self::where('user_id', $userId)->first();
        
        if (!$wallet) {
            $wallet = self::create([
                'user_id' => $userId,
                'balance' => 0,
                'total_deposit' => 0,
                'total_withdrawal' => 0,
                'total_spent' => 0,
                'is_active' => true
            ]);
        }
        
        return $wallet;
    }
}
