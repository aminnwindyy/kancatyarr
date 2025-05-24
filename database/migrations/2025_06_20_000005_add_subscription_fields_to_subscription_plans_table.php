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
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->integer('max_users')->nullable()->after('is_featured');
            $table->integer('discount_percentage')->default(0)->after('max_users');
            $table->integer('sort_order')->default(0)->after('discount_percentage');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'max_users', 'discount_percentage', 'sort_order']);
            $table->dropSoftDeletes();
        });
    }
}; 