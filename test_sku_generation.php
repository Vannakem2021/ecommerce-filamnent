<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;

echo "Testing SKU Auto-generation\n";
echo "==========================\n\n";

// Check existing products
echo "Existing products:\n";
$products = Product::with('brand')->select('id', 'name', 'slug', 'sku', 'brand_id')->take(3)->get();
foreach ($products as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Slug: " . ($product->slug ?: 'NULL') . ", SKU: " . ($product->sku ?: 'NULL') . ", Brand: " . ($product->brand->name ?? 'No Brand') . "\n";
}

echo "\n";

// Test SKU generation method
echo "Testing SKU generation method:\n";
$testProduct = $products->first();
if ($testProduct) {
    echo "Original SKU: " . ($testProduct->sku ?: 'NULL') . "\n";
    $generatedSku = $testProduct->generateSku();
    echo "Generated SKU: {$generatedSku}\n";
}

echo "\n";

// Test creating a new product without SKU
echo "Testing auto-generation on new product creation:\n";
try {
    $brand = Brand::first();
    $category = Category::first();

    if ($brand && $category) {
        $newProduct = new Product([
            'name' => 'Test Product Auto SKU',
            'slug' => 'test-product-auto-sku',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'price_cents' => 9999,
            'description' => 'Test product for SKU auto-generation',
            // Note: No SKU provided - should auto-generate
        ]);

        echo "Before save - SKU: " . ($newProduct->sku ?: 'NULL') . "\n";
        $newProduct->save();
        echo "After save - SKU: " . ($newProduct->sku ?: 'NULL') . "\n";

        // Clean up - delete the test product
        $newProduct->delete();
        echo "Test product deleted.\n";
    } else {
        echo "No brand or category found for testing.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
