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
        Schema::create('order_shippings', function (Blueprint $table) {
            $table->id('shipping_id');
            $table->foreignId('order_id')->constrained('orders', 'order_id')->onDelete('cascade');
            $table->text('address');
            $table->string('tracking_code', 50)->nullable();
            $table->string('shipping_method', 50);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('status', 50)->default('pending');
            $table->timestamps();

            // Add unique constraint - one shipping entry per order
            $table->unique('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shippings');
    }
};
