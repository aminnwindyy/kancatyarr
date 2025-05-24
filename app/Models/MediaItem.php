<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MediaItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'title',
        'image_path',
        'link',
        'order',
        'is_active',
        'position',
        'provider',
        'script_code',
        'start_date',
        'end_date',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Constants for media types
     */
    public const TYPE_BANNER = 'banner';
    public const TYPE_SLIDER = 'slider';

    /**
     * Constants for banner positions
     */
    public const POSITION_TOP = 'top';
    public const POSITION_BOTTOM = 'bottom';
    public const POSITION_SIDEBAR = 'sidebar';
    public const POSITION_MAIN_SLIDER = 'main_slider';
    public const POSITION_POPUP = 'popup';

    /**
     * Constants for providers
     */
    public const PROVIDER_CUSTOM = 'custom';
    public const PROVIDER_YEKTANET = 'yektanet';
    public const PROVIDER_TAPSELL = 'tapsell';
    public const PROVIDER_OTHER = 'other';

    /**
     * Get allowed media types.
     *
     * @return array
     */
    public static function getAllowedTypes(): array
    {
        return [
            self::TYPE_BANNER,
            self::TYPE_SLIDER,
        ];
    }

    /**
     * Get allowed positions.
     *
     * @return array
     */
    public static function getAllowedPositions(): array
    {
        return [
            self::POSITION_TOP,
            self::POSITION_BOTTOM,
            self::POSITION_SIDEBAR,
            self::POSITION_MAIN_SLIDER,
            self::POSITION_POPUP,
        ];
    }

    /**
     * Get allowed providers.
     *
     * @return array
     */
    public static function getAllowedProviders(): array
    {
        return [
            self::PROVIDER_CUSTOM,
            self::PROVIDER_YEKTANET,
            self::PROVIDER_TAPSELL,
            self::PROVIDER_OTHER,
        ];
    }

    /**
     * Get the user that created this media item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope a query to only include active media items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by position.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $position
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope a query to filter items that are currently active based on dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentlyActive($query)
    {
        $now = Carbon::now();
        
        return $query->where('is_active', true)
            ->where(function($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            });
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
     * Get the full URL to the image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (strpos($this->image_path, 'http') === 0) {
            return $this->image_path;
        }
        
        return url('storage/' . $this->image_path);
    }
}
