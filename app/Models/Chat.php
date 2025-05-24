<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    
    /**
     * جدول مرتبط با این مدل
     *
     * @var string
     */
    protected $table = 'chats';
    
    /**
     * کلید اصلی جدول
     *
     * @var string
     */
    protected $primaryKey = 'chat_id';
    
    /**
     * ستون‌هایی که قابل تغییر هستند
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'status',
    ];
    
    /**
     * رابطه با جدول کاربران
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    
    /**
     * رابطه با جدول پیام‌ها
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id', 'chat_id');
    }
    
    /**
     * دریافت آخرین پیام گفتگو
     */
    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'chat_id', 'chat_id')
            ->latest('created_at');
    }
    
    /**
     * تعداد پیام‌های خوانده نشده پشتیبانی
     */
    public function unreadCount()
    {
        return $this->messages()
            ->where('sender_type', 'support')
            ->where('is_read', false)
            ->count();
    }
}
