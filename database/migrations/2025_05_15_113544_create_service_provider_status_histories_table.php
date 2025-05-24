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
        Schema::create('service_provider_status_histories', function (Blueprint $table) {
            $table->id()->comment('شناسه منحصر به فرد');
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade')->comment('شناسه خدمات‌دهنده');
            $table->foreignId('admin_id')->nullable()->constrained('users', 'user_id')->nullOnDelete()->comment('شناسه ادمین تغییردهنده وضعیت');
            $table->string('previous_status')->comment('وضعیت قبلی');
            $table->string('new_status')->comment('وضعیت جدید');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->timestamps();
            
            // ایندکس برای جستجوی بهینه
            $table->index(['service_provider_id', 'new_status']);
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_status_histories');
    }
};
