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
        if (!Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans', 'plan_id')->cascadeOnDelete();
                $table->timestamp('starts_at');
                $table->timestamp('expires_at')->nullable();
                $table->decimal('price_paid', 10, 2);
                $table->string('payment_id')->nullable(); // شناسه پرداخت مرتبط
                $table->enum('status', ['active', 'canceled', 'expired', 'pending'])->default('pending');
                $table->boolean('auto_renew')->default(false);
                $table->timestamp('next_billing_date')->nullable();
                $table->timestamp('canceled_at')->nullable(); // تاریخ لغو اشتراک
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
}; 