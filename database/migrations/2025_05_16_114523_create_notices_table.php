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
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['announcement', 'policy'])->comment('نوع: اطلاعیه یا قانون');
            $table->string('title')->comment('عنوان');
            $table->text('body')->comment('متن');
            $table->json('target')->comment('هدف اطلاعیه: آرایه‌ای از شناسه کاربران/گروه‌ها یا ["all"]');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->comment('وضعیت: پیش‌نویس، منتشرشده، آرشیو‌شده');
            $table->timestamp('publish_at')->nullable()->comment('زمان انتشار برای زمان‌بندی');
            $table->integer('version')->default(1)->comment('شماره نسخه (برای قوانین)');
            $table->unsignedBigInteger('created_by')->comment('ایجاد کننده');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('آخرین ویرایش کننده');
            $table->timestamps();
            
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            
            // ایندکس‌ها برای جستجوهای رایج و بهبود عملکرد
            $table->index(['type', 'status']);
            $table->index('publish_at');
        });
        
        // جدول برای نگهداری تاریخچه نمایش اطلاعیه‌ها توسط کاربران
        Schema::create('notice_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notice_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('viewed_at');
            
            $table->foreign('notice_id')->references('id')->on('notices')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            
            $table->unique(['notice_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_views');
        Schema::dropIfExists('notices');
    }
};
