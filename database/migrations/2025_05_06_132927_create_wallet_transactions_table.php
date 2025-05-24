<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تلاش برای ایجاد جدول wallet_transactions فقط در صورتی که جدول wallets وجود داشته باشد
        try {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id('transaction_id');
                $table->foreignId('wallet_id')->constrained('wallets', 'wallet_id')->onDelete('cascade');
                $table->string('transaction_type');
                $table->decimal('amount', 10, 2);
                $table->timestamp('transaction_date')->useCurrent();
                $table->text('description')->nullable();
                $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id')->onDelete('set null');
                $table->timestamps();
            });

            // Add enum check constraint
            DB::statement("ALTER TABLE wallet_transactions ADD CONSTRAINT transaction_type_check CHECK (transaction_type IN ('deposit', 'withdrawal', 'order_payment', 'order_refund', 'gift_card'))");
        } catch (\Exception $e) {
            // اگر جدول wallets وجود نداشت، خطا را نادیده بگیر
            // وقتی مهاجرت wallets اجرا شود، می‌توانیم این مهاجرت را دوباره اجرا کنیم
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
