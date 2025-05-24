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
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade')->comment('شناسه کاربر');
            $table->string('name')->comment('نام خدمات‌دهنده');
            $table->string('email')->unique()->comment('ایمیل');
            $table->string('phone')->nullable()->comment('شماره تماس');
            $table->string('national_code')->nullable()->comment('کد ملی');
            $table->string('business_license')->nullable()->comment('شماره جواز کسب');
            $table->enum('category', ['commercial', 'connectyar'])->comment('نوع خدمات‌دهنده');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('وضعیت');
            $table->text('address')->nullable()->comment('آدرس');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->string('website')->nullable()->comment('وب‌سایت');
            $table->decimal('rating', 3, 2)->default(0)->comment('امتیاز فروشگاه');
            $table->timestamp('last_activity_at')->nullable()->comment('آخرین فعالیت');
            $table->foreignId('admin_id')->nullable()->constrained('users', 'user_id')->nullOnDelete()->comment('آخرین ادمین ویرایش کننده');
            $table->timestamps();
            $table->softDeletes();

            // ایندکس برای فیلدهای جستجو و فیلترینگ
            $table->index('status');
            $table->index('category');
            $table->index('national_code');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
}; 