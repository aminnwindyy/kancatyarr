<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    
    /**
     * جدول مرتبط با این مدل
     *
     * @var string
     */
    protected $table = 'chat_messages';
    
    /**
     * کلید اصلی جدول
     *
     * @var string
     */
    protected $primaryKey = 'message_id';
    
    /**
     * ستون‌هایی که قابل تغییر هستند
     *
     * @var array
     */
    protected $fillable = [
        'chat_id',
        'sender_id',
        'sender_type',
        'content',
        'is_read',
    ];
    
    /**
     * ستون‌هایی که به صورت boolean تعریف می‌شوند
     *
     * @var array
     */
    protected $casts = [
        'is_read' => 'boolean',
    ];
    
    /**
     * رابطه با جدول گفتگوها
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id', 'chat_id');
    }
    
    /**
     * رابطه با جدول کاربران برای پیام‌هایی که توسط کاربر ارسال شده‌اند
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id')
            ->where('sender_type', 'user');
    }
    
    /**
     * رابطه با جدول ادمین‌ها برای پیام‌هایی که توسط پشتیبان ارسال شده‌اند
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id')
            ->where('sender_type', 'support');
    }
}
