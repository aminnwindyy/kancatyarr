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
        Schema::create('service_provider_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_provider_id');
            $table->unsignedTinyInteger('rating')->comment('1-5 star rating');
            $table->text('comment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable()->comment('Admin who approved/rejected the review');
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');
            $table->foreign('admin_id')->references('user_id')->on('users')->onDelete('set null');

            $table->unique(['order_id', 'user_id', 'service_provider_id'], 'unique_review');
        });

        Schema::create('service_provider_review_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('review_id');
            $table->enum('old_status', ['pending', 'approved', 'rejected']);
            $table->enum('new_status', ['pending', 'approved', 'rejected']);
            $table->unsignedBigInteger('changed_by');
            $table->timestamp('changed_at');
            $table->text('note')->nullable();
            
            $table->foreign('review_id')->references('id')->on('service_provider_reviews')->onDelete('cascade');
            $table->foreign('changed_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_review_logs');
        Schema::dropIfExists('service_provider_reviews');
    }
};
