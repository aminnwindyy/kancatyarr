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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('کلید منحصر به فرد کش');
            $table->mediumText('value')->comment('مقدار کش شده');
            $table->integer('expiration')->comment('زمان انقضا');
            
            $table->index('expiration')->comment('ایندکس زمان انقضا برای پاکسازی خودکار');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('کلید منحصر به فرد قفل کش');
            $table->string('owner')->comment('مالک قفل');
            $table->integer('expiration')->comment('زمان انقضای قفل');
            
            $table->index('expiration')->comment('ایندکس زمان انقضا برای پاکسازی خودکار');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
