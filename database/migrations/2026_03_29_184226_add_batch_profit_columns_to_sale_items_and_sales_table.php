<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->decimal('unit_cost', 12, 4)->default(0)->after('unit_price');
            $table->decimal('profit', 12, 2)->default(0)->after('subtotal');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_cost', 12, 2)->default(0)->after('grand_total');
            $table->decimal('total_profit', 12, 2)->default(0)->after('total_cost');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['batch_id', 'unit_cost', 'profit']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'total_profit']);
        });
    }
};
