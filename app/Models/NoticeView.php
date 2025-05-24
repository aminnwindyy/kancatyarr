<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeView extends Model
{
    use HasFactory;
    
    /**
     * بدون استفاده از timestamps
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'notice_id',
        'user_id',
        'viewed_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'viewed_at' => 'datetime',
    ];
    
    /**
     * رابطه با اطلاعیه
     */
    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }
    
    /**
     * رابطه با کاربر
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
