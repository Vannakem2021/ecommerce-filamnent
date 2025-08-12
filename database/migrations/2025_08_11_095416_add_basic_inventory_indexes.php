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
        // Add simple indexes for better inventory query performance
        // Only add if they don't already exist

        try {
            Schema::table('products', function (Blueprint $table) {
                // Composite index for active products with stock
                $table->index(['is_active', 'stock_quantity'], 'products_active_stock_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }

        try {
            Schema::table('product_variants', function (Blueprint $table) {
                // Composite index for active variants with stock
                $table->index(['is_active', 'stock_quantity'], 'product_variants_active_stock_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_active_stock_index');
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }

        try {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropIndex('product_variants_active_stock_index');
            });
        } catch (\Exception $e) {
            // Index might not exist, continue
        }
    }
};
