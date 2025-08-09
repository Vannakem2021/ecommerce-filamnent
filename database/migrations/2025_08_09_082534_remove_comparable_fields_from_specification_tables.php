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
        // Remove is_comparable column from specification_attributes table
        if (Schema::hasColumn('specification_attributes', 'is_comparable')) {
            Schema::table('specification_attributes', function (Blueprint $table) {
                $table->dropColumn('is_comparable');
            });
        }

        // Remove is_comparable column from product_attributes table if it exists
        if (Schema::hasColumn('product_attributes', 'is_comparable')) {
            Schema::table('product_attributes', function (Blueprint $table) {
                $table->dropColumn('is_comparable');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back is_comparable column to specification_attributes table
        Schema::table('specification_attributes', function (Blueprint $table) {
            $table->boolean('is_comparable')->default(true)->after('is_filterable')
                ->comment('Whether this attribute should appear in product comparisons');
        });

        // Add back is_comparable column to product_attributes table
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->boolean('is_comparable')->default(true)->after('is_filterable')
                ->comment('Whether this attribute should appear in product comparisons');
        });
    }
};
