<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'total_price',
        'total_items',
        'discount_amount',
        'final_price',
        'discount_code',
    ];

    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the cart.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate cart totals based on items
     */
    public function calculateTotals()
    {
        $totalPrice = 0;
        $totalItems = 0;

        foreach ($this->items as $item) {
            $totalPrice += $item->price * $item->quantity;
            $totalItems += $item->quantity;
        }

        $this->total_price = $totalPrice;
        $this->total_items = $totalItems;
        
        // اگر کد تخفیف وجود داشته باشد، تخفیف را اعمال می‌کنیم
        if ($this->discount_code) {
            $this->final_price = $totalPrice - $this->discount_amount;
        } else {
            $this->final_price = $totalPrice;
            $this->discount_amount = 0;
        }
        
        $this->save();
        
        return $this;
    }
} 