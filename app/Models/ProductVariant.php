<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price_cents',
        'compare_price_cents',
        'cost_price_cents',
        'stock_quantity',
        'stock_status',
        'low_stock_threshold',
        'track_inventory',
        'weight',
        'dimensions',
        'barcode',
        'images',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'images' => 'array',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($variant) {
            // Auto-generate basic SKU if not provided (will be updated after attributes are attached)
            if (empty($variant->sku)) {
                $variant->sku = $variant->generateBasicSku();
            }
        });

        static::saving(function ($variant) {
            // Ensure only one default variant per product
            if ($variant->is_default) {
                static::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the product this variant belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the attribute values for this variant
     */
    public function attributeValues()
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_variant_attributes')
            ->withPivot('product_attribute_id')
            ->with('attribute');
    }

    /**
     * Get the attributes for this variant (through attribute values)
     */
    public function attributes()
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_variant_attributes')
            ->withPivot('product_attribute_value_id');
    }

    /**
     * Get order items for this variant
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ===== PRICE ACCESSORS AND MUTATORS =====

    /**
     * Get the price in dollars from cents
     */
    public function getPriceAttribute()
    {
        return $this->price_cents ? $this->price_cents / 100 : 0;
    }

    /**
     * Set the price in cents from dollars
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['price_cents'] = $value ? round($value * 100) : 0;
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
     * Generate basic SKU without attributes (used during creation)
     */
    public function generateBasicSku()
    {
        $baseSku = $this->product ? $this->product->sku : 'PRODUCT';

        // Generate a basic unique SKU
        $sku = $baseSku . '-VAR';
        $counter = 1;
        while (static::where('sku', $sku)->where('id', '!=', $this->id ?? 0)->exists()) {
            $sku = $baseSku . '-VAR-' . $counter;
            $counter++;
        }

        return $sku;
    }

    /**
     * Generate SKU based on product and attributes
     */
    public function generateSku()
    {
        $baseSku = $this->product ? $this->product->sku : 'PRODUCT';
        $attributeParts = [];

        // Get attribute values for SKU generation
        if ($this->exists) {
            $attributeValues = $this->attributeValues()->with('attribute')->get();
            foreach ($attributeValues as $value) {
                $attributeParts[] = strtoupper($value->slug);
            }
        }

        $sku = $baseSku;
        if (!empty($attributeParts)) {
            $sku .= '-' . implode('-', $attributeParts);
        } else {
            // If no attributes, use a simple variant identifier
            $sku .= '-VAR';
        }

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
     * Regenerate SKU after attributes are attached
     */
    public function regenerateSku()
    {
        $newSku = $this->generateSku();
        if ($this->sku !== $newSku) {
            $this->sku = $newSku;
            $this->save();
        }
        return $this;
    }

    /**
     * Get the full variant name
     */
    public function getFullNameAttribute()
    {
        if ($this->name) {
            return $this->name;
        }

        $productName = $this->product ? $this->product->name : 'Product';
        $attributeValues = $this->attributeValues()->with('attribute')->get();

        if ($attributeValues->isEmpty()) {
            return $productName;
        }

        $attributeParts = $attributeValues->pluck('value')->toArray();
        return $productName . ' - ' . implode(' / ', $attributeParts);
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
     * Check if variant is on sale
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
     * Scope for active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for in stock variants
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    /**
     * Scope for default variants
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ===== SPECIFICATION RELATIONSHIPS =====

    /**
     * Get the variant-level specification values
     */
    public function specificationValues()
    {
        return $this->hasMany(VariantSpecificationValue::class, 'product_variant_id');
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
            ->join('specification_attributes', 'specification_attributes.id', '=', 'variant_specification_values.specification_attribute_id')
            ->orderBy('specification_attributes.sort_order')
            ->orderBy('specification_attributes.name');
    }

    /**
     * Get all specifications for this variant (product-level + variant-level)
     */
    public function getAllSpecifications()
    {
        $productSpecs = $this->product->specificationsWithAttributes()->get();
        $variantSpecs = $this->specificationsWithAttributes()->get();

        // Variant specs override product specs for the same attribute
        $allSpecs = $productSpecs->keyBy('specification_attribute_id');
        $variantSpecs->each(function ($spec) use ($allSpecs) {
            $allSpecs[$spec->specification_attribute_id] = $spec;
        });

        return $allSpecs->values();
    }

    /**
     * Set a variant-level specification value
     */
    public function setSpecificationValue($attributeId, $value)
    {
        $specValue = $this->specificationValues()
            ->where('specification_attribute_id', $attributeId)
            ->first();

        if (!$specValue) {
            $specValue = new VariantSpecificationValue([
                'product_variant_id' => $this->id,
                'specification_attribute_id' => $attributeId,
            ]);
        }

        $specValue->setValue($value);
        $specValue->save();

        return $specValue;
    }

    /**
     * Get a variant-level specification value
     */
    public function getSpecificationValue($attributeId)
    {
        return $this->specificationValues()
            ->where('specification_attribute_id', $attributeId)
            ->first();
    }
}
