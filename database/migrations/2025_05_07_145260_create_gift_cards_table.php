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
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id('gift_card_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('card_number', 24)->unique();
            $table->decimal('amount', 12, 2);
            $table->date('expiry_date');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            // Add indexes for faster querying
            $table->index('user_id');
            $table->index('created_by');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
