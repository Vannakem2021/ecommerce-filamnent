<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Service to handle transitions between simple products and variant products
 * Resolves conflicts in pricing and inventory when switching modes
 */
class ProductVariantTransitionService
{
    /**
     * Convert a simple product to a variant product
     * Only updates the flag - requires manual variant creation
     */
    public static function convertToVariantProduct(Product $product): void
    {
        if ($product->has_variants) {
            return; // Already a variant product
        }

        DB::transaction(function () use ($product) {
            // Update product to have variants
            $product->update([
                'has_variants' => true,
                'track_inventory' => false, // Variants will handle inventory tracking
            ]);

            // Note: We don't create "Standard" variants anymore
            // The admin interface should require users to create real variants

            // Refresh the product to ensure the changes are reflected
            $product->refresh();
        });
    }

    /**
     * Convert a variant product back to a simple product
     * Consolidates variant data back to product level
     */
    public static function convertToSimpleProduct(Product $product): void
    {
        if (!$product->has_variants) {
            return; // Already a simple product
        }

        DB::transaction(function () use ($product) {
            $variants = $product->variants()->get();

            if ($variants->isEmpty()) {
                // No variants exist, just update the product
                $product->update([
                    'has_variants' => false,
                    'track_inventory' => true,
                    'stock_quantity' => 0,
                ]);
                return;
            }

            // Use the default variant or first variant for consolidation
            $primaryVariant = $variants->where('is_default', true)->first() ?? $variants->first();
            
            // Calculate total stock from all variants
            $totalStock = $variants->sum('stock_quantity');
            
            // Use the primary variant's price as the product price
            $productPrice = $primaryVariant->override_price ?? $product->price_cents;

            // Update product with consolidated data
            $product->update([
                'has_variants' => false,
                'price_cents' => $productPrice,
                'stock_quantity' => $totalStock,
                'track_inventory' => true,
                'low_stock_threshold' => $primaryVariant->low_stock_threshold ?? 5,
            ]);

            // Delete all variants
            $product->variants()->delete();
        });
    }

    /**
     * Sync product price to all variants that don't have override prices
     */
    public static function syncPriceToVariants(Product $product): void
    {
        if (!$product->has_variants) {
            return;
        }

        $product->variants()
            ->whereNull('override_price')
            ->update(['override_price' => $product->price_cents]);
    }

    /**
     * Create a default variant when enabling variants on an existing product
     */
    public static function createDefaultVariant(Product $product, array $options = []): ProductVariant
    {
        $defaultOptions = array_merge([
            'Color' => 'Standard',
            'Storage' => 'Standard'
        ], $options);

        return $product->variants()->create([
            'sku' => $product->sku . '-' . strtoupper(substr($defaultOptions['Color'], 0, 3)) . '-' . strtoupper(substr($defaultOptions['Storage'], 0, 3)),
            'options' => $defaultOptions,
            'override_price' => $product->price_cents,
            'stock_quantity' => $product->stock_quantity,
            'is_active' => true,
            'is_default' => true,
            'track_inventory' => $product->track_inventory,
            'low_stock_threshold' => $product->low_stock_threshold ?? 5,
        ]);
    }

    /**
     * Validate and fix any pricing/inventory conflicts
     */
    public static function validateAndFixConflicts(Product $product): array
    {
        $issues = [];
        $fixes = [];

        if ($product->has_variants) {
            // Check for variant products
            $variants = $product->variants()->get();
            
            if ($variants->isEmpty()) {
                $issues[] = 'Product marked as having variants but no variants exist';
                $fixes[] = 'Create default variant or convert to simple product';
            }

            // Check if product still has stock when it should use variant stock
            if ($product->stock_quantity > 0) {
                $issues[] = 'Product has stock quantity but should use variant stock';
                $fixes[] = 'Move stock to variants or convert to simple product';
            }

            // Check for variants without prices
            $variantsWithoutPrice = $variants->whereNull('override_price');
            if ($variantsWithoutPrice->isNotEmpty()) {
                $issues[] = $variantsWithoutPrice->count() . ' variants without override prices';
                $fixes[] = 'Set override prices or ensure product base price is correct';
            }

        } else {
            // Check for simple products
            if ($product->variants()->exists()) {
                $issues[] = 'Product marked as simple but has variants';
                $fixes[] = 'Enable variants or delete variant records';
            }

            if ($product->price_cents <= 0) {
                $issues[] = 'Simple product has no price set';
                $fixes[] = 'Set product price';
            }
        }

        return [
            'issues' => $issues,
            'fixes' => $fixes,
            'has_conflicts' => !empty($issues)
        ];
    }

    /**
     * Auto-fix common conflicts
     */
    public static function autoFixConflicts(Product $product): array
    {
        $fixed = [];

        if ($product->has_variants) {
            $variants = $product->variants()->get();

            // Fix: No variants exist but product marked as having variants
            if ($variants->isEmpty()) {
                // Don't create "Standard" variants - convert back to simple product
                $product->update(['has_variants' => false, 'track_inventory' => true]);
                $fixed[] = 'Converted to simple product (no variants exist)';
            }

            // Fix: Product has stock but should use variant stock
            if ($product->stock_quantity > 0) {
                $totalVariantStock = $variants->sum('stock_quantity');
                if ($totalVariantStock === 0) {
                    // Move product stock to default variant
                    $defaultVariant = $variants->where('is_default', true)->first();
                    if ($defaultVariant) {
                        $defaultVariant->update(['stock_quantity' => $product->stock_quantity]);
                        $product->update(['stock_quantity' => 0]);
                        $fixed[] = 'Moved product stock to default variant';
                    }
                } else {
                    // Variants already have stock, just clear product stock
                    $product->update(['stock_quantity' => 0]);
                    $fixed[] = 'Cleared product stock (variants already have stock)';
                }
            }

            // Fix: Variants without override prices
            $variantsWithoutPrice = $variants->whereNull('override_price');
            if ($variantsWithoutPrice->isNotEmpty() && $product->price_cents > 0) {
                $variantsWithoutPrice->each(function ($variant) use ($product) {
                    $variant->update(['override_price' => $product->price_cents]);
                });
                $fixed[] = 'Set override prices for variants without prices';
            }

        } else {
            // Fix: Simple product with variants
            if ($product->variants()->exists()) {
                self::convertToSimpleProduct($product);
                $fixed[] = 'Converted to simple product and consolidated variant data';
            }
        }

        return $fixed;
    }
}
