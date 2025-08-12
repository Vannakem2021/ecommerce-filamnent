<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class PaymentConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'environment',
        'merchant_id',
        'api_key',
        'webhook_secret',
        'api_url',
        'checkout_url',
        'configuration',
        'supported_currencies',
        'supported_payment_options',
        'is_active',
        'is_default',
        'min_amount',
        'max_amount',
        'timeout_seconds',
    ];

    protected $casts = [
        'configuration' => 'array',
        'supported_currencies' => 'array',
        'supported_payment_options' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'timeout_seconds' => 'integer',
    ];

    /**
     * Environment constants
     */
    const ENV_SANDBOX = 'sandbox';
    const ENV_PRODUCTION = 'production';

    /**
     * Encrypt the API key when storing
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypt the webhook secret when storing
     */
    protected function webhookSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Scope to get active configurations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to filter by environment
     */
    public function scopeEnvironment(Builder $query, string $environment): Builder
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope to get default configuration for provider
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Get configuration for ABA Pay in current environment
     */
    public static function getAbaPayConfig(): ?self
    {
        $environment = config('app.env') === 'production' ? self::ENV_PRODUCTION : self::ENV_SANDBOX;

        return self::provider('aba_pay')
            ->environment($environment)
            ->active()
            ->first();
    }

    /**
     * Check if configuration supports a currency
     */
    public function supportsCurrency(string $currency): bool
    {
        $currencies = $this->supported_currencies;
        if (empty($currencies) || !is_array($currencies)) {
            return true;
        }

        return in_array(strtoupper($currency), $currencies);
    }

    /**
     * Check if configuration supports a payment option
     */
    public function supportsPaymentOption(string $option): bool
    {
        $options = $this->supported_payment_options;
        if (empty($options) || !is_array($options)) {
            return true;
        }

        return in_array($option, $options);
    }

    /**
     * Get configuration value by key
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Check if amount is within limits
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
     * Get the appropriate API URL based on environment
     */
    public function getApiUrlAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Fallback URLs based on environment
        if ($this->environment === self::ENV_PRODUCTION) {
            return 'https://checkout.payway.com.kh/api/payment-gateway/v1/payments/purchase';
        }

        return 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase';
    }

    /**
     * Check if this is a production configuration
     */
    public function isProduction(): bool
    {
        return $this->environment === self::ENV_PRODUCTION;
    }

    /**
     * Check if this is a sandbox configuration
     */
    public function isSandbox(): bool
    {
        return $this->environment === self::ENV_SANDBOX;
    }
}
