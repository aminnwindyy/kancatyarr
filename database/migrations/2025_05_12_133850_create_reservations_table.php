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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->unsignedBigInteger('service_provider_id');
            $table->foreign('service_provider_id')->references('id')->on('service_providers');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('product_id')->on('products')->nullable();
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('description')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            
            // ایندکس
            $table->index('user_id');
            $table->index('service_provider_id');
            $table->index('status');
            $table->index('reservation_date');
            $table->index('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
