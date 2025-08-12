<?php

// Simple test script to verify stock tracking logic
require_once 'vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;

echo "Testing Stock Tracking Logic\n";
echo "============================\n\n";

// Test 1: Product without variants
echo "Test 1: Product without variants\n";
try {
    // Simulate a product without variants
    $product = new Product([
        'name' => 'Test Product',
        'has_variants' => false,
        'track_inventory' => true,
        'stock_quantity' => 10
    ]);
    
    $errors = $product->validateStockConfiguration();
    if (empty($errors)) {
        echo "✓ Product without variants: Valid configuration\n";
    } else {
        echo "✗ Product without variants: " . implode(', ', $errors) . "\n";
    }
    
    // Test inventory validation
    $validation = InventoryService::validateQuantity($product, 5);
    echo "  Inventory validation (5 units): " . ($validation['valid'] ? 'Valid' : $validation['message']) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error testing product without variants: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Product with variants (correct configuration)
echo "Test 2: Product with variants (correct configuration)\n";
try {
    // Simulate a product with variants
    $product = new Product([
        'name' => 'Test Product with Variants',
        'has_variants' => true,
        'track_inventory' => false,
        'stock_quantity' => 0
    ]);
    
    $errors = $product->validateStockConfiguration();
    if (empty($errors)) {
        echo "✓ Product with variants: Valid configuration\n";
    } else {
        echo "✗ Product with variants: " . implode(', ', $errors) . "\n";
    }
    
    // Test inventory validation without variant
    $validation = InventoryService::validateQuantity($product, 5);
    echo "  Inventory validation without variant: " . $validation['message'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Error testing product with variants: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Product with variants (incorrect configuration)
echo "Test 3: Product with variants (incorrect configuration)\n";
try {
    // Simulate a product with variants but wrong config
    $product = new Product([
        'name' => 'Test Product with Wrong Config',
        'has_variants' => true,
        'track_inventory' => true,  // WRONG: should be false
        'stock_quantity' => 10      // WRONG: should be 0
    ]);
    
    $errors = $product->validateStockConfiguration();
    if (empty($errors)) {
        echo "✓ Product with wrong config: Valid configuration\n";
    } else {
        echo "✗ Product with wrong config: " . implode(', ', $errors) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error testing product with wrong config: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Product without variants (incorrect configuration)
echo "Test 4: Product without variants (incorrect configuration)\n";
try {
    // Simulate a product without variants but wrong config
    $product = new Product([
        'name' => 'Test Product without Variants Wrong Config',
        'has_variants' => false,
        'track_inventory' => false,  // WRONG: should be true
        'stock_quantity' => 10
    ]);
    
    $errors = $product->validateStockConfiguration();
    if (empty($errors)) {
        echo "✓ Product without variants (wrong config): Valid configuration\n";
    } else {
        echo "✗ Product without variants (wrong config): " . implode(', ', $errors) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error testing product without variants (wrong config): " . $e->getMessage() . "\n";
}

echo "\nStock tracking logic tests completed!\n";
