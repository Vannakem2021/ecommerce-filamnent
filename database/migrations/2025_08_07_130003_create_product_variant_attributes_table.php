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
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
            $table->timestamps();

            // Ensure unique attribute per variant (can't have Color=Red AND Color=Blue for same variant)
            $table->unique(['product_variant_id', 'product_attribute_id'], 'variant_attribute_unique');

            // Indexes for performance (with shorter names)
            $table->index(['product_variant_id', 'product_attribute_id'], 'pva_variant_attribute_idx');
            $table->index(['product_attribute_id', 'product_attribute_value_id'], 'pva_attribute_value_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
    }
};
