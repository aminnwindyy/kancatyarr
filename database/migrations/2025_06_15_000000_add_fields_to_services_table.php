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
        Schema::table('services', function (Blueprint $table) {
            $table->enum('delivery_type', ['single', 'multiple'])->default('single')->after('description');
            $table->decimal('price', 10, 2)->default(0)->after('delivery_type');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'price', 'status']);
        });
    }
}; 