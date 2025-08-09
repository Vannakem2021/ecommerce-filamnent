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
        Schema::table('product_variants', function (Blueprint $table) {
            // Composite index for price range queries
            $table->index(['product_id', 'is_active', 'price_cents'], 'idx_variants_product_price');

            // Index for stock filtering
            $table->index(['is_active', 'stock_status', 'stock_quantity'], 'idx_variants_stock');

            // Index for default variant lookup
            $table->index(['product_id', 'is_default', 'is_active'], 'idx_variants_default');
        });

        Schema::table('product_variant_attributes', function (Blueprint $table) {
            // Composite index for variant filtering by attributes
            $table->index(['product_attribute_id', 'product_attribute_value_id', 'product_variant_id'], 'idx_pva_filtering');

            // Index for reverse lookup (variant to attributes)
            $table->index(['product_variant_id', 'product_attribute_id', 'product_attribute_value_id'], 'idx_pva_variant_lookup');
        });

        Schema::table('product_attribute_values', function (Blueprint $table) {
            // Index for attribute value filtering
            $table->index(['product_attribute_id', 'is_active', 'sort_order'], 'idx_pav_active_sorted');
        });

        Schema::table('products', function (Blueprint $table) {
            // Composite index for product listing with variants
            $table->index(['is_active', 'has_variants', 'is_featured'], 'idx_products_listing');

            // Index for price-based sorting
            $table->index(['is_active', 'price_cents'], 'idx_products_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('idx_variants_product_price');
            $table->dropIndex('idx_variants_stock');
            $table->dropIndex('idx_variants_default');
        });

        Schema::table('product_variant_attributes', function (Blueprint $table) {
            $table->dropIndex('idx_pva_filtering');
            $table->dropIndex('idx_pva_variant_lookup');
        });

        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropIndex('idx_pav_active_sorted');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_listing');
            $table->dropIndex('idx_products_price');
        });
    }
};
