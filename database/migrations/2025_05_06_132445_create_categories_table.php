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
        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id')->comment('شناسه منحصر به فرد دسته‌بندی');
            $table->string('name')->unique()->comment('نام دسته‌بندی (منحصر به فرد)');
            $table->text('description')->nullable()->comment('توضیحات دسته‌بندی');
            $table->foreignId('parent_category_id')->nullable()->references('category_id')->on('categories')->onDelete('set null')->comment('شناسه دسته‌بندی والد');
            $table->string('icon')->nullable()->comment('آیکون دسته‌بندی');
            $table->timestamps();
            
            // ایندکس‌ها برای بهبود عملکرد
            $table->index('parent_category_id')->comment('ایندکس دسته‌بندی والد برای ساختار درختی');
            $table->index('name')->comment('ایندکس نام برای جستجو');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
