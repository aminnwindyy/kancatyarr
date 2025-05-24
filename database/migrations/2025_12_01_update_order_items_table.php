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
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'seller_id')) {
                $table->foreignId('seller_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('order_items', 'options')) {
                $table->json('options')->nullable()->after('total_price');
            }
            
            if (!Schema::hasColumn('order_items', 'status')) {
                $table->string('status')->default('pending')->after('options');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'seller_id',
                'options',
                'status',
            ]);
        });
    }
};