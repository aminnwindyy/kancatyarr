<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_id',
        'name',
        'description',
        'category_id',
        'price',
        'image_path',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // رابطه با سرویس‌دهنده
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id', 'id');
    }

    // رابطه با دسته‌بندی
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
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