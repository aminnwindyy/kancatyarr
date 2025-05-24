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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id('wallet_id')->comment('شناسه منحصر به فرد کیف پول');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade')->comment('شناسه کاربر مالک کیف پول');
            $table->decimal('balance', 12, 2)->default(0)->comment('موجودی اصلی کیف پول');
            $table->decimal('gift_balance', 12, 2)->default(0)->comment('موجودی هدیه کیف پول');
            $table->timestamps();

            // ایندکس‌ها برای جستجوی سریع‌تر
            $table->index('user_id')->comment('ایندکس کاربر');
            $table->index('balance')->comment('ایندکس موجودی برای گزارش‌گیری');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
