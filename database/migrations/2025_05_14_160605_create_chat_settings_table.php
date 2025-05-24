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
        Schema::create('chat_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('کلید تنظیمات');
            $table->text('value')->nullable()->comment('مقدار تنظیمات');
            $table->text('description')->nullable()->comment('توضیحات تنظیمات');
            $table->string('type')->default('boolean')->comment('نوع تنظیمات: boolean, integer, string');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('chat_settings_logs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('کلید تنظیمات');
            $table->text('old_value')->nullable()->comment('مقدار قبلی');
            $table->text('new_value')->nullable()->comment('مقدار جدید');
            $table->unsignedBigInteger('updated_by');
            $table->timestamp('created_at');

            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_settings_logs');
        Schema::dropIfExists('chat_settings');
    }
};
