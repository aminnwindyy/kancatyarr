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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number')->after('id')->unique();
            }
            
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->after('status')->default('online');
            }
            
            if (!Schema::hasColumn('orders', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->after('payment_method')->constrained('payments')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('orders', 'admin_approved_at')) {
                $table->timestamp('admin_approved_at')->nullable()->after('payment_id');
            }
            
            if (!Schema::hasColumn('orders', 'admin_approved_by')) {
                $table->foreignId('admin_approved_by')->nullable()->after('admin_approved_at')->constrained('users')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('orders', 'seller_delivered_at')) {
                $table->timestamp('seller_delivered_at')->nullable()->after('admin_approved_by');
            }
            
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('seller_delivered_at');
            }
            
            if (!Schema::hasColumn('orders', 'tracking_code')) {
                $table->string('tracking_code')->nullable()->after('delivered_at');
            }
            
            if (!Schema::hasColumn('orders', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('tracking_code');
            }
            
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_code');
            }
            
            if (!Schema::hasColumn('orders', 'final_price')) {
                $table->decimal('final_price', 15, 2)->default(0)->after('discount_amount');
            }
            
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('final_price');
            }
            
            if (!Schema::hasColumn('orders', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('notes');
            }
            
            if (!Schema::hasColumn('orders', 'seller_notes')) {
                $table->text('seller_notes')->nullable()->after('admin_notes');
            }
            
            if (!Schema::hasColumn('orders', 'reject_reason')) {
                $table->text('reject_reason')->nullable()->after('seller_notes');
            }
            
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            
            $table->dropColumn([
                'order_number',
                'payment_method',
                'payment_id',
                'admin_approved_at',
                'admin_approved_by',
                'seller_delivered_at',
                'delivered_at',
                'tracking_code',
                'discount_code',
                'discount_amount',
                'final_price',
                'notes',
                'admin_notes',
                'seller_notes',
                'reject_reason',
            ]);
        });
    }
}; 