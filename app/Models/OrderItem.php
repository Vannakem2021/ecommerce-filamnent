<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = "order_items";

    protected $casts = [
        'variant_attributes' => 'array',
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'variant_sku',
        'variant_attributes',
        'quantity',
        'unit_amount',
        'total_amount',
    ];

    public function orders()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant for this order item
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Get the effective item name (variant name or product name)
     */
    public function getEffectiveNameAttribute()
    {
        if ($this->variant) {
            return $this->variant->full_name;
        }

        return $this->product ? $this->product->name : 'Unknown Product';
    }

    /**
     * Get the effective SKU (variant SKU or product SKU)
     */
    public function getEffectiveSkuAttribute()
    {
        if ($this->variant_sku) {
            return $this->variant_sku;
        }

        if ($this->variant) {
            return $this->variant->sku;
        }

        return $this->product ? $this->product->sku : null;
    }
}
