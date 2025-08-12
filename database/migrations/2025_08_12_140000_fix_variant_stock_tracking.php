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
        // Fix stock tracking logic for products with variants
        
        // Step 1: Set stock_quantity to 0 for all products that have variants
        // Products with variants should not track inventory at product level
        DB::statement("
            UPDATE products 
            SET stock_quantity = 0, 
                track_inventory = false 
            WHERE has_variants = true
        ");
        
        // Step 2: Ensure all variants of products with variants have track_inventory = true
        DB::statement("
            UPDATE product_variants pv
            INNER JOIN products p ON pv.product_id = p.id
            SET pv.track_inventory = true
            WHERE p.has_variants = true
        ");
        
        // Step 3: For products without variants, ensure they track inventory at product level
        DB::statement("
            UPDATE products 
            SET track_inventory = true 
            WHERE has_variants = false 
            AND track_inventory = false
        ");
        
        // Step 4: Log the changes for audit purposes
        $productsWithVariants = DB::table('products')->where('has_variants', true)->count();
        $productsWithoutVariants = DB::table('products')->where('has_variants', false)->count();
        $totalVariants = DB::table('product_variants')->count();
        
        \Log::info('Stock tracking migration completed', [
            'products_with_variants' => $productsWithVariants,
            'products_without_variants' => $productsWithoutVariants,
            'total_variants' => $totalVariants,
            'timestamp' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration is not easily reversible as it corrects data integrity
        // We'll log the reversal attempt but not actually reverse the changes
        // as they represent correct business logic
        
        \Log::warning('Attempted to reverse stock tracking fix migration', [
            'message' => 'This migration corrects data integrity and should not be reversed',
            'timestamp' => now()
        ]);
    }
};
