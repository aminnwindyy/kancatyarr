<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'service_provider_id',
        'status',
        'description',
        'rejection_reason',
        'price',
        'quantity',
        'is_paid',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'is_paid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // رابطه با کاربر
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // رابطه با محصول
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // رابطه با سرویس‌دهنده
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id', 'id');
    }

    // اسکوپ فیلتر بر اساس وضعیت
    public function scopeOfStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    // اسکوپ برای خریدهای امروز
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    // اسکوپ برای خریدهای هفته جاری
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek()->toDateTimeString(),
            now()->endOfWeek()->toDateTimeString()
        ]);
    }

    // اسکوپ برای خریدهای ماه جاری
    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month);
    }
} 