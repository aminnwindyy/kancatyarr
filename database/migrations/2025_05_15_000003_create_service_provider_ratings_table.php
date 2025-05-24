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
        Schema::create('service_provider_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade')->comment('شناسه خدمات‌دهنده');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade')->comment('شناسه کاربر');
            $table->decimal('rating', 2, 1)->comment('امتیاز (0 تا 5)');
            $table->text('comment')->nullable()->comment('نظر کاربر');
            $table->timestamps();
            
            // هر کاربر فقط یک بار می‌تواند به یک خدمات‌دهنده امتیاز دهد
            $table->unique(['service_provider_id', 'user_id']);
            
            // ایندکس برای جستجوی بهینه
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_ratings');
    }
};