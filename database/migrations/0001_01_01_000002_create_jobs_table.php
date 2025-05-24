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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('شناسه منحصر به فرد کار');
            $table->string('queue')->index()->comment('نام صف کار');
            $table->longText('payload')->comment('محتوای کار');
            $table->unsignedTinyInteger('attempts')->comment('تعداد تلاش‌های انجام شده');
            $table->unsignedInteger('reserved_at')->nullable()->comment('زمان رزرو شدن کار');
            $table->unsignedInteger('available_at')->comment('زمان در دسترس بودن کار');
            $table->unsignedInteger('created_at')->comment('زمان ایجاد کار');

            $table->index(['queue', 'reserved_at'])->comment('ایندکس ترکیبی صف و زمان رزرو');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('شناسه منحصر به فرد دسته کار');
            $table->string('name')->comment('نام دسته کار');
            $table->integer('total_jobs')->comment('تعداد کل کارها');
            $table->integer('pending_jobs')->comment('تعداد کارهای در انتظار');
            $table->integer('failed_jobs')->comment('تعداد کارهای ناموفق');
            $table->longText('failed_job_ids')->comment('شناسه‌های کارهای ناموفق');
            $table->mediumText('options')->nullable()->comment('تنظیمات اضافی');
            $table->integer('cancelled_at')->nullable()->comment('زمان لغو');
            $table->integer('created_at')->comment('زمان ایجاد');
            $table->integer('finished_at')->nullable()->comment('زمان اتمام');

            $table->index(['finished_at', 'cancelled_at'])->comment('ایندکس وضعیت تکمیل');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('شناسه منحصر به فرد');
            $table->string('uuid')->unique()->comment('شناسه منحصر به فرد جهانی');
            $table->text('connection')->comment('اتصال');
            $table->text('queue')->comment('صف');
            $table->longText('payload')->comment('محتوای کار');
            $table->longText('exception')->comment('استثنای رخ داده');
            $table->timestamp('failed_at')->useCurrent()->comment('زمان شکست');

            $table->index('failed_at')->comment('ایندکس زمان شکست');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
