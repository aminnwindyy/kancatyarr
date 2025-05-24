<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id('withdrawal_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('bank_account_number', 50);
            $table->string('status')->default('pending');
            $table->timestamp('request_date')->useCurrent();
            $table->timestamp('process_date')->nullable();
            $table->timestamps();
        });

        // Add enum check constraint
        DB::statement("ALTER TABLE withdrawal_requests ADD CONSTRAINT status_check CHECK (status IN ('pending', 'processing', 'completed', 'rejected'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
