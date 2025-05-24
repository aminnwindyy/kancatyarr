<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'phone_number',
        'code',
        'type',
        'expires_at',
        'is_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Generate a random OTP code
     *
     * @param int $length
     * @return string
     */
    public static function generateCode(int $length = 4): string
    {
        // تولید کد تصادفی با طول مشخص شده
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string) mt_rand($min, $max);
    }

    /**
     * Create a new OTP code for the given phone number
     *
     * @param string $phoneNumber
     * @param int $expiresInMinutes
     * @return self
     */
    public static function createForPhone(string $phoneNumber, int $expiresInMinutes = 2): self
    {
        // حذف کدهای قبلی برای این شماره موبایل
        self::where('phone_number', $phoneNumber)->delete();

        // ایجاد کد جدید
        $code = self::generateCode();
        
        // ذخیره در دیتابیس
        return self::create([
            'phone_number' => $phoneNumber,
            'code' => $code,
            'type' => 'sms',
            'expires_at' => Carbon::now()->addMinutes($expiresInMinutes),
        ]);
    }

    /**
     * Check if the given code is valid for the phone number
     *
     * @param string $phoneNumber
     * @param string $code
     * @return bool
     */
    public static function validateCode(string $phoneNumber, string $code): bool
    {
        $otpRecord = self::where('phone_number', $phoneNumber)
            ->where('code', $code)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_used', false)
            ->first();

        if ($otpRecord) {
            // نشانه گذاری کد به عنوان استفاده شده
            $otpRecord->is_used = true;
            $otpRecord->save();
            
            return true;
        }

        return false;
    }

    /**
     * Check if the phone number has reached the rate limit
     *
     * @param string $phoneNumber
     * @param int $maxAttempts
     * @param int $minutes
     * @return bool
     */
    public static function hasReachedRateLimit(string $phoneNumber, int $maxAttempts = 3, int $minutes = 5): bool
    {
        $count = self::where('phone_number', $phoneNumber)
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->count();

        return $count >= $maxAttempts;
    }
}
