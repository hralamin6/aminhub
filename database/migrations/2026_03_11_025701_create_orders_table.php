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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('address_id')->nullable()->constrained('user_addresses')->nullOnDelete();

            $table->string('shipping_name')->nullable();
            $table->string('shipping_phone', 20)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_division', 100)->nullable();
            $table->string('shipping_district', 100)->nullable();
            $table->string('shipping_upazila', 100)->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->enum('status', ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'returned'])->default('pending');
            $table->enum('payment_method', ['cod', 'bkash', 'nagad', 'card', 'other'])->default('cod');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');

            $table->enum('delivery_method', ['shop_delivery', 'courier', 'pickup'])->default('shop_delivery');
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->date('delivery_date')->nullable();

            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
