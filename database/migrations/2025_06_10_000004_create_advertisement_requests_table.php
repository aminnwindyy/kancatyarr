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
        if (!Schema::hasTable('advertisement_requests')) {
            Schema::create('advertisement_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_provider_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('image_path')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('price', 10, 2)->default(0);
                $table->boolean('is_featured')->default(false);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisement_requests');
    }
}; 