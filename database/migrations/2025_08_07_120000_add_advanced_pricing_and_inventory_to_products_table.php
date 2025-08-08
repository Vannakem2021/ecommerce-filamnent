<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // First, let's add the new columns
            $table->integer('price_cents')->after('price')->index()->comment('Current selling price in cents');
            $table->integer('compare_price_cents')->nullable()->after('price_cents')->comment('Original/MSRP price in cents for showing discounts');
            $table->integer('cost_price_cents')->nullable()->after('compare_price_cents')->comment('Cost price in cents for profit calculations');

            // Inventory tracking fields
            $table->integer('stock_quantity')->default(0)->after('cost_price_cents')->index()->comment('Current stock quantity');
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'back_order'])->default('in_stock')->after('stock_quantity')->index()->comment('Current stock status');
            $table->integer('low_stock_threshold')->default(5)->after('stock_status')->comment('Threshold for low stock alerts');
            $table->boolean('track_inventory')->default(true)->after('low_stock_threshold')->comment('Whether to track inventory for this product');

            // Make the old price field nullable for backward compatibility
            $table->decimal('price', 10, 2)->nullable()->change();
        });

        // Convert existing decimal prices to cents
        DB::statement('UPDATE products SET price_cents = ROUND(price * 100)');

        // Update stock status based on existing in_stock field
        DB::statement("UPDATE products SET stock_status = CASE WHEN in_stock = 1 THEN 'in_stock' ELSE 'out_of_stock' END");

        // Set initial stock quantity based on in_stock status
        DB::statement('UPDATE products SET stock_quantity = CASE WHEN in_stock = 1 THEN 100 ELSE 0 END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_cents',
                'compare_price_cents',
                'cost_price_cents',
                'stock_quantity',
                'stock_status',
                'low_stock_threshold',
                'track_inventory'
            ]);
        });
    }
};
