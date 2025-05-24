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
        Schema::table('products', function (Blueprint $table) {
            // اضافه کردن فیلد service_provider_id
            $table->foreignId('service_provider_id')
                  ->after('seller_id')
                  ->nullable()
                  ->constrained('service_providers')
                  ->onDelete('cascade')
                  ->comment('شناسه خدمات‌دهنده');
            
            // ایجاد ایندکس برای بهبود عملکرد
            $table->index(['service_provider_id', 'approval_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // حذف ایندکس و فیلد
            $table->dropIndex(['service_provider_id', 'approval_status']);
            $table->dropForeign(['service_provider_id']);
            $table->dropColumn('service_provider_id');
        });
    }
};
