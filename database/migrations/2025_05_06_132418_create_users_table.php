<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id')->comment('شناسه منحصر به فرد کاربر');
            $table->string('first_name')->comment('نام');
            $table->string('last_name')->comment('نام خانوادگی');
            $table->string('email')->unique()->comment('ایمیل (منحصر به فرد)');
            $table->string('password')->comment('رمز عبور');
            $table->string('phone_number', 20)->unique()->comment('شماره تلفن همراه (منحصر به فرد)');
            $table->string('username')->nullable()->unique()->comment('نام کاربری (منحصر به فرد)');
            $table->integer('accepted_terms_version')->nullable()->comment('نسخه قوانین پذیرفته شده');
            $table->timestamp('registration_date')->useCurrent()->comment('تاریخ ثبت نام');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال بودن حساب');
            $table->boolean('is_admin')->default(false)->comment('آیا کاربر ادمین است');
            $table->string('city', 100)->nullable()->comment('شهر');
            $table->string('province', 100)->nullable()->comment('استان');
            $table->string('profile_image')->nullable()->comment('تصویر پروفایل');

            // فیلدهای مربوط به احراز هویت و تأیید
            $table->string('national_id', 10)->nullable()->unique()->comment('کد ملی (منحصر به فرد)');
            $table->string('sheba_number', 24)->nullable()->unique()->comment('شماره شبا (منحصر به فرد)');

            // وضعیت تأیید اطلاعات
            $table->boolean('is_first_name_verified')->default(false)->comment('آیا نام تایید شده است');
            $table->boolean('is_last_name_verified')->default(false)->comment('آیا نام خانوادگی تایید شده است');
            $table->boolean('is_phone_verified')->default(false)->comment('آیا شماره تلفن تایید شده است');
            $table->boolean('is_mobile_verified')->default(false)->comment('آیا شماره موبایل تایید شده است');
            $table->boolean('is_national_id_verified')->default(false)->comment('آیا کد ملی تایید شده است');
            $table->boolean('is_sheba_verified')->default(false)->comment('آیا شماره شبا تایید شده است');
            $table->string('login_preference')->default('phone_otp')->comment('ترجیح ورود به سیستم (password, email_otp, phone_otp)');

            $table->timestamp('email_verified_at')->nullable()->comment('تاریخ تایید ایمیل');
            $table->rememberToken()->comment('توکن به خاطرسپاری');
            $table->timestamps();

            // ایندکس‌ها برای بهبود عملکرد
            $table->index('phone_number');
            $table->index('username');
            $table->index('national_id');
            $table->index(['is_active', 'is_admin']);
            $table->index('registration_date');
            $table->index('accepted_terms_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
