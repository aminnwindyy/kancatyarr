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
        Schema::create('ad_settings', function (Blueprint $table) {
            $table->id();
            $table->string('service')->comment('سرویس تبلیغات: yektanet یا tapsell');
            $table->string('placement')->comment('نقطه نمایش: مثلاً هدر، سایدبار، بین مطالب');
            $table->string('position_id')->comment('شناسه پوزیشن در پنل سرویس ثالث');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->integer('order')->default(0)->comment('اولویت نمایش');
            $table->json('config')->nullable()->comment('تنظیمات اضافی به صورت JSON');
            $table->unsignedBigInteger('created_by')->comment('ایجاد کننده');
            $table->timestamps();

            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['placement', 'order'], 'ad_settings_placement_order_index');
        });

        Schema::create('ad_settings_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_setting_id')->comment('شناسه تنظیم تبلیغات');
            $table->json('old_values')->nullable()->comment('مقادیر قبلی');
            $table->json('new_values')->nullable()->comment('مقادیر جدید');
            $table->unsignedBigInteger('updated_by')->comment('به‌روزرسانی کننده');
            $table->timestamp('created_at');

            $table->foreign('ad_setting_id')->references('id')->on('ad_settings')->onDelete('cascade');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_settings_logs');
        Schema::dropIfExists('ad_settings');
    }
}; 