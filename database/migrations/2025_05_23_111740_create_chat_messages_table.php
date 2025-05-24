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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('chat_id')->comment('شناسه گفتگو');
            $table->unsignedBigInteger('sender_id')->comment('شناسه فرستنده');
            $table->enum('sender_type', ['user', 'support'])->comment('نوع فرستنده');
            $table->text('content')->comment('محتوای پیام');
            $table->boolean('is_read')->default(false)->comment('وضعیت خوانده شدن');
            $table->timestamps();
            
            $table->foreign('chat_id')->references('chat_id')->on('chats')->onDelete('cascade');
        });
    }

    /**
     * بازگشت از مایگریشن
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
