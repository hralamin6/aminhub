<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return_in', 'return_out', 'transfer']);
            $table->enum('direction', ['in', 'out']);
            $table->decimal('quantity', 12, 4);           // Always in base unit
            $table->foreignId('unit_id')->nullable()->constrained('units');
            $table->decimal('original_quantity', 12, 4)->nullable(); // Before conversion
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('product_variant_id');
            $table->index('type');
            $table->index('direction');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
