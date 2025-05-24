<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_id',
        'product_id',
        'discount_percentage',
        'start_date',
        'end_date',
        'description',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'discount_percentage' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // رابطه با سرویس‌دهنده
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id', 'id');
    }

    // رابطه با محصول
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // اسکوپ فیلتر بر اساس وضعیت
    public function scopeOfStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    // اسکوپ برای درخواست‌های امروز
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    // اسکوپ برای درخواست‌های هفته جاری
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek()->toDateTimeString(),
            now()->endOfWeek()->toDateTimeString()
        ]);
    }

    // اسکوپ برای درخواست‌های ماه جاری
    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month);
    }
}