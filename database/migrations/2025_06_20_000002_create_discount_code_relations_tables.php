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
        // جدول ارتباطی کدهای تخفیف با محصولات
        if (!Schema::hasTable('discount_code_products')) {
            Schema::create('discount_code_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_code_id')->constrained('discount_codes')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['discount_code_id', 'product_id']);
            });
        }

        // جدول ارتباطی کدهای تخفیف با پلن‌های اشتراک
        if (!Schema::hasTable('discount_code_plans')) {
            Schema::create('discount_code_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_code_id')->constrained('discount_codes')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans', 'plan_id')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['discount_code_id', 'plan_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_code_plans');
        Schema::dropIfExists('discount_code_products');
    }
}; 