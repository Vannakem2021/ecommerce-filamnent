<?php

namespace Tests\Feature;

use App\Helpers\CartManagement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 99.99,
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);
        
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'price_cents' => 12999, // $129.99
            'stock_quantity' => 5,
            'track_inventory' => true,
        ]);
    }

    public function test_can_add_product_to_cart()
    {
        $result = CartManagement::addItemToCart($this->product->id);
        
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
        
        $cartItems = CartManagement::getCartItemsFromCookie();
        $this->assertCount(1, $cartItems);
        $this->assertEquals($this->product->id, $cartItems[0]['product_id']);
        $this->assertEquals(1, $cartItems[0]['quantity']);
    }

    public function test_can_add_variant_to_cart()
    {
        $attributes = ['color' => 'red', 'size' => 'large'];
        $result = CartManagement::addItemToCartWithVariant(
            $this->product->id,
            $this->variant->id,
            2,
            $attributes
        );
        
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
        
        $cartItems = CartManagement::getCartItemsFromCookie();
        $this->assertCount(1, $cartItems);
        $this->assertEquals($this->product->id, $cartItems[0]['product_id']);
        $this->assertEquals($this->variant->id, $cartItems[0]['variant_id']);
        $this->assertEquals($attributes, $cartItems[0]['variant_attributes']);
        $this->assertEquals(2, $cartItems[0]['quantity']);
    }

    public function test_inventory_validation_prevents_overselling()
    {
        // Try to add more than available stock
        $result = CartManagement::addItemToCartWithQuantity($this->product->id, 15, 'product');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContains('Insufficient stock', $result['error']);
    }

    public function test_cart_validation_service_validates_prices()
    {
        $cartItem = [
            'item_key' => 'product_' . $this->product->id,
            'product_id' => $this->product->id,
            'variant_id' => null,
            'name' => $this->product->name,
            'quantity' => 1,
            'unit_amount' => 199.99, // Wrong price
            'total_amount' => 199.99,
            'type' => 'product'
        ];
        
        $validation = CartValidationService::validateCartItem($cartItem);
        
        $this->assertFalse($validation['valid']);
        $this->assertContains('Price mismatch detected', $validation['errors'][0]);
        $this->assertEquals(99.99, $validation['corrected_price']);
    }

    public function test_cart_item_sanitization()
    {
        $dirtyItem = [
            'item_key' => 'product_1',
            'product_id' => '1',
            'name' => '<script>alert("xss")</script>Product Name',
            'quantity' => '2',
            'unit_amount' => '99.99',
            'total_amount' => '199.98',
            'type' => 'product'
        ];
        
        $sanitized = CartValidationService::sanitizeCartItem($dirtyItem);
        
        $this->assertEquals(1, $sanitized['product_id']);
        $this->assertEquals('alert(&quot;xss&quot;)Product Name', $sanitized['name']); // XSS should be escaped
        $this->assertEquals(2, $sanitized['quantity']);
        $this->assertEquals(99.99, $sanitized['unit_amount']);
    }

    public function test_unique_item_keys_for_different_variants()
    {
        $attributes1 = ['color' => 'red', 'size' => 'small'];
        $attributes2 = ['color' => 'blue', 'size' => 'large'];
        
        // Add same variant with different attributes
        CartManagement::addItemToCartWithVariant($this->product->id, $this->variant->id, 1, $attributes1);
        CartManagement::addItemToCartWithVariant($this->product->id, $this->variant->id, 1, $attributes2);
        
        $cartItems = CartManagement::getCartItemsFromCookie();
        
        // Should have 2 separate items
        $this->assertCount(2, $cartItems);
        $this->assertNotEquals($cartItems[0]['item_key'], $cartItems[1]['item_key']);
    }

    public function test_quantity_increment_with_inventory_check()
    {
        // Add item to cart
        CartManagement::addItemToCart($this->product->id);
        $cartItems = CartManagement::getCartItemsFromCookie();
        $itemKey = $cartItems[0]['item_key'];
        
        // Increment quantity
        $result = CartManagement::incrementQuantityToCartItem($itemKey);
        
        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);
        
        $updatedItems = CartManagement::getCartItemsFromCookie();
        $this->assertEquals(2, $updatedItems[0]['quantity']);
    }

    public function test_quantity_increment_fails_when_exceeding_stock()
    {
        // Add maximum stock to cart
        CartManagement::addItemToCartWithQuantity($this->product->id, 10, 'product');
        $cartItems = CartManagement::getCartItemsFromCookie();
        $itemKey = $cartItems[0]['item_key'];
        
        // Try to increment beyond stock
        $result = CartManagement::incrementQuantityToCartItem($itemKey);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContains('Insufficient stock', $result['error']);
    }

    public function test_cart_hash_generation_and_verification()
    {
        $cartItems = [
            [
                'product_id' => 1,
                'variant_id' => null,
                'quantity' => 2,
                'unit_amount' => 99.99
            ]
        ];
        
        $hash = CartValidationService::generateCartHash($cartItems);
        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA256 hash length
        
        // Verify integrity
        $this->assertTrue(CartValidationService::verifyCartIntegrity($cartItems, $hash));
        
        // Modify cart and verify it fails
        $cartItems[0]['unit_amount'] = 199.99;
        $this->assertFalse(CartValidationService::verifyCartIntegrity($cartItems, $hash));
    }
}
