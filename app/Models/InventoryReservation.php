<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the product that owns the reservation.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant that owns the reservation.
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Scope to get active (non-expired) reservations
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get reservations for a specific product
     */
    public function scopeForProduct($query, $productId, $variantId = null)
    {
        return $query->where('product_id', $productId)
                    ->where('product_variant_id', $variantId);
    }

    /**
     * Scope to get reservations for a specific session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Check if this reservation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Extend the reservation expiry time
     */
    public function extend(int $minutes = 15): bool
    {
        $this->expires_at = now()->addMinutes($minutes);
        return $this->save();
    }

    /**
     * Clean up expired reservations
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Get total reserved quantity for a product/variant
     */
    public static function getReservedQuantity($productId, $variantId = null): int
    {
        return static::active()
            ->forProduct($productId, $variantId)
            ->sum('quantity');
    }

    /**
     * Create a new reservation
     */
    public static function createReservation($sessionId, $productId, $variantId, $quantity, $expiryMinutes = 15): self
    {
        return static::create([
            'session_id' => $sessionId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Release reservations for a session
     */
    public static function releaseForSession($sessionId): int
    {
        return static::forSession($sessionId)->delete();
    }
}
