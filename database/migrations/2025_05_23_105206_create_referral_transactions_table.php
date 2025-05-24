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
        Schema::create('referral_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('new_user_id')->constrained('users', 'user_id')->comment('کاربر جدید (دعوت شده)');
            $table->foreignId('referrer_user_id')->constrained('users', 'user_id')->comment('کاربر دعوت کننده');
            $table->decimal('bonus_amount_per_user', 10, 2)->comment('مبلغ پاداش برای هر کاربر');
            $table->timestamp('referral_date')->useCurrent()->comment('تاریخ دعوت');
            $table->foreignId('new_user_wallet_transaction_id')->nullable()->comment('شناسه تراکنش کیف پول کاربر جدید');
            $table->foreignId('referrer_wallet_transaction_id')->nullable()->comment('شناسه تراکنش کیف پول کاربر دعوت کننده');
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete()->comment('شناسه رکورد دعوت');
            $table->timestamps();

            // ایندکس‌ها برای بهبود عملکرد
            $table->index('new_user_id');
            $table->index('referrer_user_id');
            $table->index('referral_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_transactions');
    }
};
