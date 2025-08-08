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
        Schema::table('order_items', function (Blueprint $table) {
            // Add variant support to order items
            $table->foreignId('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete()
                ->comment('The specific variant that was ordered');
            
            // Store variant details at time of order (for historical accuracy)
            $table->string('variant_sku')->nullable()->after('product_variant_id')
                ->comment('SKU of the variant at time of order');
            
            $table->json('variant_attributes')->nullable()->after('variant_sku')
                ->comment('Attribute values of the variant at time of order');
            
            // Add index for performance
            $table->index(['product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn([
                'product_variant_id',
                'variant_sku',
                'variant_attributes'
            ]);
        });
    }
};
