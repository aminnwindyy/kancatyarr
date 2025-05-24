<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'transaction_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'order_id',
        'gift_card_id',
        'bank_card_id',
        'type',
        'amount',
        'description',
        'status',
        'reference_code',
        'payment_method',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the wallet associated with the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'wallet_id');
    }

    /**
     * Get the order associated with the transaction.
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
     * Get the bank card associated with the transaction.
     */
    public function bankCard(): BelongsTo
    {
        return $this->belongsTo(BankCard::class, 'bank_card_id', 'card_id');
    }
}
