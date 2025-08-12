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
        // Core product fields
        'category_id',
        'brand_id',
        'name',
        'slug',
        'images',
        'description',
        'short_description',

        // Simple pricing (base price for variants)
        'price_cents',

        // Simple inventory (for non-variant products)
        'sku',
        'stock_quantity',

        // Simple variant support
        'has_variants',

        // Status fields
        'is_active',
        'is_featured',
        'on_sale',

        // SEO fields
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
        'variant_config' => 'array',
        'track_inventory' => 'boolean',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
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
            // Ensure price_cents is always set (required field)
            if ($product->isDirty('price') || !$product->exists) {
                $product->price_cents = round(($product->price ?? 0) * 100);
            }

            // Ensure compare_price_cents is set when compare_price is provided
            if (($product->isDirty('compare_price') || !$product->exists) && $product->compare_price !== null) {
                $product->compare_price_cents = round($product->compare_price * 100);
            }

            // Ensure cost_price_cents is set when cost_price is provided
            if (($product->isDirty('cost_price') || !$product->exists) && $product->cost_price !== null) {
                $product->cost_price_cents = round($product->cost_price * 100);
            }

            // Validate stock quantity
            if ($product->stock_quantity < 0) {
                throw new \InvalidArgumentException('Stock quantity cannot be negative');
            }
        });

        static::saved(function ($product) {
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

    /**
     * Validate stock configuration for this product
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validateStockConfiguration(): array
    {
        $errors = [];

        if ($this->has_variants) {
            // Products with variants should not track inventory at product level
            if ($this->track_inventory) {
                $errors[] = 'Products with variants should not track inventory at product level';
            }

            if ($this->stock_quantity > 0) {
                $errors[] = 'Products with variants should have zero product-level stock';
            }

            // Check if all active variants track inventory
            $variantsWithoutTracking = $this->variants()
                ->where('is_active', true)
                ->where('track_inventory', false)
                ->count();

            if ($variantsWithoutTracking > 0) {
                $errors[] = "Product has {$variantsWithoutTracking} active variants not tracking inventory";
            }
        } else {
            // Products without variants should track inventory at product level
            if (!$this->track_inventory) {
                $errors[] = 'Products without variants should track inventory at product level';
            }
        }

        return $errors;
    }

    /**
     * Check if stock configuration is valid
     *
     * @return bool
     */
    public function hasValidStockConfiguration(): bool
    {
        return empty($this->validateStockConfiguration());
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
     * Get the effective stock quantity (uses unified InventoryService)
     * @deprecated Use InventoryService::getTotalStock() instead
     */
    public function getEffectiveStockQuantityAttribute()
    {
        return $this->getTotalStock();
    }

    /**
     * Get the effective stock status (uses unified InventoryService)
     * @deprecated Use InventoryService::getStockStatus() instead
     */
    public function getEffectiveStockStatusAttribute()
    {
        return $this->getStockStatus();
    }

    /**
     * Check if product has any variants in stock (uses unified InventoryService)
     * @deprecated Use InventoryService::hasStock() instead
     */
    public function getHasStockAttribute()
    {
        return $this->hasStock();
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
                'formatted' => '$' . number_format($this->price ?? 0, 2)
            ];
        }

        try {
            $lowest = $this->lowest_price ?? 0;
            $highest = $this->highest_price ?? 0;

            return [
                'min' => $lowest,
                'max' => $highest,
                'formatted' => $lowest === $highest
                    ? '$' . number_format($lowest, 2)
                    : '$' . number_format($lowest, 2) . ' - $' . number_format($highest, 2)
            ];
        } catch (Exception $e) {
            return [
                'min' => $this->price ?? 0,
                'max' => $this->price ?? 0,
                'formatted' => '$' . number_format($this->price ?? 0, 2)
            ];
        }
    }



    // ========================================
    // SIMPLIFIED VARIANT CREATION METHODS
    // ========================================

    /**
     * Create a variant with JSON options (simplified approach)
     *
     * @param array $options JSON options like ['Color' => 'Black', 'Storage' => '256GB']
     * @param int|null $overridePrice Price in cents, null to use product base price
     * @param int $stockQuantity Stock quantity for this variant
     * @param string|null $imageUrl Optional image URL for this variant
     * @return ProductVariant
     */
    public function createSimpleVariant(array $options, ?int $overridePrice = null, int $stockQuantity = 10, ?string $imageUrl = null)
    {
        // Check if variant with these options already exists
        $existingVariant = $this->findVariantByOptions($options);
        if ($existingVariant) {
            return $existingVariant;
        }

        // Generate SKU from options
        $sku = $this->generateSkuFromOptions($options);

        // Create new variant with simplified approach
        $variant = $this->variants()->create([
            'sku' => $sku,
            'options' => $options,
            'override_price' => $overridePrice,
            'stock_quantity' => $stockQuantity,
            'image_url' => $imageUrl,
            'stock_status' => 'in_stock',
            'low_stock_threshold' => 5,
            'track_inventory' => true,
            'is_active' => true,
            'is_default' => $this->variants()->count() === 0, // First variant is default
        ]);

        return $variant;
    }

    /**
     * Generate SKU from JSON options
     */
    protected function generateSkuFromOptions(array $options): string
    {
        $baseSku = $this->brand ? strtoupper($this->brand->slug) : 'PROD';
        $productSku = strtoupper(str_replace(' ', '', $this->name));

        $optionParts = [];
        foreach ($options as $key => $value) {
            $optionParts[] = strtoupper(str_replace(' ', '', $value));
        }

        return $baseSku . '-' . $productSku . '-' . implode('-', $optionParts);
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
     * Uses unified InventoryService for consistency
     */
    public function getTotalStock()
    {
        return \App\Services\InventoryService::getTotalStock($this);
    }

    /**
     * Check if product has any stock available
     * Uses unified InventoryService for consistency
     */
    public function hasStock()
    {
        return \App\Services\InventoryService::hasStock($this);
    }

    /**
     * Get stock status for this product
     * Uses unified InventoryService for consistency
     */
    public function getStockStatus()
    {
        return \App\Services\InventoryService::getStockStatus($this);
    }

    /**
     * Check if product is low stock
     * Uses unified InventoryService for consistency
     */
    public function isLowStock()
    {
        return \App\Services\InventoryService::isLowStock($this);
    }

    /**
     * Get calculated stock status (accessor)
     * This makes stock_status always calculated, never manually set
     */
    public function getStockStatusAttribute()
    {
        return $this->getStockStatus();
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





    /**
     * Get variant by SKU (simplified lookup)
     */
    public function getVariantBySku($sku)
    {
        return $this->variants()->where('sku', $sku)->first();
    }

    // ========================================
    // JSON VARIANT HELPER METHODS (Phase 4)
    // ========================================

    /**
     * Get variant configuration from JSON
     */
    public function getVariantConfiguration(): array
    {
        return $this->variant_config ?? [];
    }

    /**
     * Get product attributes from JSON
     */
    public function getProductAttributes(): array
    {
        return $this->attributes ?? [];
    }

    /**
     * Check if product has been migrated to JSON system
     */
    public function isMigratedToJson(): bool
    {
        return $this->migrated_to_json === true;
    }

    /**
     * Get variant count from configuration
     */
    public function getVariantCount(): int
    {
        $config = $this->getVariantConfiguration();
        return $config['variant_count'] ?? $this->variants()->count();
    }







    // ===========================================
    // SIMPLE VARIANT METHODS FOR COLOR+STORAGE
    // ===========================================

    /**
     * Add a simple variant with Color and Storage
     */
    public function addVariant(string $color, string $storage, int $priceModifier = 0, int $stock = 10): ProductVariant
    {
        $basePrice = $this->price_cents ?? 0;
        $finalPrice = $priceModifier > 0 ? $basePrice + $priceModifier : ($basePrice > 0 ? $basePrice : null);

        return $this->variants()->create([
            'options' => ['Color' => $color, 'Storage' => $storage],
            'override_price' => $finalPrice,
            'stock_quantity' => $stock,
            'sku' => $this->generateVariantSku($color, $storage),
            'is_active' => true
        ]);
    }

    /**
     * Get available colors for this product
     */
    public function getAvailableColors(): array
    {
        if (!$this->has_variants) {
            return [];
        }

        return $this->variants()
            ->where('is_active', true)
            ->get()
            ->pluck('options')
            ->map(fn($options) => $options['Color'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get available storage options for this product
     */
    public function getAvailableStorage(): array
    {
        if (!$this->has_variants) {
            return [];
        }

        return $this->variants()
            ->where('is_active', true)
            ->get()
            ->pluck('options')
            ->map(fn($options) => $options['Storage'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Find variant by color and storage
     */
    public function findVariant(string $color, string $storage): ?ProductVariant
    {
        return $this->variants()
            ->where('is_active', true)
            ->get()
            ->first(function ($variant) use ($color, $storage) {
                $options = $variant->options ?? [];
                return ($options['Color'] ?? null) === $color &&
                       ($options['Storage'] ?? null) === $storage;
            });
    }

    /**
     * Generate SKU for variant
     */
    private function generateVariantSku(string $color, string $storage): string
    {
        $colorCode = strtoupper(substr($color, 0, 3));
        $storageCode = str_replace('GB', '', $storage);
        return "{$this->sku}-{$colorCode}-{$storageCode}";
    }
}
