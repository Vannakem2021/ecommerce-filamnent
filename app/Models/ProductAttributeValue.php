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
}
