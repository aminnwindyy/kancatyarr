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
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->comment('شناسه کاربر یا NULL اگر تراکنش سیستمی باشد');
            $table->foreignId('provider_id')->nullable()->comment('شناسه خدمات‌دهنده یا NULL');
            $table->enum('type', ['withdraw_user', 'withdraw_provider', 'deposit', 'fee', 'refund', 'settlement'])
                ->comment('نوع تراکنش');
            $table->decimal('amount', 12, 0)->comment('مبلغ');
            $table->enum('status', ['pending', 'approved', 'rejected', 'settled'])
                ->default('pending')->comment('وضعیت');
            $table->string('reference_id')->nullable()->comment('شماره سفارش یا تراکنش خارجی');
            $table->json('metadata')->nullable()->comment('جزئیات بیشتر');
            $table->string('bank_account')->nullable()->comment('شماره حساب بانکی');
            $table->string('tracking_code')->nullable()->comment('کد پیگیری');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('ادمین تایید کننده');
            $table->text('reject_reason')->nullable()->comment('دلیل رد');
            $table->timestamps();
            
            // ایندکس‌ها
            $table->index(['type', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index('created_at');
            
            // کلیدهای خارجی
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('provider_id')->references('id')->on('service_providers')->onDelete('set null');
            $table->foreign('admin_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_transactions');
    }
};
