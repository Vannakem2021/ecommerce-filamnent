<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Specifying the database table associated with this model
    protected $table = "orders";

    protected $fillable = [
        'user_id',
        'grand_total',
        'payment_method',
        'payment_status',
        'status',
        'currency',
        'shipping_amount',
        'shipping_method',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Get the payment method model for this order
     */
    public function paymentMethodModel()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }

    /**
     * Get ABA Pay transactions for this order
     */
    public function abaPayTransactions()
    {
        return $this->hasMany(AbaPayTransaction::class);
    }

    /**
     * Get the latest ABA Pay transaction for this order
     */
    public function latestAbaPayTransaction()
    {
        return $this->hasOne(AbaPayTransaction::class)->latestOfMany();
    }

    /**
     * Check if this order uses ABA Pay
     */
    public function isAbaPayOrder(): bool
    {
        return $this->payment_method === 'aba_pay';
    }

    /**
     * Get formatted grand total with currency
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->grand_total, 2) . ' ' . ($this->currency ?? 'USD');
    }
}
