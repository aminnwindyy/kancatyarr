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
        if (!Schema::hasTable('discount_requests')) {
            Schema::create('discount_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_provider_id');
                $table->unsignedBigInteger('product_id');
                $table->float('discount_percentage')->default(0);
                $table->date('start_date');
                $table->date('end_date');
                $table->text('description')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');
                $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_requests');
    }
}; 