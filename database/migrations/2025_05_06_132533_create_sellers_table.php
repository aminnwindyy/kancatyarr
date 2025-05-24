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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id('seller_id');
            $table->foreignId('user_id')->unique()->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('national_code', 20)->nullable();
            $table->string('business_license_number', 50)->nullable();
            $table->string('shop_name')->nullable();
            $table->text('shop_description')->nullable();
            $table->string('shop_logo')->nullable();
            $table->text('shop_address')->nullable();
            $table->string('shop_phone_number', 20)->nullable();
            $table->json('social_media_links')->nullable();
            $table->json('location')->nullable();
            $table->boolean('accept_from_own_city')->default(false);
            $table->boolean('accept_nationwide')->default(false);
            $table->string('document_verification_status')->default('pending');
            $table->text('verification_comment')->nullable();
            $table->timestamps();
        });

        // Add enum check constraint for document_verification_status
        DB::statement("ALTER TABLE sellers ADD CONSTRAINT document_verification_status_check CHECK (document_verification_status IN ('pending', 'approved', 'rejected'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
