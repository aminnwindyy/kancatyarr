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
        if (!Schema::hasTable('product_requests')) {
            Schema::create('product_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_provider_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('category_id');
                $table->decimal('price', 10, 2)->default(0);
                $table->string('image_path')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');
                $table->foreign('category_id')->references('category_id')->on('categories')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
}; 