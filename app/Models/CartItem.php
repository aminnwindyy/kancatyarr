<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'total_price',
        'options',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Get the cart that owns the cart item.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product that owns the cart item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate total price for this item
     */
    public function calculateTotalPrice()
    {
        $this->total_price = $this->price * $this->quantity;
        $this->save();
        
        // بعد از محاسبه قیمت آیتم، کل سبد خرید را بروزرسانی می‌کنیم
        $this->cart->calculateTotals();
        
        return $this;
    }
} 