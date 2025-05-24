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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans', 'plan_id')->onDelete('restrict');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->text('notes')->nullable();
            $table->boolean('is_custom_schedule')->default(false);
            $table->json('schedule_details')->nullable();
            $table->timestamps();

            // Add index for faster querying
            $table->index(['user_id', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
