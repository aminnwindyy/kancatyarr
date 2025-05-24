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
        Schema::create('seller_business_categories', function (Blueprint $table) {
            $table->foreignId('seller_id')->constrained('sellers', 'seller_id')->onDelete('cascade');
            $table->foreignId('business_category_id')->constrained('business_categories', 'business_category_id')->onDelete('cascade');
            $table->primary(['seller_id', 'business_category_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_business_categories');
    }
};
