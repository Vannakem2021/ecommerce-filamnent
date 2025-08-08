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
        return $this->hasMany(ProductVariant::class)->orderBy('is_default', 'desc');
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
     * Get the effective price (from default variant or product)
     */
    public function getEffectivePriceAttribute()
    {
        if ($this->has_variants && $this->defaultVariant) {
            return $this->defaultVariant->price;
        }

        return $this->price;
    }

    /**
     * Get the effective price in cents (from default variant or product)
     */
    public function getEffectivePriceCentsAttribute()
    {
        if ($this->has_variants && $this->defaultVariant) {
            return $this->defaultVariant->price_cents;
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
     * Get the lowest price among variants or product price
     */
    public function getLowestPriceAttribute()
    {
        if ($this->has_variants) {
            $lowestPriceCents = $this->activeVariants()->min('price_cents');
            return $lowestPriceCents ? $lowestPriceCents / 100 : 0;
        }

        return $this->price;
    }

    /**
     * Get the highest price among variants or product price
     */
    public function getHighestPriceAttribute()
    {
        if ($this->has_variants) {
            $highestPriceCents = $this->activeVariants()->max('price_cents');
            return $highestPriceCents ? $highestPriceCents / 100 : 0;
        }

        return $this->price;
    }

    /**
     * Get price range for display
     */
    public function getPriceRangeAttribute()
    {
        if (!$this->has_variants) {
            return null;
        }

        $lowest = $this->lowest_price;
        $highest = $this->highest_price;

        if ($lowest === $highest) {
            return null; // All variants have same price
        }

        return [
            'min' => $lowest,
            'max' => $highest,
            'formatted' => '₹' . number_format($lowest, 2) . ' - ₹' . number_format($highest, 2)
        ];
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

        // Create new variant
        $variant = $this->variants()->create([
            'price_cents' => $this->price_cents,
            'compare_price_cents' => $this->compare_price_cents,
            'cost_price_cents' => $this->cost_price_cents,
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status,
            'low_stock_threshold' => $this->low_stock_threshold,
            'track_inventory' => $this->track_inventory,
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

        return $variant;
    }
}
