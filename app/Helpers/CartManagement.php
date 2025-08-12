<?php

namespace App\Helpers;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartValidationService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class CartManagement {

    // add item to cart
    static public function addItemToCart($product_id)
    {
        return self::addItemToCartWithQuantity($product_id, 1, 'product');
    }

    // add item to cart with variant support (simplified - uses JSON options)
    static public function addItemToCartWithVariant($product_id, $variant_id = null, $quantity = 1, $variant_options = [])
    {
        $type = $variant_id ? 'variant' : 'product';
        $item_id = $variant_id ?? $product_id;

        return self::addItemToCartWithQuantity($item_id, $quantity, $type, $variant_options, $product_id);
    }

    // add item to cart with specific quantity and type (simplified - uses JSON options)
    static public function addItemToCartWithQuantity($item_id, $quantity = 1, $type = 'product', $variant_options = [], $product_id = null)
    {
        // Check permissions and rate limiting
        if (!CartValidationService::validateCartPermissions('add_to_cart', auth()->id())) {
            return ['error' => 'Too many cart operations. Please try again later.'];
        }

        // NEW: Validate item data before adding to cart
        $validationResult = self::validateItemData($item_id, $quantity, $type, $variant_options, $product_id);
        if (!$validationResult['valid']) {
            return ['error' => $validationResult['message']];
        }

        $cart_items = self::getCartItemsFromCookie();

        // Create unique item key that includes variant options (JSON-based)
        $item_key = self::generateItemKey($item_id, $type, $variant_options);

        $existing_item = null;

        foreach ($cart_items as $key => $item)
        {
            if ($item['item_key'] == $item_key) {
                $existing_item = $key;
                break;
            }
        }

        if ($existing_item !== null) {
            // Validate inventory for existing item quantity increase
            $existingItem = $cart_items[$existing_item];
            $newQuantity = $existingItem['quantity'] + $quantity;

            $validation = self::validateInventory(
                $existingItem['product_id'],
                $existingItem['variant_id'] ?? null,
                $newQuantity
            );

            if (!$validation['valid']) {
                return ['error' => $validation['message']];
            }

            $cart_items[$existing_item]['quantity'] = $newQuantity;
            $cart_items[$existing_item]['total_amount'] = $cart_items[$existing_item]['quantity'] * $cart_items[$existing_item]['unit_amount'];
        } else {
            // Validate inventory for new item
            $productId = $product_id ?? $item_id;
            $variantId = $type === 'variant' ? $item_id : null;

            $validation = self::validateInventory($productId, $variantId, $quantity);

            if (!$validation['valid']) {
                return ['error' => $validation['message']];
            }

            // Get item data (product or variant)
            $itemData = self::getItemData($item_id, $type, $product_id);

            if ($itemData) {
                $cart_items[] = [
                    'item_key' => $item_key,
                    'product_id' => $itemData['product_id'],
                    'variant_id' => $itemData['variant_id'],
                    'name' => $itemData['name'],
                    'image' => $itemData['image'],
                    'variant_options' => $variant_options, // Simplified JSON options
                    'quantity' => $quantity,
                    'unit_amount' => $itemData['price'],
                    'total_amount' => $itemData['price'] * $quantity,
                    'type' => $type
                ];
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    // remove item from cart

    static public function removeCartItems($item_key)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item)
        {
            if ($item['item_key'] == $item_key){
                unset($cart_items[$key]);
            }
        }

        self::addCartItemsToCookie($cart_items);

        return $cart_items;
    }

    // add cart item to cookie

    static public function addCartItemsToCookie($cart_items)
    {
        Cookie::queue('cart_items', json_encode($cart_items), 60*24*30);
    }

    // clear cart items from cookie

    static public function clearCartItems()
    {
        Cookie::queue(Cookie::forget('cart_items'));
    }

    // get all cart items from cookie with validation
    static public function getCartItemsFromCookie()
    {
        try {
            $cartData = Cookie::get('cart_items');

            if (empty($cartData)) {
                return [];
            }

            $cart_items = json_decode($cartData, true);

            // Validate JSON decode success
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Invalid cart JSON data', ['error' => json_last_error_msg()]);
                self::clearCartItems(); // Clear corrupted cart
                return [];
            }

            if (!is_array($cart_items)) {
                Log::warning('Cart data is not an array');
                self::clearCartItems();
                return [];
            }

            // Sanitize and validate cart items
            return self::validateAndCorrectCartItems($cart_items);

        } catch (\Exception $e) {
            Log::error('Cart retrieval error', ['error' => $e->getMessage()]);
            self::clearCartItems();
            return [];
        }
    }

