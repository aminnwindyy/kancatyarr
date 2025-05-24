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
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id')->comment('شناسه منحصر به فرد محصول');
            $table->foreignId('category_id')->constrained('categories', 'category_id')->onDelete('cascade')->comment('شناسه دسته‌بندی');
            $table->foreignId('seller_id')->constrained('sellers', 'seller_id')->onDelete('cascade')->comment('شناسه فروشنده');
            $table->string('name')->comment('نام محصول');
            $table->text('description')->nullable()->comment('توضیحات محصول');
            $table->decimal('price', 10, 2)->comment('قیمت محصول');
            $table->string('image_url')->nullable()->comment('آدرس تصویر محصول');
            $table->integer('stock')->nullable()->comment('موجودی انبار');
            $table->string('approval_status')->default('pending')->comment('وضعیت تایید (در انتظار، تایید شده، رد شده)');
            $table->text('approval_reason')->nullable()->comment('دلیل رد یا تایید');
            $table->timestamps();

            // ایندکس‌ها برای بهبود عملکرد
            $table->index(['seller_id', 'approval_status'])->comment('ایندکس ترکیبی فروشنده و وضعیت');
            $table->index(['category_id', 'approval_status'])->comment('ایندکس ترکیبی دسته‌بندی و وضعیت');
            $table->index('price')->comment('ایندکس قیمت برای مرتب‌سازی');
            $table->index('name')->comment('ایندکس نام برای جستجو');
        });

        // اضافه کردن محدودیت‌های enum
        DB::statement("ALTER TABLE products ADD CONSTRAINT approval_status_check CHECK (approval_status IN ('pending', 'approved', 'rejected'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
