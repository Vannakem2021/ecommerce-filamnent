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
        'price_cents',
        'override_price', // Simplified pricing override
        'stock_quantity',
        'options', // JSON column for variant choices
        'image_url', // Variant-specific image
        'name',
        'compare_price_cents',
        'cost_price_cents',
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
        'options' => 'array', // JSON cast for variant options
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

    // ===== SIMPLIFIED PRICING METHODS =====

    /**
     * Get the final price using simplified pricing logic
     * Returns override_price if set, otherwise falls back to product base price
     */
    public function getFinalPriceAttribute()
    {
        return $this->override_price ?? $this->product->price_cents;
    }

    /**
     * Get the final price in dollars
     */
    public function getFinalPriceInDollarsAttribute()
    {
        return $this->final_price / 100;
    }

    /**
     * Get the override price in dollars from cents
     */
    public function getOverridePriceInDollarsAttribute()
    {
        return $this->override_price ? $this->override_price / 100 : null;
    }

    /**
     * Set the override price in cents from dollars
     */
    public function setOverridePriceInDollarsAttribute($value)
    {
        $this->attributes['override_price'] = $value ? round($value * 100) : null;
    }

    /**
     * Check if this variant has a price override
     */
    public function hasPriceOverride()
    {
        return !is_null($this->override_price);
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
     * Generate SKU based on product and JSON options
     */
    public function generateSku()
    {
        $baseSku = $this->product ? $this->product->sku : 'PRODUCT';
        $optionParts = [];

        // Get option values for SKU generation from JSON
        if ($this->options) {
            foreach ($this->options as $key => $value) {
                $optionParts[] = strtoupper(substr($value, 0, 3)); // First 3 chars
            }
        }

        $sku = $baseSku;
        if (!empty($optionParts)) {
            $sku .= '-' . implode('-', $optionParts);
        } else {
            // If no options, use a simple variant identifier
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
     * Get the full variant name using JSON options
     */
    public function getFullNameAttribute()
    {
        if ($this->name) {
            return $this->name;
        }

        $productName = $this->product ? $this->product->name : 'Product';
        
        if ($this->options && !empty($this->options)) {
            $optionParts = [];
            foreach ($this->options as $key => $value) {
                $optionParts[] = $value;
            }
            return $productName . ' - ' . implode(' / ', $optionParts);
        }

        return $productName;
    }

    /**
     * Calculate profit margin percentage - SIMPLIFIED PRICING
     */
    public function getProfitMarginAttribute()
    {
        if (!$this->cost_price_cents || !$this->final_price) {
            return null;
        }

        $profit = $this->final_price - $this->cost_price_cents;
        return round(($profit / $this->final_price) * 100, 2);
    }

    /**
     * Calculate profit amount in dollars - SIMPLIFIED PRICING
     */
    public function getProfitAmountAttribute()
    {
        if (!$this->cost_price_cents || !$this->final_price) {
            return null;
        }

        return ($this->final_price - $this->cost_price_cents) / 100;
    }

    /**
     * Calculate discount percentage from compare price - SIMPLIFIED PRICING
     */
    public function getDiscountPercentageAttribute()
    {
        if (!$this->compare_price_cents || !$this->final_price) {
            return null;
        }

        $discount = $this->compare_price_cents - $this->final_price;
        return round(($discount / $this->compare_price_cents) * 100, 2);
    }

    /**
     * Check if variant is on sale - SIMPLIFIED PRICING
     */
    public function getIsOnSaleAttribute()
    {
        return $this->compare_price_cents && $this->final_price && $this->compare_price_cents > $this->final_price;
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

    // ========================================
    // SIMPLIFIED INVENTORY METHODS (SKU-based)
    // ========================================

    /**
     * Simple stock reduction with overselling prevention
     * Returns true if successful, false if insufficient stock
     */
    public function reduceStockSimple($quantity)
    {
        if ($this->stock_quantity < $quantity) {
            return false; // Prevent overselling
        }

        $this->decrement('stock_quantity', $quantity);
        return true;
    }

    /**
     * Simple stock increase
     */
    public function increaseStockSimple($quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock()
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Get simple stock status for display
     */
    public function getSimpleStockStatus()
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock_quantity <= 5) { // configurable threshold
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Get stock display text
     */
    public function getStockDisplayText()
    {
        if ($this->stock_quantity > 0) {
            return "In Stock ({$this->stock_quantity} available)";
        }
        return "Out of Stock";
    }

    /**
     * Find variant by options (for simple variant matching)
     */
    public static function findByOptions($productId, $selectedOptions)
    {
        return static::where('product_id', $productId)
            ->get()
            ->first(function ($variant) use ($selectedOptions) {
                return ($variant->options ?? []) === $selectedOptions;
            });
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

    // ========================================
    // JSON VARIANT HELPER METHODS (Phase 4)
    // ========================================
    
    /**
     * Get variant options from JSON
     */
    public function getVariantOptions(): array
    {
        return $this->options ?? [];
    }
    
    /**
     * Get specific option value
     */
    public function getOptionValue(string $key, $default = null)
    {
        $options = $this->getVariantOptions();
        return isset($options[$key]['value']) ? $options[$key]['value'] : 
               (isset($options[$key]) && !is_array($options[$key]) ? $options[$key] : $default);
    }
    
    /**
     * Get option with full details
     */
    public function getOption(string $key): ?array
    {
        $options = $this->getVariantOptions();
        return $options[$key] ?? null;
    }
    
    /**
     * Check if variant has specific option
     */
    public function hasOption(string $key): bool
    {
        $options = $this->getVariantOptions();
        return isset($options[$key]);
    }
    
    /**
     * Get effective price (with override if set)
     */
    public function getEffectivePrice(): int
    {
        if ($this->override_price !== null) {
            return $this->override_price;
        }
        
        return $this->price_cents ?? $this->product->price_cents ?? 0;
    }
    
    /**
     * Get effective price in dollars
     */
    public function getEffectivePriceInDollars(): float
    {
        return $this->getEffectivePrice() / 100;
    }
    
    /**
     * Check if variant has been migrated to JSON system
     */
    public function isMigratedToJson(): bool
    {
        return $this->migrated_to_json === true;
    }
    
    /**
     * Get variant image URL or fallback to product images
     */
    public function getImageUrl(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }
        
        // Fallback to first product image
        $productImages = $this->product->images ?? [];
        return !empty($productImages) ? $productImages[0] : null;
    }

    
    /**
     * Get variant display name based on options
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }
        
        $options = $this->getVariantOptions();
        if (empty($options)) {
            return "Variant #{$this->id}";
        }
        
        $displayParts = [];
        foreach ($options as $key => $value) {
            $displayValue = is_array($value) ? ($value['value'] ?? $value) : $value;
            $displayParts[] = $displayValue;
        }
        
        return implode(' - ', $displayParts);
    }

}
