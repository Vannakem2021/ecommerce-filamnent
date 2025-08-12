<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unified Inventory Service
 * 
 * This service provides a single source of truth for all inventory calculations
 * and ensures consistent stock management across the entire application.
 */
class InventoryService
{
    /**
     * Check if a product has any stock available
     * 
     * @param Product $product
     * @return bool
     */
    public static function hasStock(Product $product): bool
    {
        if (!$product->track_inventory) {
            return true;
        }

        if (!$product->has_variants) {
            return $product->stock_quantity > 0;
        }

        // For products with variants, check if any active variant has stock
        return $product->variants()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->exists();
    }

    /**
     * Get total stock quantity for a product
     * 
     * @param Product $product
     * @return int
     */
    public static function getTotalStock(Product $product): int
    {
        if (!$product->track_inventory) {
            return 999999; // Unlimited stock when not tracking
        }

        if (!$product->has_variants) {
            return $product->stock_quantity;
        }

        // For products with variants, sum all active variant stock
        return $product->variants()
            ->where('is_active', true)
            ->sum('stock_quantity');
    }

    /**
     * Check if a specific variant has stock
     * 
     * @param ProductVariant $variant
     * @return bool
     */
    public static function variantHasStock(ProductVariant $variant): bool
    {
        if (!$variant->track_inventory) {
            return true;
        }

        return $variant->stock_quantity > 0;
    }

    /**
     * Get available stock for a specific variant
     * 
     * @param ProductVariant $variant
     * @return int
     */
    public static function getVariantStock(ProductVariant $variant): int
    {
        if (!$variant->track_inventory) {
            return 999999; // Unlimited stock when not tracking
        }

        return $variant->stock_quantity;
    }

    /**
     * Check if product is low stock
     * 
     * @param Product $product
     * @return bool
     */
    public static function isLowStock(Product $product): bool
    {
        if (!$product->track_inventory) {
            return false;
        }

        $totalStock = self::getTotalStock($product);
        $threshold = $product->low_stock_threshold ?? 5;

        return $totalStock <= $threshold && $totalStock > 0;
    }

    /**
     * Check if variant is low stock
     * 
     * @param ProductVariant $variant
     * @return bool
     */
    public static function isVariantLowStock(ProductVariant $variant): bool
    {
        if (!$variant->track_inventory) {
            return false;
        }

        $threshold = $variant->low_stock_threshold ?? 5;
        return $variant->stock_quantity <= $threshold && $variant->stock_quantity > 0;
    }

    /**
     * Get stock status for a product
     * 
     * @param Product $product
     * @return string ('in_stock', 'out_of_stock', 'low_stock')
     */
    public static function getStockStatus(Product $product): string
    {
        if (!$product->track_inventory) {
            return 'in_stock';
        }

        if (!self::hasStock($product)) {
            return 'out_of_stock';
        }

        if (self::isLowStock($product)) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get stock status for a variant
     * 
     * @param ProductVariant $variant
     * @return string ('in_stock', 'out_of_stock', 'low_stock')
     */
    public static function getVariantStockStatus(ProductVariant $variant): string
    {
        if (!$variant->track_inventory) {
            return 'in_stock';
        }

        if (!self::variantHasStock($variant)) {
            return 'out_of_stock';
        }

        if (self::isVariantLowStock($variant)) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Validate if requested quantity is available
     * 
     * @param Product $product
     * @param int $quantity
     * @param ProductVariant|null $variant
     * @return array ['valid' => bool, 'message' => string, 'available' => int]
     */
    public static function validateQuantity(Product $product, int $quantity, ?ProductVariant $variant = null): array
    {
        if ($quantity <= 0) {
            return [
                'valid' => false,
                'message' => 'Quantity must be greater than 0',
                'available' => 0
            ];
        }

        if ($variant) {
            $available = self::getVariantStock($variant);
            $valid = $available >= $quantity;
            
            return [
                'valid' => $valid,
                'message' => $valid ? 'Stock available' : "Insufficient stock. Available: {$available}, Requested: {$quantity}",
                'available' => $available
            ];
        }

        if ($product->has_variants) {
            return [
                'valid' => false,
                'message' => 'Please select product options',
                'available' => 0
            ];
        }

        $available = self::getTotalStock($product);
        $valid = $available >= $quantity;

        return [
            'valid' => $valid,
            'message' => $valid ? 'Stock available' : "Insufficient stock. Available: {$available}, Requested: {$quantity}",
            'available' => $available
        ];
    }

    /**
     * Get effective stock for display purposes
     * This method determines what stock to show to users
     *
     * @param Product $product
     * @param ProductVariant|null $selectedVariant
     * @return array ['quantity' => int, 'status' => string, 'message' => string]
     */
    public static function getDisplayStock(Product $product, ?ProductVariant $selectedVariant = null): array
    {
        if ($selectedVariant) {
            $quantity = self::getVariantStock($selectedVariant);
            $status = self::getVariantStockStatus($selectedVariant);

            return [
                'quantity' => $quantity,
                'status' => $status,
                'message' => self::formatStockMessage($quantity, $status)
            ];
        }

        if ($product->has_variants) {
            $totalStock = self::getTotalStock($product);
            $hasStock = self::hasStock($product);
            $status = $hasStock ? 'in_stock' : 'out_of_stock';

            return [
                'quantity' => $totalStock,
                'status' => $status,
                'message' => self::formatStockMessage($totalStock, $status)
            ];
        }

        $quantity = self::getTotalStock($product);
        $status = self::getStockStatus($product);

        return [
            'quantity' => $quantity,
            'status' => $status,
            'message' => self::formatStockMessage($quantity, $status)
        ];
    }

    /**
     * Format user-friendly stock messages
     *
     * @param int $quantity
     * @param string $status
     * @return string
     */
    public static function formatStockMessage(int $quantity, string $status): string
    {
        switch ($status) {
            case 'out_of_stock':
                return 'Out of stock';

            case 'low_stock':
                if ($quantity === 1) {
                    return 'Only 1 left!';
                } elseif ($quantity <= 3) {
                    return "Only {$quantity} left!";
                } else {
                    return "Low stock - {$quantity} left";
                }

            case 'in_stock':
            default:
                if ($quantity >= 999999) {
                    return 'In stock';
                } elseif ($quantity >= 20) {
                    return 'In stock';
                } elseif ($quantity >= 10) {
                    return "{$quantity} in stock";
                } else {
                    return "{$quantity} in stock";
                }
        }
    }

    /**
     * Bulk check stock status for multiple products
     * Optimized for product listings
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $products
     * @return array [product_id => ['has_stock' => bool, 'total_stock' => int]]
     */
    public static function bulkCheckStock($products): array
    {
        $results = [];
        
        // Eager load variants to avoid N+1 queries (only if it's an Eloquent Collection)
        if (method_exists($products, 'load')) {
            $products->load(['variants' => function ($query) {
                $query->where('is_active', true)->select('id', 'product_id', 'stock_quantity', 'track_inventory');
            }]);
        }

        foreach ($products as $product) {
            $results[$product->id] = [
                'has_stock' => self::hasStock($product),
                'total_stock' => self::getTotalStock($product),
                'status' => self::getStockStatus($product)
            ];
        }

        return $results;
    }
}
