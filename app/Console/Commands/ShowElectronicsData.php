<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Console\Command;

class ShowElectronicsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'electronics:show-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display created electronics attributes, categories, and products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Electronics Data Summary');
        $this->newLine();

        $this->showAttributes();
        $this->newLine();
        
        $this->showCategories();
        $this->newLine();
        
        $this->showProducts();
        $this->newLine();
        
        $this->showVariants();
    }

    private function showAttributes()
    {
        $this->info('🏷️  Product Attributes:');
        
        $attributes = ProductAttribute::with('values')->orderBy('sort_order')->get();
        
        $attributeData = [];
        foreach ($attributes as $attribute) {
            $attributeData[] = [
                $attribute->name,
                $attribute->type,
                $attribute->values->count() . ' values',
                $attribute->is_required ? 'Yes' : 'No',
                $attribute->is_active ? 'Active' : 'Inactive',
            ];
        }
        
        $this->table(
            ['Attribute', 'Type', 'Values', 'Required', 'Status'],
            $attributeData
        );
    }

    private function showCategories()
    {
        $this->info('📂 Categories:');
        
        $categories = Category::withCount('products')->get();
        
        $categoryData = [];
        foreach ($categories as $category) {
            $categoryData[] = [
                $category->name,
                $category->slug,
                $category->products_count . ' products',
                $category->is_active ? 'Active' : 'Inactive',
            ];
        }
        
        $this->table(
            ['Name', 'Slug', 'Products', 'Status'],
            $categoryData
        );
    }

    private function showProducts()
    {
        $this->info('📱 Products:');
        
        $products = Product::with(['category', 'brand', 'variants'])
            ->where('category_id', '>', 0)
            ->orderBy('name')
            ->get();
        
        $productData = [];
        foreach ($products as $product) {
            $productData[] = [
                $product->name,
                $product->category->name ?? 'N/A',
                $product->brand->name ?? 'N/A',
                '$' . number_format($product->price, 2),
                $product->variants->count() . ' variants',
                $product->stock_quantity,
                $product->is_active ? 'Active' : 'Inactive',
            ];
        }
        
        $this->table(
            ['Product', 'Category', 'Brand', 'Price', 'Variants', 'Stock', 'Status'],
            $productData
        );
    }

    private function showVariants()
    {
        $this->info('🔧 Product Variants (Sample):');
        
        $variants = ProductVariant::with(['product', 'attributeValues.attribute'])
            ->limit(20)
            ->get();
        
        $variantData = [];
        foreach ($variants as $variant) {
            $attributes = $variant->attributeValues->map(function ($value) {
                return $value->attribute->name . ': ' . $value->value;
            })->implode(', ');
            
            $variantData[] = [
                $variant->product->name ?? 'N/A',
                $variant->sku,
                $attributes ?: 'No attributes',
                '$' . number_format($variant->price_cents / 100, 2),
                $variant->stock_quantity,
                $variant->is_active ? 'Active' : 'Inactive',
            ];
        }
        
        $this->table(
            ['Product', 'SKU', 'Attributes', 'Price', 'Stock', 'Status'],
            $variantData
        );
        
        $totalVariants = ProductVariant::count();
        $this->info("📊 Total variants created: {$totalVariants}");
    }
}
