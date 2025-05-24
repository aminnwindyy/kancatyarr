<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProviderStatusHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_provider_id',
        'previous_status',
        'new_status',
        'changed_by',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service provider that owns this history record.
     */
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    /**
     * Get the user who changed the status.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope a query to get changes to a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToStatus($query, string $status)
    {
        return $query->where('new_status', $status);
    }

    /**
     * Scope a query to get changes from a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromStatus($query, string $status)
    {
        return $query->where('previous_status', $status);
    }

    /**
     * Scope a query to get changes made by a specific admin.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $adminId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('changed_by', $adminId);
    }
}
