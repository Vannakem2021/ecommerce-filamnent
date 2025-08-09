<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSpecificationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'specification_attribute_id',
        'value_text',
        'value_number',
        'value_boolean',
        'specification_attribute_option_id',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_boolean' => 'boolean',
    ];

    /**
     * Get the product this specification value belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the specification attribute
     */
    public function specificationAttribute()
    {
        return $this->belongsTo(SpecificationAttribute::class);
    }

    /**
     * Get the specification attribute option (for enum types)
     */
    public function specificationAttributeOption()
    {
        return $this->belongsTo(SpecificationAttributeOption::class);
    }

    /**
     * Get the formatted value based on data type
     */
    public function getFormattedValueAttribute()
    {
        $attribute = $this->specificationAttribute;
        
        if (!$attribute) {
            return $this->value_text;
        }

        return match($attribute->data_type) {
            'text' => $this->value_text,
            'number' => $this->formatNumberValue(),
            'boolean' => $this->value_boolean ? 'Yes' : 'No',
            'enum' => $this->specificationAttributeOption?->value ?? $this->value_text,
            default => $this->value_text
        };
    }

    /**
     * Get the raw value based on data type
     */
    public function getRawValueAttribute()
    {
        $attribute = $this->specificationAttribute;
        
        if (!$attribute) {
            return $this->value_text;
        }

        return match($attribute->data_type) {
            'text' => $this->value_text,
            'number' => $this->value_number,
            'boolean' => $this->value_boolean,
            'enum' => $this->specificationAttributeOption?->value ?? $this->value_text,
            default => $this->value_text
        };
    }

    /**
     * Format number value with unit
     */
    protected function formatNumberValue()
    {
        if ($this->value_number === null) {
            return null;
        }

        $formatted = number_format($this->value_number, $this->getDecimalPlaces());
        $unit = $this->specificationAttribute->unit;
        
        return $unit ? "{$formatted} {$unit}" : $formatted;
    }

    /**
     * Get appropriate decimal places for display
     */
    protected function getDecimalPlaces()
    {
        if ($this->value_number == floor($this->value_number)) {
            return 0; // No decimals for whole numbers
        }
        
        return 2; // Default to 2 decimal places
    }

    /**
     * Set value based on data type
     */
    public function setValue($value)
    {
        $attribute = $this->specificationAttribute;
        
        if (!$attribute) {
            $this->value_text = $value;
            return;
        }

        // Clear all value fields first
        $this->value_text = null;
        $this->value_number = null;
        $this->value_boolean = null;
        $this->specification_attribute_option_id = null;

        match($attribute->data_type) {
            'text' => $this->value_text = $value,
            'number' => $this->value_number = is_numeric($value) ? (float) $value : null,
            'boolean' => $this->value_boolean = (bool) $value,
            'enum' => $this->handleEnumValue($value),
            default => $this->value_text = $value
        };
    }

    /**
     * Handle enum value assignment
     */
    protected function handleEnumValue($value)
    {
        if (is_numeric($value)) {
            // Assume it's an option ID
            $this->specification_attribute_option_id = $value;
        } else {
            // Try to find the option by value
            $option = $this->specificationAttribute
                ->options()
                ->where('value', $value)
                ->first();
            
            if ($option) {
                $this->specification_attribute_option_id = $option->id;
            } else {
                // Store as text if option not found
                $this->value_text = $value;
            }
        }
    }
}
