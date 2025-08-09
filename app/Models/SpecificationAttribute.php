<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SpecificationAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'data_type',
        'unit',
        'scope',
        'is_filterable',
        'is_required',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
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
            if (empty($attribute->code)) {
                $attribute->code = Str::slug($attribute->name, '_');
            }
        });

        static::updating(function ($attribute) {
            if ($attribute->isDirty('name') && empty($attribute->code)) {
                $attribute->code = Str::slug($attribute->name, '_');
            }
        });
    }

    /**
     * Get the options for this attribute (for enum type)
     */
    public function options()
    {
        return $this->hasMany(SpecificationAttributeOption::class)->orderBy('sort_order');
    }

    /**
     * Get active options
     */
    public function activeOptions()
    {
        return $this->hasMany(SpecificationAttributeOption::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Get product specification values
     */
    public function productValues()
    {
        return $this->hasMany(ProductSpecificationValue::class);
    }

    /**
     * Get variant specification values
     */
    public function variantValues()
    {
        return $this->hasMany(VariantSpecificationValue::class);
    }

    /**
     * Scope for active attributes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filterable attributes
     */
    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }



    /**
     * Scope for product-level attributes
     */
    public function scopeProductLevel($query)
    {
        return $query->where('scope', 'product');
    }

    /**
     * Scope for variant-level attributes
     */
    public function scopeVariantLevel($query)
    {
        return $query->where('scope', 'variant');
    }

    /**
     * Scope for ordering by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the data type label
     */
    public function getDataTypeLabelAttribute()
    {
        return match($this->data_type) {
            'text' => 'Text',
            'number' => 'Number',
            'boolean' => 'Yes/No',
            'enum' => 'Select Option',
            default => 'Text'
        };
    }

    /**
     * Get the scope label
     */
    public function getScopeLabelAttribute()
    {
        return match($this->scope) {
            'product' => 'Product Level',
            'variant' => 'Variant Level',
            default => 'Product Level'
        };
    }

    /**
     * Check if this attribute is enum type
     */
    public function getIsEnumAttribute()
    {
        return $this->data_type === 'enum';
    }

    /**
     * Check if this attribute is numeric
     */
    public function getIsNumericAttribute()
    {
        return $this->data_type === 'number';
    }

    /**
     * Check if this attribute is boolean
     */
    public function getIsBooleanAttribute()
    {
        return $this->data_type === 'boolean';
    }

    /**
     * Get formatted display name with unit
     */
    public function getDisplayNameAttribute()
    {
        return $this->name . ($this->unit ? " ({$this->unit})" : '');
    }
}
