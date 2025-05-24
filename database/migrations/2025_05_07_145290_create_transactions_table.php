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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id')->comment('شناسه منحصر به فرد تراکنش');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade')->comment('شناسه کاربر');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets', 'wallet_id')->nullOnDelete()->comment('شناسه کیف پول (اختیاری)');
            $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id')->nullOnDelete()->comment('شناسه سفارش (اختیاری)');
            $table->foreignId('gift_card_id')->nullable()->constrained('gift_cards', 'gift_card_id')->nullOnDelete()->comment('شناسه کارت هدیه (اختیاری)');
            $table->foreignId('bank_card_id')->nullable()->constrained('bank_cards', 'card_id')->nullOnDelete()->comment('شناسه کارت بانکی (اختیاری)');
            $table->enum('type', ['deposit', 'withdrawal', 'gift', 'payment', 'refund', 'transfer'])->comment('نوع تراکنش (واریز، برداشت، هدیه، پرداخت، بازپرداخت، انتقال)');
            $table->decimal('amount', 12, 2)->comment('مبلغ تراکنش');
            $table->text('description')->nullable()->comment('توضیحات تراکنش');
            $table->string('status', 50)->comment('وضعیت تراکنش');
            $table->string('payment_method', 50)->comment('روش پرداخت');
            $table->string('reference_code', 100)->nullable()->comment('کد مرجع تراکنش');
            $table->timestamps();

            // ایندکس‌ها برای جستجوی سریع‌تر
            $table->index(['user_id', 'type', 'status'])->comment('ایندکس ترکیبی کاربر، نوع و وضعیت');
            $table->index(['wallet_id'])->comment('ایندکس کیف پول');
            $table->index(['order_id', 'status'])->comment('ایندکس ترکیبی سفارش و وضعیت');
            $table->index(['gift_card_id'])->comment('ایندکس کارت هدیه');
            $table->index(['bank_card_id'])->comment('ایندکس کارت بانکی');
            $table->index('created_at')->comment('ایندکس تاریخ ایجاد');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
