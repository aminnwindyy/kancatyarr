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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->unsignedBigInteger('service_provider_id')->nullable();
            $table->foreign('service_provider_id')->references('id')->on('service_providers')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('product_id')->on('products')->nullable();
            $table->enum('order_type', ['public', 'private'])->default('public');
            $table->enum('delivery_type', ['national', 'local'])->default('national');
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // ایندکس‌ها
            $table->index('user_id');
            $table->index('service_provider_id');
            $table->index('status');
            $table->index('order_type');
            $table->index('delivery_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
