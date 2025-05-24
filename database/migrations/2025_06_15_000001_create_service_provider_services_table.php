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
        if (!Schema::hasTable('service_provider_services')) {
            Schema::create('service_provider_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_provider_id')->constrained('service_providers', 'id')->onDelete('cascade');
                $table->foreignId('service_id')->constrained('services', 'service_id')->onDelete('cascade');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Unique combination of service provider and service
                $table->unique(['service_provider_id', 'service_id'], 'provider_service_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_services');
    }
}; 