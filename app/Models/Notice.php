<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notice extends Model
{
    use HasFactory;
    
    /**
     * نوع اطلاعیه: اطلاعیه
     */
    const TYPE_ANNOUNCEMENT = 'announcement';
    
    /**
     * نوع اطلاعیه: قانون
     */
    const TYPE_POLICY = 'policy';
    
    /**
     * وضعیت اطلاعیه: پیش‌نویس
     */
    const STATUS_DRAFT = 'draft';
    
    /**
     * وضعیت اطلاعیه: منتشر شده
     */
    const STATUS_PUBLISHED = 'published';
    
    /**
     * وضعیت اطلاعیه: آرشیو شده
     */
    const STATUS_ARCHIVED = 'archived';
    
    /**
     * هدف اطلاعیه: همه کاربران
     */
    const TARGET_ALL = 'all';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'title',
        'body',
        'target',
        'status',
        'publish_at',
        'version',
        'created_by',
        'updated_by',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target' => 'json',
        'publish_at' => 'datetime',
        'version' => 'integer',
    ];
    
    /**
     * رابطه با کاربر ایجاد کننده
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * رابطه با کاربر ویرایش کننده
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    /**
     * رابطه با بازدیدهای اطلاعیه
     */
    public function views(): HasMany
    {
        return $this->hasMany(NoticeView::class);
    }
    
    /**
     * محدود کردن کوئری به اطلاعیه‌های از نوع خاص
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * محدود کردن کوئری به اطلاعیه‌های منتشر شده
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('publish_at')
                  ->orWhere('publish_at', '<=', now());
            });
    }
    
    /**
     * محدود کردن کوئری به اطلاعیه‌های پیش‌نویس
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }
    
    /**
     * محدود کردن کوئری به اطلاعیه‌های آرشیو شده
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }
    
    /**
     * محدود کردن کوئری به اطلاعیه‌های قابل نمایش برای یک کاربر
     */
    public function scopeVisibleToUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // اطلاعیه‌هایی که برای همه کاربران قابل نمایش هستند
            $q->whereJsonContains('target', self::TARGET_ALL)
              // یا اطلاعیه‌هایی که به صورت اختصاصی برای این کاربر قابل نمایش هستند
              ->orWhereJsonContains('target', (string) $userId);
        });
    }
    
    /**
     * بررسی اینکه آیا اطلاعیه قابل نمایش است
     */
    public function isVisible(): bool
    {
        // فقط اطلاعیه‌های منتشر شده قابل نمایش هستند
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }
        
        // اگر زمان انتشار تنظیم شده، آیا زمان آن فرا رسیده است؟
        if ($this->publish_at !== null && $this->publish_at->isFuture()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * بررسی اینکه آیا اطلاعیه برای کاربر خاصی قابل نمایش است
     */
    public function isVisibleToUser(int $userId): bool
    {
        // ابتدا بررسی کنیم که آیا کلا قابل نمایش است
        if (!$this->isVisible()) {
            return false;
        }
        
        // بررسی اینکه آیا برای همه کاربران قابل نمایش است
        if (in_array(self::TARGET_ALL, $this->target)) {
            return true;
        }
        
        // بررسی اینکه آیا به صورت اختصاصی برای این کاربر قابل نمایش است
        return in_array((string) $userId, $this->target);
    }
    
    /**
     * بررسی اینکه آیا اطلاعیه توسط این کاربر دیده شده است
     */
    public function isViewedBy(int $userId): bool
    {
        return $this->views()->where('user_id', $userId)->exists();
    }
}
