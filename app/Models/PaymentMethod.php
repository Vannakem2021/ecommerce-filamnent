<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'provider',
        'is_active',
        'configuration',
        'sort_order',
        'description',
        'icon',
        'min_amount',
        'max_amount',
        'supported_currencies',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'configuration' => 'array',
        'supported_currencies' => 'array',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to get only active payment methods
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope to filter by provider
     */
    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if payment method supports a specific currency
     */
    public function supportsCurrency(string $currency): bool
    {
        $currencies = $this->supported_currencies;
        if (empty($currencies) || !is_array($currencies)) {
            return true; // If no currencies specified, assume all are supported
        }

        return in_array(strtoupper($currency), $currencies);
    }

    /**
     * Check if amount is within allowed limits
     */
    public function isAmountValid(float $amount): bool
    {
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get configuration value by key
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Check if this is an ABA Pay method
     */
    public function isAbaPayMethod(): bool
    {
        return $this->provider === 'aba_pay';
    }

    /**
     * Get the display name with icon
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->icon) {
            return "<i class='{$this->icon}'></i> {$this->name}";
        }

        return $this->name;
    }

    /**
     * Relationship with orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_method', 'code');
    }

    /**
     * Relationship with ABA Pay transactions
     */
    public function abaPayTransactions()
    {
        return $this->hasMany(AbaPayTransaction::class, 'payment_method_code', 'code');
    }
}
