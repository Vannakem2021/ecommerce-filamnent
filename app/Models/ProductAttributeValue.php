<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_attribute_id',
        'value',
        'slug',
        'color_code',
        'description',
        'is_active',
        'sort_order',
        'price_modifier_cents',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($value) {
            if (empty($value->slug)) {
                $value->slug = Str::slug($value->value);
            }
        });

        static::updating(function ($value) {
            if ($value->isDirty('value') && empty($value->slug)) {
                $value->slug = Str::slug($value->value);
            }
        });
    }

    /**
     * Get the attribute this value belongs to
     */
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    /**
     * Get variants that use this attribute value
     */
    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_attributes');
    }

    /**
     * Scope for active values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('value');
    }

    /**
     * Get the display value (with color indicator if applicable)
     */
    public function getDisplayValueAttribute()
    {
        if ($this->color_code) {
            return $this->value . ' (' . $this->color_code . ')';
        }
        
        return $this->value;
    }

    /**
     * Check if this is a color attribute value
     */
    public function getIsColorAttribute()
    {
        return $this->attribute && $this->attribute->type === 'color';
    }

    /**
     * Get the price modifier in dollars from cents
     */
    public function getPriceModifierAttribute()
    {
        return $this->price_modifier_cents ? $this->price_modifier_cents / 100 : 0;
    }

    /**
     * Set the price modifier in cents from dollars
     */
    public function setPriceModifierAttribute($value)
    {
        $this->attributes['price_modifier_cents'] = $value ? round($value * 100) : 0;
    }

    /**
     * Get formatted price modifier for display (e.g., "+$30.00", "-$10.00", "$0.00")
     */
    public function getFormattedPriceModifierAttribute()
    {
        $modifier = $this->price_modifier;

        if ($modifier > 0) {
            return '+$' . number_format($modifier, 2);
        } elseif ($modifier < 0) {
            return '-$' . number_format(abs($modifier), 2);
        } else {
            return '$0.00';
        }
    }
}
