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
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('service_provider_id');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('description')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->boolean('is_paid')->default(false);
                $table->timestamps();

                $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
                $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
                $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
}; 