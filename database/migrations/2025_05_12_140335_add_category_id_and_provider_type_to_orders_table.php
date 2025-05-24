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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('product_id');
            $table->foreign('category_id')->references('category_id')->on('categories')->nullable();
            $table->enum('service_provider_type', ['business', 'connectyar'])->nullable()->after('service_provider_id');
            
            // ایندکس برای فیلدهای جدید
            $table->index('category_id');
            $table->index('service_provider_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['service_provider_type']);
            $table->dropColumn('category_id');
            $table->dropColumn('service_provider_type');
        });
    }
};
