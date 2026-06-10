<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add landed cost tracking to purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('landed_cost', 12, 4)->default(0)->after('unit_price');
            $table->decimal('shipping_share', 12, 4)->default(0)->after('landed_cost');
            $table->decimal('tax_share', 12, 4)->default(0)->after('shipping_share');
        });

        // Update product_batches to track landed cost
        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('landed_cost', 12, 4)->default(0)->after('purchase_price');
        });

        // Add unique constraint to batch numbers per variant
        Schema::table('product_batches', function (Blueprint $table) {
            $table->unique(['product_variant_id', 'batch_number'], 'unique_batch_per_variant');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['landed_cost', 'shipping_share', 'tax_share']);
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('landed_cost');
            $table->dropUnique('unique_batch_per_variant');
        });
    }
};
