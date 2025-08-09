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
        
        // Get product-level specifications
        $productSpecs = $product->specificationsWithAttributes()->get();
        
        // Get variant-level specifications if variant is specified
        $variantSpecs = collect();
        if ($variant) {
            $variantSpecs = $variant->specificationsWithAttributes()->get();
        } elseif ($product->has_variants && $product->defaultVariant) {
            $variantSpecs = $product->defaultVariant->specificationsWithAttributes()->get();
        }
        
        // Combine specifications, with variant specs overriding product specs
        $allSpecs = $productSpecs->keyBy('specification_attribute_id');
        $variantSpecs->each(function ($spec) use ($allSpecs) {
            $allSpecs[$spec->specification_attribute_id] = $spec;
        });
        
        $specifications = $allSpecs->values()
            ->sortBy(function ($spec) {
                return $spec->specificationAttribute->sort_order ?? 999;
            })
            ->map(function ($spec) {
                $attribute = $spec->specificationAttribute;
                
                return [
                    'id' => $spec->id,
                    'attribute_id' => $attribute->id,
                    'name' => $attribute->name,
                    'code' => $attribute->code,
                    'data_type' => $attribute->data_type,
                    'unit' => $attribute->unit,
                    'scope' => $attribute->scope,
                    'description' => $attribute->description,
                    'raw_value' => $spec->raw_value,
                    'formatted_value' => $spec->formatted_value,
                    'is_filterable' => $attribute->is_filterable,
                ];
            })
            ->filter(function ($spec) {
                return !empty($spec['formatted_value']);
            })
            ->values();
        
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
        $products = $query->with(['specificationsWithAttributes', 'variants.specificationsWithAttributes'])
            ->get();
        
        // Collect all unique filterable specifications
        $filterableSpecs = collect();
        
        foreach ($products as $product) {
            // Add product-level specs
            $productSpecs = $product->specificationsWithAttributes()
                ->where('specification_attributes.is_filterable', true)
                ->get();
            
            foreach ($productSpecs as $spec) {
                $this->addSpecToFilterable($filterableSpecs, $spec);
            }
            
            // Add variant-level specs
            foreach ($product->variants as $variant) {
                $variantSpecs = $variant->specificationsWithAttributes()
                    ->where('specification_attributes.is_filterable', true)
                    ->get();
                
                foreach ($variantSpecs as $spec) {
                    $this->addSpecToFilterable($filterableSpecs, $spec);
                }
            }
        }
        
        return response()->json([
            'filterable_specifications' => $filterableSpecs->values(),
        ]);
    }
    
    private function addSpecToFilterable($collection, $spec)
    {
        $attribute = $spec->specificationAttribute;
        $key = $attribute->code;
        
        if (!$collection->has($key)) {
            $collection[$key] = [
                'attribute_id' => $attribute->id,
                'name' => $attribute->name,
                'code' => $attribute->code,
                'data_type' => $attribute->data_type,
                'unit' => $attribute->unit,
                'values' => collect(),
                'min_value' => null,
                'max_value' => null,
            ];
        }
        
        $item = $collection[$key];
        
        if ($attribute->data_type === 'number' && $spec->value_number !== null) {
            $item['min_value'] = $item['min_value'] === null 
                ? $spec->value_number 
                : min($item['min_value'], $spec->value_number);
            $item['max_value'] = $item['max_value'] === null 
                ? $spec->value_number 
                : max($item['max_value'], $spec->value_number);
        } else {
            $value = $spec->formatted_value;
            if ($value && !$item['values']->contains($value)) {
                $item['values']->push($value);
            }
        }
        
        $collection[$key] = $item;
    }
}
