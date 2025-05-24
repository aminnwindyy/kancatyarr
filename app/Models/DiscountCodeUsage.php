<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCodeUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discount_code_id',
        'user_id',
        'order_id',
        'plan_id',
        'product_id',
        'amount',
        'discount_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'discount_amount' => 'float',
    ];

    /**
     * رابطه با جدول کد تخفیف
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * رابطه با جدول کاربران
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * رابطه با جدول پلن‌های اشتراک
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * رابطه با جدول محصولات
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 