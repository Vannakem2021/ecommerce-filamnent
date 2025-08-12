<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductSpecificationController extends Controller
{
    /**
     * Get specifications for a product, optionally for a specific variant
     */
    public function index(Request $request, Product $product)
    {
        $variantId = $request->get('variant');
        $variant = null;

        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $product->id)
                ->first();
        }

        // Use simplified JSON-based attributes
        $productAttributes = $product->getProductAttributes();

        // Get variant-specific options if variant is specified
        $variantOptions = [];
        if ($variant) {
            $variantOptions = $variant->getVariantOptions();
        } elseif ($product->has_variants && $product->defaultVariant) {
            $variantOptions = $product->defaultVariant->getVariantOptions();
        }

        // Combine product attributes and variant options
        $allSpecs = array_merge($productAttributes, $variantOptions);

        // Convert to simplified format for API response
        $specifications = collect($allSpecs)->map(function ($value, $key) {
            return [
                'id' => $key,
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'code' => $key,
                'data_type' => 'text',
                'unit' => null,
                'scope' => in_array($key, ['color', 'storage', 'ram', 'size']) ? 'variant' : 'product',
                'description' => null,
                'raw_value' => $value,
                'formatted_value' => $value,
                'is_filterable' => true,
            ];
        })->values();
        
        return response()->json([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'specifications' => $specifications,
        ]);
    }
    
    /**
     * Get filterable specifications for product filtering
     */
    public function filterable(Request $request)
    {
        $categoryId = $request->get('category');
        $query = Product::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Get all products in the category/filter
        $products = $query->with(['variants'])->get();

        // Collect all unique filterable specifications from JSON attributes
        $filterableSpecs = collect();

        foreach ($products as $product) {
            // Add product-level attributes
            $productAttributes = $product->getProductAttributes();
            foreach ($productAttributes as $key => $value) {
                $this->addSpecToFilterableSimple($filterableSpecs, $key, $value, 'product');
            }

            // Add variant-level options
            foreach ($product->variants as $variant) {
                $variantOptions = $variant->getVariantOptions();
                foreach ($variantOptions as $key => $value) {
                    $this->addSpecToFilterableSimple($filterableSpecs, $key, $value, 'variant');
                }
            }
        }

        return response()->json([
            'filterable_specifications' => $filterableSpecs->values(),
        ]);
    }
    
    private function addSpecToFilterableSimple($collection, $key, $value, $scope)
    {
        if (!$collection->has($key)) {
            $collection[$key] = [
                'attribute_id' => $key,
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'code' => $key,
                'data_type' => 'text',
                'unit' => null,
                'scope' => $scope,
                'values' => collect(),
                'min_value' => null,
                'max_value' => null,
            ];
        }

        $item = $collection[$key];

        // Add unique values
        if ($value && !$item['values']->contains($value)) {
            $item['values']->push($value);
        }

        $collection[$key] = $item;
    }
}
