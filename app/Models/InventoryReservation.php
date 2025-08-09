<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'session_id',
        'user_id',
        'status',
        'expires_at',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            // Set default expiration time (30 minutes from now)
            if (!$reservation->expires_at) {
                $reservation->expires_at = Carbon::now()->addMinutes(30);
            }
        });
    }

    /**
     * Get the product this reservation belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant this reservation belongs to
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the user this reservation belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active reservations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Check if reservation is expired
     */
    public function isExpired()
    {
        return $this->status === 'active' && $this->expires_at <= now();
    }

    /**
     * Mark reservation as expired
     */
    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);
        return $this;
    }

    /**
     * Mark reservation as fulfilled
     */
    public function markAsFulfilled()
    {
        $this->update(['status' => 'fulfilled']);
        return $this;
    }

    /**
     * Mark reservation as cancelled
     */
    public function markAsCancelled()
    {
        $this->update(['status' => 'cancelled']);
        return $this;
    }

    /**
     * Extend reservation expiration time
     */
    public function extend($minutes = 30)
    {
        if ($this->status === 'active') {
            $this->update(['expires_at' => Carbon::now()->addMinutes($minutes)]);
        }
        return $this;
    }
}
