<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ad_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service',
        'placement',
        'position_id',
        'is_active',
        'order',
        'config',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'config' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Constants for ad services
     */
    public const SERVICE_YEKTANET = 'yektanet';
    public const SERVICE_TAPSELL = 'tapsell';

    /**
     * Get allowed ad services.
     *
     * @return array
     */
    public static function getAllowedServices(): array
    {
        return [
            self::SERVICE_YEKTANET,
            self::SERVICE_TAPSELL,
        ];
    }

    /**
     * Get the user that created this setting.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope a query to only include active settings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by placement.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $placement
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPlacement($query, $placement)
    {
        return $query->where('placement', $placement);
    }

    /**
     * Scope a query to filter by service.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $service
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByService($query, $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope a query to order by display order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Log changes to this setting
     *
     * @param  array  $oldValues
     * @param  array  $newValues
     * @param  int  $updatedBy
     * @return void
     */
    public function logChanges(array $oldValues, array $newValues, int $updatedBy): void
    {
        \DB::table('ad_settings_logs')->insert([
            'ad_setting_id' => $this->id,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'updated_by' => $updatedBy,
            'created_at' => now(),
        ]);
    }
} 