    // get validated cart items (server-side price verification)
    static public function getValidatedCartItems()
    {
        $cart_items = self::getCartItemsFromCookie();
        $validation = CartValidationService::validateCart($cart_items);

        if (!$validation['valid']) {
            // Log validation errors for security monitoring
            Log::warning('Cart validation failed', [
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'errors' => $validation['errors']
            ]);

            // Return corrected items or empty cart based on severity
            return $validation['corrected_items'] ?? [];
        }

        return $validation['corrected_items'];
    }

    // increment item quantity by item_key
    static public function incrementQuantityToCartItem($item_key)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {
            if ($item['item_key'] == $item_key) {
                // Validate inventory before incrementing
                $validation = self::validateInventory(
                    $item['product_id'],
                    $item['variant_id'] ?? null,
                    $item['quantity'] + 1
                );

                if ($validation['valid']) {
                    $cart_items[$key]['quantity']++;
                    $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount'];
                } else {
                    // Return error message if validation fails
                    return ['error' => $validation['message']];
                }
                break;
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    // decrement item quantity by item_key
    static public function decrementQuantityToCartItem($item_key)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {
            if ($item['item_key'] == $item_key) {
                if ($cart_items[$key]['quantity'] > 1) {
                    $cart_items[$key]['quantity']--;
                    $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount'];
                }
                break;
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    // calculate grandtotal

    static public function calculateGrandTotal($items)
    {
        return array_sum(array_column($items, 'total_amount'));
    }

    // calculate total quantity of all items in cart
    static public function calculateTotalQuantity($items = null)
    {
        if ($items === null) {
            $items = self::getCartItemsFromCookie();
        }
        return array_sum(array_column($items, 'quantity'));
    }

    // Generate unique item key for cart items (simplified - uses JSON options)
    static public function generateItemKey($item_id, $type, $variant_options = [])
    {
        $base_key = $type . '_' . $item_id;

        if (!empty($variant_options)) {
            // Sort options to ensure consistent key generation
            ksort($variant_options);
            $options_hash = md5(serialize($variant_options));
            return $base_key . '_' . $options_hash;
        }

        return $base_key;
    }

    // NEW METHOD: Validate item data before adding to cart
    static private function validateItemData($item_id, $quantity, $type, $variant_options, $product_id)
    {
        // Validate quantity
        if ($quantity <= 0 || $quantity > 100) {
            return ['valid' => false, 'message' => 'Invalid quantity. Must be between 1 and 100.'];
        }

        // Validate product/variant exists and get correct price (standardized to dollars)
        if ($type === 'variant') {
            $variant = ProductVariant::find($item_id);
            if (!$variant || !$variant->is_active) {
                return ['valid' => false, 'message' => 'Product variant not found or inactive'];
            }

            // Verify variant belongs to the specified product if product_id is provided
            if ($product_id && $variant->product_id !== $product_id) {
                return ['valid' => false, 'message' => 'Variant does not belong to the specified product'];
            }

            $correctPrice = $variant->getFinalPrice(); // Returns dollars (from cents)
        } else {
            $product = Product::find($item_id);
            if (!$product || !$product->is_active) {
                return ['valid' => false, 'message' => 'Product not found or inactive'];
            }
            $correctPrice = $product->price; // Returns dollars (from price_cents via accessor)
        }

        return [
            'valid' => true,
            'correct_price' => $correctPrice,
            'message' => 'Valid item data'
        ];
    }

    // NEW METHOD: Validate cart items and correct prices
    static private function validateAndCorrectCartItems($cart_items)
    {
        $corrected_items = [];

        foreach ($cart_items as $item) {
            // First sanitize the item
            $sanitized_item = CartValidationService::sanitizeCartItem($item);

            // Validate required fields
            if (!isset($sanitized_item['product_id'], $sanitized_item['quantity'], $sanitized_item['unit_amount'])) {
                continue; // Skip invalid items
            }

            // Get correct price from database
            $correctPrice = self::getCorrectItemPrice($sanitized_item);
            if ($correctPrice === null) {
                continue; // Skip items that no longer exist
            }

            // Correct the item data
            $sanitized_item['unit_amount'] = $correctPrice;
            $sanitized_item['total_amount'] = $correctPrice * $sanitized_item['quantity'];

            $corrected_items[] = $sanitized_item;
        }

        return $corrected_items;
    }

    // NEW METHOD: Get correct price from database (standardized to dollars)
    static private function getCorrectItemPrice($item)
    {
        if (isset($item['variant_id']) && $item['variant_id']) {
            $variant = ProductVariant::find($item['variant_id']);
            if ($variant && $variant->is_active) {
                // Use getFinalPrice() which returns dollars (converted from cents)
                return $variant->getFinalPrice();
            }
            return null;
        }

        $product = Product::find($item['product_id']);
        if ($product && $product->is_active) {
            // Use price attribute which returns dollars (converted from price_cents)
            return $product->price;
        }
        return null;
    }

    // Get item data (product or variant) - STANDARDIZED PRICING (dollars)
    static protected function getItemData($item_id, $type, $product_id = null)
    {
        if ($type === 'variant') {
            $variant = ProductVariant::with('product')->find($item_id);
            if ($variant) {
                return [
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'name' => $variant->name ?: $variant->product->name,
                    'image' => $variant->image_url ?: ($variant->images[0] ?? $variant->product->images[0] ?? null),
                    'price' => $variant->getFinalPrice(), // Standardized: returns dollars
                    'product_slug' => $variant->product->slug,
                ];
            }
        } else {
            $product = Product::find($item_id);
            if ($product) {
                return [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => $product->name,
                    'image' => $product->images[0] ?? null,
                    'price' => $product->price, // Standardized: returns dollars (from price_cents)
                    'product_slug' => $product->slug,
                ];
            }
        }

        return null;
    }

    // Validate inventory before adding to cart (using unified InventoryService)
    static public function validateInventory($product_id, $variant_id = null, $quantity = 1)
    {
        $product = Product::find($product_id);
        if (!$product) {
            return ['valid' => false, 'message' => 'Product not found'];
        }

        $variant = null;
        if ($variant_id) {
            $variant = ProductVariant::find($variant_id);
            if (!$variant) {
                return ['valid' => false, 'message' => 'Product variant not found'];
            }
        }

        // Use unified InventoryService for validation
        return \App\Services\InventoryService::validateQuantity($product, $quantity, $variant);
    }

    // ========================================
    // SIMPLIFIED INVENTORY OPERATIONS
    // ========================================

    /**
     * Reduce stock when order is placed (simplified approach)
     */
    static public function reduceInventoryForOrder($cart_items)
    {
        foreach ($cart_items as $item) {
            if ($item['type'] === 'variant' && $item['variant_id']) {
                $variant = ProductVariant::find($item['variant_id']);
                if ($variant) {
                    $success = $variant->reduceStockSimple($item['quantity']);
                    if (!$success) {
                        throw new \Exception("Failed to reduce stock for variant {$variant->sku}");
                    }
                }
            } else {
                $product = Product::find($item['product_id']);
                if ($product) {
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product {$product->name}");
                    }
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }
        }
    }

}