<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'city',
        'province',
        'profile_image',
        'is_active',
        'national_id',
        'sheba_number',
        'is_first_name_verified',
        'is_last_name_verified',
        'is_phone_verified',
        'is_national_id_verified',
        'is_sheba_verified',
        'login_preference',
        'name',
        'mobile',
        'status',
        'referral_code',
        'username',
        'accepted_terms_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_date' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_first_name_verified' => 'boolean',
            'is_last_name_verified' => 'boolean',
            'is_phone_verified' => 'boolean',
            'is_national_id_verified' => 'boolean',
            'is_sheba_verified' => 'boolean',
            'login_preference' => 'string',
            'accepted_terms_version' => 'integer',
        ];
    }

    /**
     * Check if the user has admin role
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Custom method to check if user has a specific role
     * Note: This method extends the HasRoles trait from spatie/laravel-permission
     *
     * @param string $role
     * @return bool
     */
    public function hasLocalRole(string $role): bool
    {
        if ($role === 'admin') {
            return $this->isAdmin();
        }

        return false;
    }

    /**
     * Get the seller associated with the user.
     */
    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class, 'user_id', 'user_id');
    }

    /**
     * Get the wallet associated with the user.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id');
    }

    /**
     * Get the orders for the user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    /**
     * Get the support tickets for the user.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id', 'user_id');
    }

    /**
     * Get the tickets for the user.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id', 'user_id');
    }

    /**
     * Get the withdrawal requests for the user.
     */
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'user_id', 'user_id');
    }

    /**
     * Get the sent messages.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id', 'user_id');
    }

    /**
     * Get the received messages.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id', 'user_id');
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'user_id');
    }

    /**
     * Get the active subscription for the user, if any.
     */
    public function activeSubscription()
    {
        $now = now()->format('Y-m-d');
        return $this->subscriptions()
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->orderBy('end_date', 'desc')
            ->first();
    }

    /**
     * Get the entity's notifications.
     */
    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get login histories for the user.
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'user_id', 'user_id');
    }

    /**
     * دعوت‌های ارسالی کاربر
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sentReferrals()
    {
        return $this->hasMany(Referral::class, 'referrer_user_id');
    }

    /**
     * دعوت‌های دریافتی کاربر
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receivedReferrals()
    {
        return $this->hasMany(Referral::class, 'referred_user_id');
    }

    /**
     * Get the OTP codes for the user
     */
    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class, 'user_id', 'user_id');
    }

    /**
     * Find a user by phone number
     *
     * @param string $phoneNumber
     * @return self|null
     */
    public static function findByPhone(string $phoneNumber)
    {
        return self::where('phone_number', $phoneNumber)->first();
    }

    /**
     * Check if the user's National ID is valid according to Iran's algorithm
     *
     * @param string $nationalId
     * @return bool
     */
    public static function validateNationalId(string $nationalId): bool
    {
        // حذف خط تیره و فاصله‌ها
        $nationalId = preg_replace('/[^0-9]/', '', $nationalId);

        // بررسی طول کد ملی
        if (strlen($nationalId) != 10) {
            return false;
        }

        // بررسی رقم‌های تکراری
        if (preg_match('/^(\d)\1{9}$/', $nationalId)) {
            return false;
        }

        // محاسبه رقم کنترل
        $check = 0;
        for ($i = 0; $i < 9; $i++) {
            $check += intval($nationalId[$i]) * (10 - $i);
        }
        $remainder = $check % 11;
        $controlDigit = ($remainder < 2) ? $remainder : 11 - $remainder;

        // مقایسه رقم کنترل با رقم آخر
        return ($controlDigit == intval($nationalId[9]));
    }

    /**
     * Generate a unique username based on the given name
     *
     * @param string $baseName
     * @return string
     */
    public static function generateUniqueUsername(string $baseName): string
    {
        // تبدیل به حروف کوچک و حذف کاراکترهای غیرالفبایی و عددی
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $baseName));
        
        // اگر نام کاربری خالی شد یا کمتر از 3 حرف بود
        if (strlen($username) < 3) {
            $username = 'user' . mt_rand(100, 999);
        }

        // بررسی یکتا بودن
        $originalUsername = $username;
        $counter = 1;
        
        while (self::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate a random and unique referral code
     *
     * @return string
     */
    public function generateReferralCode(): string
    {
        // اگر کد معرف قبلاً ایجاد شده، آن را برگردان
        if (!empty($this->referral_code)) {
            return $this->referral_code;
        }

        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = 8;
        
        do {
            $referralCode = '';
            for ($i = 0; $i < $length; $i++) {
                $referralCode .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while (self::where('referral_code', $referralCode)->exists());
        
        $this->referral_code = $referralCode;
        $this->save();
        
        return $referralCode;
    }

    /**
     * Apply referral bonus when a user uses a referral code
     *
     * @param string $referrerCode
     * @return bool
     */
    public function applyReferralBonus(string $referrerCode): bool
    {
        // جلوگیری از استفاده از کد خود کاربر یا کاربری که قبلاً دعوت شده
        if (
            $this->referral_code === $referrerCode || 
            Referral::where('referee_id', $this->user_id)->exists()
        ) {
            return false;
        }
        
        // پیدا کردن کاربر دعوت کننده
        $referrer = self::where('referral_code', $referrerCode)->first();
        if (!$referrer) {
            return false;
        }
        
        // دریافت برنامه دعوت فعال
        $referralProgram = ReferralProgram::where('is_active', true)->first();
        if (!$referralProgram) {
            return false;
        }
        
        // ایجاد رکورد دعوت
        $referral = Referral::create([
            'referrer_id' => $referrer->user_id,
            'referee_id' => $this->user_id,
            'referral_code' => $referrerCode,
            'program_id' => $referralProgram->id,
            'referrer_reward' => $referralProgram->referrer_reward,
            'referee_reward' => $referralProgram->referee_reward,
            'status' => 'completed',
            'expires_at' => now()->addDays($referralProgram->expiry_days),
        ]);
        
        // ایجاد یا بازیابی کیف پول‌ها
        $referrerWallet = Wallet::findOrCreateForUser($referrer->user_id);
        $newUserWallet = Wallet::findOrCreateForUser($this->user_id);
        
        // افزودن پاداش به کیف پول دعوت کننده
        $referrerTransaction = $referrerWallet->depositGift(
            $referralProgram->referrer_reward,
            WalletTransaction::TYPE_GIFT_CARD_REFERRAL,
            'پاداش دعوت کاربر جدید: ' . $this->phone_number,
            WalletTransaction::SOURCE_REFERRAL,
            $referral->id
        );
        
        // افزودن پاداش به کیف پول کاربر جدید
        $newUserTransaction = $newUserWallet->depositGift(
            $referralProgram->referee_reward,
            WalletTransaction::TYPE_GIFT_CARD_REFERRAL,
            'پاداش ثبت‌نام با کد معرف: ' . $referrerCode,
            WalletTransaction::SOURCE_REFERRAL,
            $referral->id
        );
        
        // به‌روزرسانی رکورد دعوت با شناسه تراکنش‌ها
        $referral->update([
            'referrer_wallet_transaction_id' => $referrerTransaction->transaction_id,
            'new_user_wallet_transaction_id' => $newUserTransaction->transaction_id,
            'referrer_reward_paid' => true,
            'referee_reward_paid' => true,
        ]);
        
        // ایجاد تراکنش دعوت
        ReferralTransaction::create([
            'new_user_id' => $this->user_id,
            'referrer_user_id' => $referrer->user_id,
            'bonus_amount_per_user' => $referralProgram->referrer_reward,
            'referral_date' => now(),
            'new_user_wallet_transaction_id' => $newUserTransaction->transaction_id,
            'referrer_wallet_transaction_id' => $referrerTransaction->transaction_id,
            'referral_id' => $referral->id,
        ]);
        
        return true;
    }
}

