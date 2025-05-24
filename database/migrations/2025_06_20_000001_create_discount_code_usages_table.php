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
        if (!Schema::hasTable('discount_code_usages')) {
            Schema::create('discount_code_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id')->nullOnDelete();
                $table->decimal('amount_discounted', 10, 2)->default(0); // مبلغ تخفیف اعمال شده
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_code_usages');
    }
}; 