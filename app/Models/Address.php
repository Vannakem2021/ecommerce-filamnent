<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Address extends Model
{
    use HasFactory;

    protected $table = "addresses";

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'contact_name',
        'phone_number',
        'house_number',
        'street_number',
        'city_province',
        'district_khan',
        'commune_sangkat',
        'postal_code',
        'additional_info',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the address.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Set this address as the default for the user and type.
     */
    public function setAsDefault()
    {
        if ($this->user_id) {
            // Remove default status from other addresses of the same type
            static::where('user_id', $this->user_id)
                ->where('type', $this->type)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            // Set this address as default
            $this->update(['is_default' => true]);
        }
    }

    /**
     * Get the full address formatted for display.
     */
    public function getFormattedAddressAttribute()
    {
        $parts = array_filter([
            $this->house_number,
            $this->street_number,
            $this->commune_sangkat,
            $this->district_khan,
            $this->city_province,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Validation rules for address creation/update.
     */
    public static function validationRules()
    {
        return [
            'type' => ['required', Rule::in(['shipping', 'billing'])],
            'contact_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'house_number' => 'nullable|string|max:100',
            'street_number' => 'nullable|string|max:100',
            'city_province' => 'required|string|max:255',
            'district_khan' => 'required|string|max:255',
            'commune_sangkat' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'additional_info' => 'nullable|string|max:500',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Scope to get addresses for a specific user and type.
     */
    public function scopeForUserAndType($query, $userId, $type)
    {
        return $query->where('user_id', $userId)->where('type', $type);
    }

    /**
     * Scope to get default address for a user and type.
     */
    public function scopeDefaultForUserAndType($query, $userId, $type)
    {
        return $query->where('user_id', $userId)
                    ->where('type', $type)
                    ->where('is_default', true);
    }
}
