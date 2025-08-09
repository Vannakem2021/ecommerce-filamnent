<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SpecificationAttributeOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'specification_attribute_id',
        'value',
        'slug',
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

        static::creating(function ($option) {
            if (empty($option->slug)) {
                $option->slug = Str::slug($option->value);
            }
        });

        static::updating(function ($option) {
            if ($option->isDirty('value') && empty($option->slug)) {
                $option->slug = Str::slug($option->value);
            }
        });
    }

    /**
     * Get the specification attribute this option belongs to
     */
    public function specificationAttribute()
    {
        return $this->belongsTo(SpecificationAttribute::class);
    }

    /**
     * Get product specification values using this option
     */
    public function productSpecificationValues()
    {
        return $this->hasMany(ProductSpecificationValue::class);
    }

    /**
     * Get variant specification values using this option
     */
    public function variantSpecificationValues()
    {
        return $this->hasMany(VariantSpecificationValue::class);
    }

    /**
     * Scope for active options
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
     * Get the display value
     */
    public function getDisplayValueAttribute()
    {
        return $this->value;
    }
}
