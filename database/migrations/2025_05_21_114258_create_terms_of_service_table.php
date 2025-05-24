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
        Schema::create('terms_of_service', function (Blueprint $table) {
            $table->id();
            $table->integer('version')->comment('شماره نسخه قوانین');
            $table->text('content')->comment('متن قوانین');
            $table->boolean('active')->default(true)->comment('وضعیت فعال بودن این نسخه');
            $table->timestamps();
            
            $table->index('version');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_of_service');
    }
};
