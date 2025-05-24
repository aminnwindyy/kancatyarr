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
        if (!Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referrer_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->foreignId('referee_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
                $table->string('referral_code')->unique();
                $table->foreignId('program_id')->constrained('referral_programs')->cascadeOnDelete();
                $table->decimal('referrer_reward', 10, 2)->default(0);
                $table->decimal('referee_reward', 10, 2)->default(0);
                $table->boolean('referrer_reward_paid')->default(false);
                $table->boolean('referee_reward_paid')->default(false);
                $table->enum('status', ['pending', 'completed', 'expired'])->default('pending');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
}; 