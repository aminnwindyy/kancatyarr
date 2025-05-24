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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('phone_number', 20)->nullable()->comment('شماره موبایل برای زمانی که کاربر هنوز ثبت‌نام نکرده');
            $table->string('code', 4)->comment('کد تأیید ۴ رقمی');
            $table->enum('type', ['email', 'sms'])->default('sms')->comment('نوع ارسال کد');
            $table->timestamp('expires_at')->comment('زمان انقضای کد');
            $table->boolean('is_used')->default(false)->comment('آیا کد استفاده شده است');
            $table->timestamps();

            // ایجاد ایندکس برای جستجوی سریع‌تر
            $table->index(['user_id', 'type', 'code']);
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
