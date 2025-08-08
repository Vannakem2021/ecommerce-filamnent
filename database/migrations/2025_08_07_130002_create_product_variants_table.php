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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique()->index(); // Auto-generated: PRODUCT-RED-64GB
            $table->string('name')->nullable(); // Optional custom name: "iPhone 15 Pro Red 64GB"
            
            // Pricing (inherits from enhanced pricing system)
            $table->integer('price_cents')->index()->comment('Variant selling price in cents');
            $table->integer('compare_price_cents')->nullable()->comment('Variant compare price in cents');
            $table->integer('cost_price_cents')->nullable()->comment('Variant cost price in cents');
            
            // Inventory (inherits from enhanced inventory system)
            $table->integer('stock_quantity')->default(0)->index()->comment('Variant stock quantity');
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'back_order'])->default('in_stock')->index();
            $table->integer('low_stock_threshold')->default(5)->comment('Variant low stock threshold');
            $table->boolean('track_inventory')->default(true)->comment('Whether to track inventory for this variant');
            
            // Variant-specific fields
            $table->decimal('weight', 8, 2)->nullable()->comment('Variant weight in kg');
            $table->json('dimensions')->nullable()->comment('Variant dimensions: {length, width, height}');
            $table->string('barcode')->nullable()->index(); // UPC/EAN barcode
            $table->json('images')->nullable()->comment('Variant-specific images');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)->index(); // Default variant for the product
            
            $table->timestamps();

            // Ensure only one default variant per product
            $table->index(['product_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
