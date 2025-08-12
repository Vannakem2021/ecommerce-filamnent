<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Exception;

class CartValidationService
{
    /**
     * Validate cart item data against database
     */
    public static function validateCartItem(array $item): array
    {
        $errors = [];
        
        // Validate product exists
        $product = Product::find($item['product_id']);
        if (!$product) {
            $errors[] = "Product not found: {$item['product_id']}";
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validate product is active
        if (!$product->is_active) {
            $errors[] = "Product is no longer available: {$product->name}";
        }
        
        // Validate variant if specified
        if (!empty($item['variant_id'])) {
            $variant = ProductVariant::find($item['variant_id']);
            if (!$variant) {
                $errors[] = "Product variant not found: {$item['variant_id']}";
            } elseif (!$variant->is_active) {
                $errors[] = "Product variant is no longer available";
            } elseif ($variant->product_id !== $product->id) {
                $errors[] = "Variant does not belong to the specified product";
            }
        }
        
        // Validate price integrity
        $expectedPrice = self::getExpectedPrice($product, $item['variant_id'] ?? null);
        if (abs($item['unit_amount'] - $expectedPrice) > 0.01) {
            $errors[] = "Price mismatch detected. Expected: {$expectedPrice}, Got: {$item['unit_amount']}";
        }
        
        // Validate quantity
        if ($item['quantity'] <= 0) {
            $errors[] = "Invalid quantity: {$item['quantity']}";
        }
        
        if ($item['quantity'] > 100) { // Maximum quantity limit
            $errors[] = "Quantity exceeds maximum limit (100)";
        }
        
        // Validate total amount calculation
        $expectedTotal = $expectedPrice * $item['quantity'];
        if (abs($item['total_amount'] - $expectedTotal) > 0.01) {
            $errors[] = "Total amount calculation error";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'corrected_price' => $expectedPrice
        ];
    }
    
    /**
     * Get expected price for product or variant
     */
    protected static function getExpectedPrice(Product $product, $variantId = null): float
    {
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->is_active) {
                // Use the getFinalPrice method which handles override_price logic
                return $variant->getFinalPrice();
            }
        }

        // For products without variants, use the product price
        return $product->price ?? 0;
    }
    
    /**
     * Validate entire cart
     */
    public static function validateCart(array $cartItems): array
    {
        $errors = [];
        $correctedItems = [];
        $totalValue = 0;
        
        foreach ($cartItems as $index => $item) {
            $validation = self::validateCartItem($item);
            
            if (!$validation['valid']) {
                $errors["item_{$index}"] = $validation['errors'];
            } else {
                // Create corrected item with verified price
                $correctedItem = $item;
                $correctedItem['unit_amount'] = $validation['corrected_price'];
                $correctedItem['total_amount'] = $validation['corrected_price'] * $item['quantity'];
                $correctedItems[] = $correctedItem;
                $totalValue += $correctedItem['total_amount'];
            }
        }
        
        // Validate cart total value (prevent extremely large orders)
        if ($totalValue > 50000) { // $50,000 limit
            $errors['cart_total'] = ['Cart total exceeds maximum allowed value'];
        }
        
        // Validate cart item count
        if (count($cartItems) > 50) { // Maximum 50 items
            $errors['cart_size'] = ['Cart contains too many items (maximum 50)'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'corrected_items' => $correctedItems,
            'total_value' => $totalValue
        ];
    }
    
    /**
     * Sanitize cart item data
     */
    public static function sanitizeCartItem(array $item): array
    {
        // First validate the structure
        if (!self::validateCartItemStructure($item)) {
            // Return a minimal valid structure for invalid items
            return [
                'item_key' => '',
                'product_id' => 0,
                'variant_id' => null,
                'name' => '',
                'image' => '',
                'variant_attributes' => [],
                'quantity' => 1,
                'unit_amount' => 0,
                'total_amount' => 0,
                'type' => 'product'
            ];
        }

        return [
            'item_key' => htmlspecialchars($item['item_key'] ?? '', ENT_QUOTES, 'UTF-8'),
            'product_id' => (int) ($item['product_id'] ?? 0),
            'variant_id' => !empty($item['variant_id']) ? (int) $item['variant_id'] : null,
            'name' => htmlspecialchars(strip_tags($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'image' => filter_var($item['image'] ?? '', FILTER_SANITIZE_URL),
            'variant_attributes' => is_array($item['variant_attributes'] ?? null) ? $item['variant_attributes'] : [],
            'variant_options' => is_array($item['variant_options'] ?? null) ? $item['variant_options'] : [],
            'quantity' => max(1, min(100, (int) ($item['quantity'] ?? 1))),
            'unit_amount' => max(0, (float) ($item['unit_amount'] ?? 0)),
            'total_amount' => max(0, (float) ($item['total_amount'] ?? 0)),
            'type' => in_array($item['type'] ?? '', ['product', 'variant']) ? $item['type'] : 'product'
        ];
    }

    /**
     * Validate cart item structure
     */
    private static function validateCartItemStructure($item): bool
    {
        if (!is_array($item)) {
            return false;
        }

        $requiredFields = ['product_id', 'quantity', 'unit_amount', 'total_amount'];

        foreach ($requiredFields as $field) {
            if (!isset($item[$field])) {
                return false;
            }
        }

        // Validate data types
        if (!is_numeric($item['product_id']) ||
            !is_numeric($item['quantity']) ||
            !is_numeric($item['unit_amount']) ||
            !is_numeric($item['total_amount'])) {
            return false;
        }

        // Validate ranges
        if ($item['quantity'] <= 0 || $item['quantity'] > 100) {
            return false;
        }

        if ($item['unit_amount'] < 0 || $item['total_amount'] < 0) {
            return false;
        }

        return true;
    }
    
    /**
     * Rate limiting for cart operations
     */
    public static function checkRateLimit($userId = null, $ipAddress = null): bool
    {
        $key = $userId ? "cart_ops_user_{$userId}" : "cart_ops_ip_{$ipAddress}";
        
        // Use Laravel's cache for simple rate limiting
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 100) { // 100 operations per hour
            return false;
        }
        
        cache()->put($key, $attempts + 1, 3600); // 1 hour
        return true;
    }
    
    /**
     * Validate cart operation permissions
     */
    public static function validateCartPermissions($operation, $userId = null): bool
    {
        // Check if user is authenticated for certain operations
        if (in_array($operation, ['checkout', 'place_order']) && !$userId) {
            return false;
        }
        
        // Check rate limiting
        if (!self::checkRateLimit($userId, request()->ip())) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate secure cart hash for integrity checking
     */
    public static function generateCartHash(array $cartItems): string
    {
        $cartData = [];
        foreach ($cartItems as $item) {
            $cartData[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_amount' => $item['unit_amount']
            ];
        }
        
        return hash_hmac('sha256', serialize($cartData), config('app.key'));
    }
    
    /**
     * Verify cart integrity
     */
    public static function verifyCartIntegrity(array $cartItems, string $expectedHash): bool
    {
        $actualHash = self::generateCartHash($cartItems);
        return hash_equals($expectedHash, $actualHash);
    }
}
