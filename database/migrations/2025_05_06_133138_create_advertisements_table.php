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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id('advertisement_id');
            $table->foreignId('seller_id')->constrained('sellers', 'seller_id')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->integer('clicks')->default(0);
            $table->integer('views')->default(0);
            $table->string('status')->default('pending_approval');
            $table->json('target_location')->nullable();
            $table->json('target_categories')->nullable();
            $table->string('banner_position', 50)->nullable();
            $table->timestamps();
        });

        // Add enum check constraint
        DB::statement("ALTER TABLE advertisements ADD CONSTRAINT status_check CHECK (status IN ('active', 'inactive', 'pending_approval', 'rejected'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
