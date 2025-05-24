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
        Schema::create('balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('تاریخ اسنپشات');
            $table->enum('period_type', ['daily', 'monthly', 'yearly'])->comment('نوع دوره');
            $table->decimal('total_balance', 12, 0)->default(0)->comment('موجودی کل');
            $table->decimal('total_revenue', 12, 0)->default(0)->comment('درآمد کل');
            $table->decimal('total_withdrawals', 12, 0)->default(0)->comment('مجموع برداشت‌ها');
            $table->decimal('total_pending_withdrawals', 12, 0)->default(0)->comment('مجموع درخواست‌های برداشت در انتظار');
            $table->json('additional_data')->nullable()->comment('داده‌های اضافی');
            $table->timestamps();
            
            // ایندکس‌ها
            $table->unique(['date', 'period_type']);
            $table->index('period_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_snapshots');
    }
};
