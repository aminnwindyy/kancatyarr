<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProviderRating extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_provider_id',
        'user_id',
        'rating',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service provider that owns this rating.
     */
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /**
     * Get the user who gave this rating.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to get ratings greater than or equal to a given value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMinRating($query, float $value)
    {
        return $query->where('rating', '>=', $value);
    }

    /**
     * Scope a query to get ratings from a specific period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
