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
            // Add JSON attributes column if not exists
            if (!Schema::hasColumn('products', 'attributes')) {
                $table->json('attributes')->nullable()->after('variant_attributes')
                    ->comment('Product-level attributes in JSON format');
            }
            
            // Add variant configuration metadata
            if (!Schema::hasColumn('products', 'variant_config')) {
                $table->json('variant_config')->nullable()->after('attributes')
                    ->comment('Variant configuration metadata');
            }
            
            // Add migration tracking
            if (!Schema::hasColumn('products', 'migrated_to_json')) {
                $table->boolean('migrated_to_json')->default(false)->after('variant_config')
                    ->comment('Track which products have been migrated');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove migration tracking columns
            if (Schema::hasColumn('products', 'migrated_to_json')) {
                $table->dropColumn('migrated_to_json');
            }
            
            if (Schema::hasColumn('products', 'variant_config')) {
                $table->dropColumn('variant_config');
            }
            
            // Note: We keep 'attributes' column as it might have been added separately
        });
    }
};
