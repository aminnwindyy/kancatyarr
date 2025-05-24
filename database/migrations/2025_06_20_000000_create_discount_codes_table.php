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
        if (!Schema::hasTable('discount_codes')) {
            Schema::create('discount_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->enum('type', ['percentage', 'fixed_amount'])->default('percentage');
                $table->decimal('value', 10, 2); // مقدار تخفیف (درصد یا مبلغ ثابت)
                $table->integer('max_uses')->nullable(); // حداکثر تعداد استفاده (null = نامحدود)
                $table->integer('max_uses_per_user')->nullable(); // حداکثر تعداد استفاده برای هر کاربر
                $table->integer('used_count')->default(0); // تعداد دفعات استفاده شده
                $table->decimal('min_order_amount', 10, 2)->default(0); // حداقل مبلغ سفارش برای اعمال تخفیف
                $table->boolean('is_active')->default(true); // وضعیت فعال یا غیرفعال
                $table->timestamp('expires_at')->nullable(); // تاریخ انقضا (null = بدون انقضا)
                $table->text('description')->nullable(); // توضیحات مربوط به کد تخفیف
                $table->foreignId('created_by')->nullable()->constrained('users', 'user_id')->nullOnDelete(); // ایجاد کننده کد تخفیف
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
}; 