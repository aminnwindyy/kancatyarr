<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->foreignId('order_id')->unique()->constrained('orders', 'order_id')->onDelete('cascade');
            $table->string('invoice_number', 50)->unique();
            $table->timestamp('invoice_date')->useCurrent();
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_status')->default('pending');
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->timestamps();
        });

        // Add enum check constraint
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT payment_status_check CHECK (payment_status IN ('pending', 'paid', 'partially_paid', 'refunded'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
