<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $table = "products";

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'images',
        'description',
        'short_description',
        'price',
        'price_cents',
        'compare_price_cents',
        'cost_price_cents',
        'sku',
        'stock_quantity',
        'stock_status',
        'low_stock_threshold',
        'track_inventory',
        'has_variants',
        'variant_type',
        'variant_attributes',
        'attributes', // New JSON column for simplified attributes
        'is_active',
        'is_featured',
        'in_stock',
        'on_sale',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */

    protected $casts = [
        'images' => 'array',
        'variant_attributes' => 'array',
        'attributes' => 'array', // New JSON column for simplified attributes
        'track_inventory' => 'boolean',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'in_stock' => 'boolean',
        'on_sale' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Auto-generate SKU if not provided
            if (empty($product->sku)) {
                $product->sku = $product->generateSku();
            }
        });

        static::saving(function ($product) {
            // Ensure price_cents is set when price is provided
            if (($product->isDirty('price') || !$product->exists) && $product->price !== null) {
                $product->price_cents = round($product->price * 100);
            }

            // Ensure compare_price_cents is set when compare_price is provided
            if (($product->isDirty('compare_price') || !$product->exists) && $product->compare_price !== null) {
                $product->compare_price_cents = round($product->compare_price * 100);
            }

            // Ensure cost_price_cents is set when cost_price is provided
            if (($product->isDirty('cost_price') || !$product->exists) && $product->cost_price !== null) {
                $product->cost_price_cents = round($product->cost_price * 100);
            }
        });

        static::saved(function ($product) {
            // Auto-generate variants when has_variants is enabled and variant_attributes are set
            if ($product->has_variants &&
                !empty($product->variant_attributes) &&
                $product->variants()->count() === 0) {

                // Generate variants based on attribute combinations
                $product->generateVariants();
            }

            // If has_variants was disabled, clean up any existing variants
            if (!$product->has_variants && $product->wasChanged('has_variants')) {
                $product->variants()->delete();
            }
        });
    }

    /**
     * Get the category that owns the product.
     */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that owns the product.
     */

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the Order Items for this product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the variants for this product.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get active variants for this product.
     */
    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true)->orderBy('is_default', 'desc');
    }

    /**
     * Get the default variant for this product.
     */
    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Get the attributes used by this product's variants (relationship).
     */
    public function productAttributes()
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_variant_attributes', 'product_id', 'product_attribute_id')
            ->distinct();
    }

    /**
     * Get the attributes used by this product's variants (collection method).
     */
    public function getProductAttributesCollection()
    {
        if (!$this->variant_attributes) {
            return collect();
        }

        return ProductAttribute::whereIn('id', $this->variant_attributes)
            ->with('activeValues')
            ->ordered()
            ->get();
    }

    // ===== PRICE ACCESSORS AND MUTATORS =====

    /**
     * Get the price in dollars from cents
     */
    public function getPriceAttribute($value)
    {
        // If we have price_cents, use that; otherwise use the original price field
        return $this->price_cents ? $this->price_cents / 100 : $value;
    }

    /**
     * Set the price in cents from dollars
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['price_cents'] = $value ? round($value * 100) : 0;
        $this->attributes['price'] = $value; // Keep the original field in sync
    }

    /**
     * Get the compare price in dollars from cents
     */
    public function getComparePriceAttribute()
    {
        return $this->compare_price_cents ? $this->compare_price_cents / 100 : null;
    }

    /**
     * Set the compare price in cents from dollars
     */
    public function setComparePriceAttribute($value)
    {
        $this->attributes['compare_price_cents'] = $value ? round($value * 100) : null;
    }

    /**
     * Get the cost price in dollars from cents
     */
    public function getCostPriceAttribute()
    {
        return $this->cost_price_cents ? $this->cost_price_cents / 100 : null;
    }

    /**
     * Set the cost price in cents from dollars
     */
    public function setCostPriceAttribute($value)
    {
        $this->attributes['cost_price_cents'] = $value ? round($value * 100) : null;
    }

    // ===== BUSINESS LOGIC METHODS =====

    /**
     * Generate SKU for the product
     */
    public function generateSku()
    {
        // Try to use brand code + product slug
        $brandCode = $this->brand ? strtoupper(substr($this->brand->slug, 0, 3)) : 'PRD';

        // Use slug if available, otherwise create from name
        $productIdentifier = $this->slug ?: Str::slug($this->name);

        // Clean and format the product identifier
        $productSlug = strtoupper($productIdentifier);
        $productSlug = preg_replace('/[^A-Z0-9]/', '', $productSlug);

        // Limit to reasonable length (10 characters max)
        $productSlug = substr($productSlug, 0, 10) ?: 'PRODUCT';

        $sku = $brandCode . '-' . $productSlug;

        // Ensure uniqueness
        $originalSku = $sku;
        $counter = 1;
        while (static::where('sku', $sku)->where('id', '!=', $this->id ?? 0)->exists()) {
            $sku = $originalSku . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    /**
     * Regenerate SKU for the product
     */
    public function regenerateSku()
    {
        $newSku = $this->generateSku();
        if ($this->sku !== $newSku) {
            $this->sku = $newSku;
            $this->save();

            // Update all variant SKUs that depend on this product SKU
            $this->variants()->each(function ($variant) {
                $variant->regenerateSku();
            });
        }
        return $this;
    }

    /**
     * Calculate profit margin percentage
     */
    public function getProfitMarginAttribute()
    {
        if (!$this->cost_price_cents || !$this->price_cents) {
            return null;
        }

        $profit = $this->price_cents - $this->cost_price_cents;
        return round(($profit / $this->price_cents) * 100, 2);
    }

    /**
     * Calculate profit amount in dollars
     */
    public function getProfitAmountAttribute()
    {
        if (!$this->cost_price_cents || !$this->price_cents) {
            return null;
        }

        return ($this->price_cents - $this->cost_price_cents) / 100;
    }

    /**
     * Calculate discount percentage from compare price
     */
    public function getDiscountPercentageAttribute()
    {
        if (!$this->compare_price_cents || !$this->price_cents) {
            return null;
        }

        $discount = $this->compare_price_cents - $this->price_cents;
        return round(($discount / $this->compare_price_cents) * 100, 2);
    }

    /**
     * Check if product is on sale (has compare price higher than current price)
     */
    public function getIsOnSaleAttribute()
    {
        return $this->compare_price_cents && $this->price_cents && $this->compare_price_cents > $this->price_cents;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute()
    {
        return $this->track_inventory && $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Update stock status based on quantity
     */
    public function updateStockStatus()
    {
        if (!$this->track_inventory) {
            $this->stock_status = 'in_stock';
            return;
        }

        if ($this->stock_quantity <= 0) {
            $this->stock_status = 'out_of_stock';
        } else {
            $this->stock_status = 'in_stock';
        }

        $this->save();
    }

    /**
     * Reduce stock quantity
     */
    public function reduceStock($quantity)
    {
        if (!$this->track_inventory) {
            return true;
        }

        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->stock_quantity -= $quantity;
        $this->updateStockStatus();

        return true;
    }

    /**
     * Increase stock quantity
     */
    public function increaseStock($quantity)
    {
        if (!$this->track_inventory) {
            return;
        }

        $this->stock_quantity += $quantity;
        $this->updateStockStatus();
    }

    // ===== VARIANT-RELATED METHODS =====

    /**
     * Get the effective price (from default variant or product) - SIMPLIFIED
     */
    public function getEffectivePriceAttribute()
    {
        try {
            if ($this->has_variants && $this->defaultVariant) {
                return $this->defaultVariant->final_price_in_dollars ?? $this->price ?? 0;
            }

            return $this->price ?? 0;
        } catch (Exception $e) {
            return $this->price ?? 0;
        }
    }

    /**
     * Get the effective price in cents (from default variant or product) - SIMPLIFIED
     */
    public function getEffectivePriceCentsAttribute()
    {
        if ($this->has_variants && $this->defaultVariant) {
            return $this->defaultVariant->final_price;
        }

        return $this->price_cents;
    }

    /**
     * Get the effective stock quantity (sum of all variants or product stock)
     */
    public function getEffectiveStockQuantityAttribute()
    {
        if ($this->has_variants) {
            return $this->activeVariants()->sum('stock_quantity');
        }

        return $this->stock_quantity;
    }

    /**
     * Get the effective stock status
     */
    public function getEffectiveStockStatusAttribute()
    {
        if ($this->has_variants) {
            $inStockVariants = $this->activeVariants()->where('stock_status', 'in_stock')->count();
            $totalVariants = $this->activeVariants()->count();

            if ($inStockVariants === 0) {
                return 'out_of_stock';
            } elseif ($inStockVariants < $totalVariants) {
                return 'partial_stock';
            } else {
                return 'in_stock';
            }
        }

        return $this->stock_status;
    }

    /**
     * Check if product has any variants in stock
     */
    public function getHasStockAttribute()
    {
        if ($this->has_variants) {
            return $this->activeVariants()->where('stock_status', 'in_stock')->exists();
        }

        return $this->stock_status === 'in_stock';
    }

    /**
     * Get the lowest price among variants or product price - SIMPLIFIED
     */
    public function getLowestPriceAttribute()
    {
        try {
            if ($this->has_variants && $this->activeVariants) {
                // Use simplified pricing logic: check both override_price and base price
                $variants = $this->activeVariants()->get();
                $lowestPrice = $variants->map(function ($variant) {
                    return $variant->final_price; // Uses override_price ?? product.price_cents
                })->min();

                return $lowestPrice ? $lowestPrice / 100 : ($this->price ?? 0);
            }

            return $this->price ?? 0;
        } catch (Exception $e) {
            return $this->price ?? 0;
        }
    }

    /**
     * Get the highest price among variants or product price
     */
    public function getHighestPriceAttribute()
    {
        try {
            if ($this->has_variants && $this->activeVariants) {
                $highestPriceCents = $this->activeVariants()->max('price_cents');
                return $highestPriceCents ? $highestPriceCents / 100 : ($this->price ?? 0);
            }

            return $this->price ?? 0;
        } catch (Exception $e) {
            return $this->price ?? 0;
        }
    }

    /**
     * Get price range for display
     */
    public function getPriceRangeAttribute()
    {
        if (!$this->has_variants) {
            return [
                'min' => $this->price ?? 0,
                'max' => $this->price ?? 0,
                'formatted' => '₹' . number_format($this->price ?? 0, 2)
            ];
        }

        try {
            $lowest = $this->lowest_price ?? 0;
            $highest = $this->highest_price ?? 0;

            return [
                'min' => $lowest,
                'max' => $highest,
                'formatted' => $lowest === $highest
                    ? '₹' . number_format($lowest, 2)
                    : '₹' . number_format($lowest, 2) . ' - ₹' . number_format($highest, 2)
            ];
        } catch (Exception $e) {
            return [
                'min' => $this->price ?? 0,
                'max' => $this->price ?? 0,
                'formatted' => '₹' . number_format($this->price ?? 0, 2)
            ];
        }
    }

    /**
     * Create variants from attribute combinations
     */
    public function generateVariants($attributeCombinations = null)
    {
        if (!$this->has_variants || !$this->variant_attributes) {
            return false;
        }

        // If no combinations provided, generate all possible combinations
        if (!$attributeCombinations) {
            $attributeCombinations = $this->generateAttributeCombinations();
        }

        foreach ($attributeCombinations as $combination) {
            $this->createVariantFromCombination($combination);
        }

        return true;
    }

    /**
     * Generate all possible attribute combinations
     */
    protected function generateAttributeCombinations()
    {
        $attributes = $this->getProductAttributesCollection();
        $combinations = [[]];

        foreach ($attributes as $attribute) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($attribute->activeValues as $value) {
                    $newCombination = $combination;
                    $newCombination[$attribute->id] = $value->id;
                    $newCombinations[] = $newCombination;
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    /**
     * Create a variant from attribute combination
     */
    protected function createVariantFromCombination($combination)
    {
        // Check if variant already exists
        $existingVariant = $this->variants()
            ->whereHas('attributeValues', function ($query) use ($combination) {
                $query->whereIn('product_attribute_value_id', array_values($combination));
            }, '=', count($combination))
            ->first();

        if ($existingVariant) {
            return $existingVariant;
        }

        // Calculate variant price based on base price + attribute value modifiers
        $variantPriceCents = $this->price_cents;
        $variantComparePriceCents = $this->compare_price_cents;
        $variantCostPriceCents = $this->cost_price_cents;

        foreach ($combination as $attributeId => $valueId) {
            $attributeValue = ProductAttributeValue::find($valueId);
            if ($attributeValue && $attributeValue->price_modifier_cents) {
                $variantPriceCents += $attributeValue->price_modifier_cents;

                // Also adjust compare price if it exists
                if ($variantComparePriceCents) {
                    $variantComparePriceCents += $attributeValue->price_modifier_cents;
                }
            }
        }

        // Create new variant
        $variant = $this->variants()->create([
            'price_cents' => $variantPriceCents,
            'compare_price_cents' => $variantComparePriceCents,
            'cost_price_cents' => $variantCostPriceCents,
            'stock_quantity' => $this->stock_quantity > 0 ? $this->stock_quantity : 10, // Default to 10 if product stock is 0
            'stock_status' => $this->stock_status ?: 'in_stock', // Default to in_stock if not set
            'low_stock_threshold' => $this->low_stock_threshold ?: 5,
            'track_inventory' => $this->track_inventory ?? true,
            'is_active' => true,
            'is_default' => $this->variants()->count() === 0, // First variant is default
        ]);

        // Attach attribute values
        foreach ($combination as $attributeId => $valueId) {
            $variant->attributeValues()->attach($valueId, [
                'product_attribute_id' => $attributeId
            ]);
        }

        // Regenerate SKU after attributes are attached
        $variant->sku = $variant->generateSku();
        $variant->save();

        // Convert attributes to JSON options for simplified system
        $variant->convertAttributesToOptions();

        return $variant;
    }

    // ========================================
    // SIMPLIFIED VARIANT METHODS (JSON-based)
    // ========================================

    /**
     * Find variant by selected options (simplified approach)
     */
    public function findVariantByOptions($selectedOptions)
    {
        $variants = $this->variants()->get();

        // First, try to find an exact match
        $exactMatch = $variants->first(function ($variant) use ($selectedOptions) {
            $variantOptions = $variant->options ?? [];
            return $variantOptions === $selectedOptions;
        });

        if ($exactMatch) {
            return $exactMatch;
        }

        // If no exact match, find the best partial match
        // (variant that contains all selected options, even if it has more)
        $partialMatch = $variants->first(function ($variant) use ($selectedOptions) {
            $variantOptions = $variant->options ?? [];

            // Check if all selected options match the variant options
            foreach ($selectedOptions as $key => $value) {
                if (!isset($variantOptions[$key]) || $variantOptions[$key] !== $value) {
                    return false;
                }
            }

            return true; // All selected options match
        });

        return $partialMatch;
    }

    /**
     * Get available options for dropdowns (simplified approach)
     */
    public function getAvailableOptions()
    {
        $options = [];
        $variants = $this->variants()->get(); // Use method call instead of property

        foreach ($variants as $variant) {
            foreach ($variant->options ?? [] as $key => $value) {
                if (!isset($options[$key])) {
                    $options[$key] = [];
                }
                if (!in_array($value, $options[$key])) {
                    $options[$key][] = $value;
                }
            }
        }

        return $options;
    }

    /**
     * Get price range from variants (simplified approach)
     */
    public function getPriceRange()
    {
        if (!$this->has_variants) {
            return [
                'min' => $this->price_cents / 100,
                'max' => $this->price_cents / 100
            ];
        }

        $variants = $this->variants()->get(); // Use method call
        if ($variants->isEmpty()) {
            return [
                'min' => $this->price_cents / 100,
                'max' => $this->price_cents / 100
            ];
        }

        $prices = $variants->pluck('price_cents')->map(fn($p) => $p / 100);
        return [
            'min' => $prices->min(),
            'max' => $prices->max()
        ];
    }

    /**
     * Get total stock across all variants
     */
    public function getTotalStock()
    {
        if (!$this->has_variants) {
            return $this->stock_quantity;
        }

        $variantStock = $this->variants()->sum('stock_quantity');

        // Fallback to product stock if no variants exist (edge case)
        if ($variantStock === 0 && $this->variants()->count() === 0) {
            return $this->stock_quantity;
        }

        return $variantStock;
    }

    /**
     * Check if product has any stock available
     */
    public function hasStock()
    {
        if (!$this->has_variants) {
            return $this->stock_quantity > 0;
        }

        $variantStock = $this->variants()->sum('stock_quantity');

        // Fallback to product stock if no variants exist (edge case)
        if ($variantStock === 0 && $this->variants()->count() === 0) {
            return $this->stock_quantity > 0;
        }

        return $variantStock > 0;
    }

    /**
     * Get the cheapest variant - SIMPLIFIED PRICING
     */
    public function getCheapestVariant()
    {
        if (!$this->has_variants) {
            return null;
        }

        $variants = $this->activeVariants()->get();
        if ($variants->isEmpty()) {
            return null;
        }

        // Sort by final price (which uses override_price ?? product.price_cents)
        return $variants->sortBy('final_price')->first();
    }

    // ========================================
    // SIMPLIFIED PRICING METHODS
    // ========================================

    /**
     * Get price for selected variant using simplified pricing logic
     * This replaces the complex calculateDynamicPrice method
     */
    public function getPriceForVariant($variantId = null, $selectedOptions = [])
    {
        // If variant ID is provided, use that variant's final price
        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) {
                $basePrice = $this->price_cents;
                $finalPrice = $variant->final_price;
                $modifier = $finalPrice - $basePrice;

                return [
                    'price_cents' => $finalPrice,
                    'price' => $variant->final_price_in_dollars,
                    'base_price_cents' => $basePrice,
                    'base_price' => $basePrice / 100,
                    'total_modifier_cents' => $modifier,
                    'total_modifier' => $modifier / 100,
                    'variant' => $variant,
                    'has_override' => $variant->hasPriceOverride(),
                    'modifiers' => [] // Empty for simplified system
                ];
            }
        }

        // If options are provided, find matching variant
        if (!empty($selectedOptions)) {
            $variant = $this->findVariantByOptions($selectedOptions);
            if ($variant) {
                $basePrice = $this->price_cents;
                $finalPrice = $variant->final_price;
                $modifier = $finalPrice - $basePrice;

                return [
                    'price_cents' => $finalPrice,
                    'price' => $variant->final_price_in_dollars,
                    'base_price_cents' => $basePrice,
                    'base_price' => $basePrice / 100,
                    'total_modifier_cents' => $modifier,
                    'total_modifier' => $modifier / 100,
                    'variant' => $variant,
                    'has_override' => $variant->hasPriceOverride(),
                    'modifiers' => [] // Empty for simplified system
                ];
            }
        }

        // Fallback to product base price
        return [
            'price_cents' => $this->price_cents,
            'price' => $this->price,
            'base_price_cents' => $this->price_cents,
            'base_price' => $this->price,
            'total_modifier_cents' => 0,
            'total_modifier' => 0,
            'variant' => null,
            'has_override' => false,
            'modifiers' => []
        ];
    }



    // ========================================
    // LEGACY DYNAMIC PRICING METHODS (DEPRECATED)
    // ========================================

    /**
     * Calculate dynamic price based on selected options using Option B approach
     * (base price + sum of attribute modifiers)
     */
    public function calculateDynamicPrice($selectedOptions = [])
    {
        $basePriceCents = $this->price_cents;
        $totalModifierCents = 0;

        if (empty($selectedOptions)) {
            return [
                'price_cents' => $basePriceCents,
                'price' => $basePriceCents / 100,
                'modifiers' => []
            ];
        }

        $modifiers = [];

        // Get all attribute values that match the selected options
        foreach ($selectedOptions as $attributeName => $selectedValue) {
            $attribute = ProductAttribute::where('name', $attributeName)->first();
            if (!$attribute) {
                continue;
            }

            $attributeValue = $attribute->values()
                ->where('value', $selectedValue)
                ->first();

            if ($attributeValue && $attributeValue->price_modifier_cents) {
                $totalModifierCents += $attributeValue->price_modifier_cents;
                $modifiers[] = [
                    'attribute' => $attributeName,
                    'value' => $selectedValue,
                    'modifier_cents' => $attributeValue->price_modifier_cents,
                    'modifier' => $attributeValue->price_modifier_cents / 100
                ];
            }
        }

        $finalPriceCents = $basePriceCents + $totalModifierCents;

        return [
            'price_cents' => $finalPriceCents,
            'price' => $finalPriceCents / 100,
            'base_price_cents' => $basePriceCents,
            'base_price' => $basePriceCents / 100,
            'total_modifier_cents' => $totalModifierCents,
            'total_modifier' => $totalModifierCents / 100,
            'modifiers' => $modifiers
        ];
    }

    /**
     * Calculate dynamic compare price based on selected options
     */
    public function calculateDynamicComparePrice($selectedOptions = [])
    {
        if (!$this->compare_price_cents) {
            return null;
        }

        $baseComparePriceCents = $this->compare_price_cents;
        $totalModifierCents = 0;

        // Apply the same modifiers to compare price
        foreach ($selectedOptions as $attributeName => $selectedValue) {
            $attribute = ProductAttribute::where('name', $attributeName)->first();
            if (!$attribute) {
                continue;
            }

            $attributeValue = $attribute->values()
                ->where('value', $selectedValue)
                ->first();

            if ($attributeValue && $attributeValue->price_modifier_cents) {
                $totalModifierCents += $attributeValue->price_modifier_cents;
            }
        }

        $finalComparePriceCents = $baseComparePriceCents + $totalModifierCents;

        return [
            'compare_price_cents' => $finalComparePriceCents,
            'compare_price' => $finalComparePriceCents / 100
        ];
    }



    /**
     * Get available price modifiers for frontend
     */
    public function getPriceModifiers()
    {
        $modifiers = [];

        // Get all attributes used by this product's variants
        $attributeNames = collect($this->getAvailableOptions())->keys();

        foreach ($attributeNames as $attributeName) {
            $attribute = ProductAttribute::where('name', $attributeName)->first();
            if (!$attribute) {
                continue;
            }

            $attributeModifiers = [];
            foreach ($attribute->values as $value) {
                if ($value->price_modifier_cents !== 0) {
                    $attributeModifiers[$value->value] = [
                        'modifier_cents' => $value->price_modifier_cents,
                        'modifier' => $value->price_modifier_cents / 100
                    ];
                }
            }

            if (!empty($attributeModifiers)) {
                $modifiers[$attributeName] = $attributeModifiers;
            }
        }

        return $modifiers;
    }

    /**
     * Get variant by SKU (simplified lookup)
     */
    public function getVariantBySku($sku)
    {
        return $this->variants()->where('sku', $sku)->first();
    }

    // ===== SPECIFICATION RELATIONSHIPS =====

    /**
     * Get the product-level specification values
     */
    public function specificationValues()
    {
        return $this->hasMany(ProductSpecificationValue::class);
    }

    /**
     * Get specification values with their attributes
     */
    public function specificationsWithAttributes()
    {
        return $this->specificationValues()
            ->with(['specificationAttribute', 'specificationAttributeOption'])
            ->whereHas('specificationAttribute', function ($query) {
                $query->where('is_active', true);
            })
            ->join('specification_attributes', 'specification_attributes.id', '=', 'product_specification_values.specification_attribute_id')
            ->orderBy('specification_attributes.sort_order')
            ->orderBy('specification_attributes.name');
    }

    /**
     * Get all specifications for this product (product-level + default variant-level)
     */
    public function getAllSpecifications()
    {
        $productSpecs = $this->specificationsWithAttributes()->get();

        // If product has variants, get default variant specs
        $variantSpecs = collect();
        if ($this->has_variants && $this->defaultVariant) {
            $variantSpecs = $this->defaultVariant->specificationsWithAttributes()->get();
        }

        return $productSpecs->concat($variantSpecs)->unique('specification_attribute_id');
    }

    /**
     * Set a product-level specification value
     */
    public function setSpecificationValue($attributeId, $value)
    {
        $specValue = $this->specificationValues()
            ->where('specification_attribute_id', $attributeId)
            ->first();

        if (!$specValue) {
            $specValue = new ProductSpecificationValue([
                'product_id' => $this->id,
                'specification_attribute_id' => $attributeId,
            ]);
        }

        $specValue->setValue($value);
        $specValue->save();

        return $specValue;
    }

    /**
     * Get a product-level specification value
     */
    public function getSpecificationValue($attributeId)
    {
        return $this->specificationValues()
            ->where('specification_attribute_id', $attributeId)
            ->first();
    }
}
