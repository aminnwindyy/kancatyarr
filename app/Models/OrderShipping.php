<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderShipping extends Model
{
    use HasFactory;

    protected $primaryKey = 'shipping_id';

    protected $fillable = [
        'order_id',
        'address',
        'tracking_code',
        'shipping_method',
        'shipping_cost',
        'status',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
