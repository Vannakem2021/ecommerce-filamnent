<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attribute) {
            if (empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });

        static::updating(function ($attribute) {
            if ($attribute->isDirty('name') && empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });
    }

    /**
     * Get the attribute values for this attribute
     */
    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order');
    }

    /**
     * Get active attribute values
     */
    public function activeValues()
    {
        return $this->hasMany(ProductAttributeValue::class)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get products that use this attribute
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variant_attributes')
            ->through(ProductVariant::class)
            ->distinct();
    }

    /**
     * Scope for active attributes
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
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the attribute type label
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'select' => 'Select',
            'color' => 'Color',
            'text' => 'Text',
            default => 'Select'
        };
    }
}
