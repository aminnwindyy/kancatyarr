<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_providers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'national_code',
        'business_license',
        'category',
        'status',
        'address',
        'description',
        'website',
        'rating',
        'admin_id',
        'last_activity_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'rating' => 'float',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Get the user that owns the service provider.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who last updated the service provider's status.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the documents for the service provider.
     */
    public function documents()
    {
        return $this->hasMany(ServiceProviderDocument::class);
    }

    /**
     * Get the status history for the service provider.
     */
    public function statusHistories()
    {
        return $this->hasMany(ServiceProviderStatusHistory::class);
    }

    /**
     * Get the activities for the service provider.
     */
    public function activities()
    {
        return $this->hasMany(ServiceProviderActivity::class);
    }

    /**
     * Get the ratings for the service provider.
     */
    public function ratings()
    {
        return $this->hasMany(ServiceProviderRating::class);
    }

    /**
     * Get the orders for the service provider.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    /**
     * Update the rating of the service provider based on user ratings.
     *
     * @return float
     */
    public function updateRating()
    {
        $averageRating = $this->ratings()->avg('rating') ?? 0;
        $this->update(['rating' => $averageRating]);
        return $averageRating;
    }

    /**
     * Check if the service provider is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the service provider is commercial.
     *
     * @return bool
     */
    public function isCommercial(): bool
    {
        return $this->category === 'commercial';
    }

    /**
     * Check if the service provider is connectyar.
     *
     * @return bool
     */
    public function isConnectyar(): bool
    {
        return $this->category === 'connectyar';
    }

    /**
     * Get all service providers created today.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedToday($query)
    {
        return $query->whereDate('created_at', now()->today());
    }

    /**
     * Filter service providers by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter service providers by category.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Search service providers by name or email.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('email', 'LIKE', "%{$searchTerm}%")
              ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
              ->orWhere('national_code', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Update the last activity timestamp.
     *
     * @return void
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Scope a query to only include service providers of a given category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get service providers with commercial category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommercial($query)
    {
        return $query->where('category', 'commercial');
    }

    /**
     * Get service providers with connectyar category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConnectyar($query)
    {
        return $query->where('category', 'connectyar');
    }

    /**
     * Scope a query to include service providers with pending status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to include service providers with approved status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to include service providers with rejected status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Get activity chart data for the service provider.
     *
     * @param  int  $months
     * @return array
     */
    public function getActivityChartData($months = 3)
    {
        $data = [];
        $labels = [];
        
        // تاریخ‌های شمسی برای ماه‌های اخیر
        $persianMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthIndex = $date->format('n') - 1; // شماره ماه (0-11)
            
            $labels[] = $persianMonths[$monthIndex];
            
            // تعداد سفارشات تکمیل شده در این ماه
            $completed = $this->orders()
                ->where('status', 'completed')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $data[] = $completed;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Scope a query to only include service providers registered today.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRegisteredToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Get the products for the service provider.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
 