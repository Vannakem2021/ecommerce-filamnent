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
        Schema::table('products', function (Blueprint $table) {
            // Add variant support fields
            $table->boolean('has_variants')->default(false)->after('track_inventory')->index()
                ->comment('Whether this product has variants');
            
            $table->enum('variant_type', ['none', 'single', 'multiple'])->default('none')->after('has_variants')
                ->comment('none=no variants, single=one variant, multiple=multiple variants');
            
            // When a product has variants, these fields become defaults/templates for new variants
            $table->text('variant_attributes')->nullable()->after('variant_type')
                ->comment('JSON array of attribute IDs that this product uses for variants');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'has_variants',
                'variant_type', 
                'variant_attributes'
            ]);
        });
    }
};
