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
        Schema::create('service_provider_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade')->comment('شناسه خدمات‌دهنده');
            $table->string('activity_name')->comment('نام فعالیت');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'user_id')->nullOnDelete()->comment('آخرین کاربر ویرایش کننده');
            $table->timestamps();

            // ایندکس برای جستجوی بهینه
            $table->index(['service_provider_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_activities');
    }
};