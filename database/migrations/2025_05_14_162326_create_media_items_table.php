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
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['banner', 'slider'])->comment('نوع: بنر یا اسلایدر');
            $table->string('title')->comment('عنوان');
            $table->string('image_path')->comment('مسیر ذخیره تصویر');
            $table->string('link')->nullable()->comment('لینک مقصد (اختیاری)');
            $table->integer('order')->default(0)->comment('اولویت نمایش');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->enum('position', ['top', 'bottom', 'sidebar', 'main_slider', 'popup'])->default('top')->comment('موقعیت نمایش بنر');
            $table->enum('provider', ['custom', 'yektanet', 'tapsell', 'other'])->default('custom')->comment('سرویس‌دهنده تبلیغات');
            $table->text('script_code')->nullable()->comment('کد اسکریپت برای تبلیغات خارجی');
            $table->timestamp('start_date')->nullable()->comment('تاریخ شروع نمایش');
            $table->timestamp('end_date')->nullable()->comment('تاریخ پایان نمایش');
            $table->unsignedBigInteger('created_by')->comment('ایجاد کننده');
            $table->timestamps();

            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['type', 'order'], 'media_items_type_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
