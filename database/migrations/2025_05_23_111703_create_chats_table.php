<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * اجرای مایگریشن
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id('chat_id');
            $table->unsignedBigInteger('user_id')->comment('شناسه کاربر');
            $table->string('title')->comment('عنوان گفتگو');
            $table->enum('status', ['open', 'resolved', 'closed'])->default('open')->comment('وضعیت گفتگو');
            $table->timestamps();
            
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * بازگشت از مایگریشن
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
