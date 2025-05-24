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
        if (!Schema::hasTable('referral_programs')) {
            Schema::create('referral_programs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('reward_type', ['percentage', 'fixed_amount', 'points'])->default('fixed_amount');
                $table->decimal('reward_amount', 10, 2)->default(0);
                $table->decimal('referrer_reward', 10, 2)->default(0); // پاداش دعوت‌کننده
                $table->decimal('referee_reward', 10, 2)->default(0); // پاداش دعوت‌شونده
                $table->integer('expiry_days')->default(30); // مدت اعتبار دعوت (روز)
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_programs');
    }
}; 