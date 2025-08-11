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
            // Add JSON options column if not exists
            if (!Schema::hasColumn('product_variants', 'options')) {
                $table->json('options')->nullable()->after('name')
                    ->comment('Variant options in JSON format');
            }
            
            // Add override pricing if not exists
            if (!Schema::hasColumn('product_variants', 'override_price')) {
                $table->integer('override_price')->nullable()->after('price_cents')
                    ->comment('Override price in cents (null = use product base price)');
            }
            
            // Add variant image URL if not exists
            if (!Schema::hasColumn('product_variants', 'image_url')) {
                $table->string('image_url')->nullable()->after('images')
                    ->comment('Variant-specific image URL');
            }
            
            // Add migration tracking
            if (!Schema::hasColumn('product_variants', 'migrated_to_json')) {
                $table->boolean('migrated_to_json')->default(false)->after('image_url')
                    ->comment('Track which variants have been migrated');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Remove migration tracking columns
            if (Schema::hasColumn('product_variants', 'migrated_to_json')) {
                $table->dropColumn('migrated_to_json');
            }
            
            if (Schema::hasColumn('product_variants', 'image_url')) {
                $table->dropColumn('image_url');
            }
            
            // Note: We keep 'options' and 'override_price' as they might be used by existing system
        });
    }
};
