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
        Schema::create('bank_cards', function (Blueprint $table) {
            $table->id('card_id')->comment('شناسه منحصر به فرد کارت بانکی');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade')->comment('شناسه کاربر مالک کارت');
            $table->string('card_number', 16)->comment('شماره کارت');
            $table->string('sheba_number', 26)->nullable()->comment('شماره شبا');
            $table->string('bank_name')->comment('نام بانک');
            $table->date('expiry_date')->comment('تاریخ انقضا');
            $table->string('cvv', 4)->comment('کد CVV');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال بودن کارت');
            $table->timestamps();

            // ایندکس‌ها برای جستجوی سریع‌تر
            $table->index('user_id')->comment('ایندکس کاربر');
            $table->index('card_number')->comment('ایندکس شماره کارت');
            $table->index('sheba_number')->comment('ایندکس شماره شبا');
            $table->unique(['user_id', 'card_number'])->comment('ترکیب منحصر به فرد کاربر و شماره کارت');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_cards');
    }
};
