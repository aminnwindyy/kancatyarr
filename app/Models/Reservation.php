<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_provider_id',
        'product_id',
        'reservation_date',
        'reservation_time',
        'number_of_people',
        'status',
        'description',
        'rejection_reason',
        'price',
        'is_paid',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'price' => 'float',
        'number_of_people' => 'integer',
        'is_paid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // رابطه با کاربر
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

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

    // اسکوپ برای رزروهای امروز
    public function scopeToday($query)
    {
        return $query->whereDate('reservation_date', now()->toDateString());
    }

    // اسکوپ برای رزروهای آینده
    public function scopeUpcoming($query)
    {
        return $query->whereDate('reservation_date', '>=', now()->toDateString())
            ->orderBy('reservation_date', 'asc')
            ->orderBy('reservation_time', 'asc');
    }

    // اسکوپ برای رزروهای گذشته
    public function scopePast($query)
    {
        return $query->whereDate('reservation_date', '<', now()->toDateString())
            ->orderBy('reservation_date', 'desc')
            ->orderBy('reservation_time', 'desc');
    }
}
