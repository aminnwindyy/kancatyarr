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
        Schema::create('service_provider_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['national_card', 'business_license', 'photo'])->comment('نوع مدرک');
            $table->string('file_path')->comment('مسیر فایل');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('وضعیت');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'user_id')->nullOnDelete()->comment('آخرین کاربر ویرایش کننده');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_documents');
    }
};