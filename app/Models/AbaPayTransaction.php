<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AbaPayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'merchant_id',
        'amount',
        'currency',
        'status',
        'payment_option',
        'payment_gate',
        'request_time',
        'hash',
        'shipping',
        'type',
        'view_type',
        'customer_info',
        'urls',
        'custom_fields',
        'response_data',
        'webhook_data',
        'processed_at',
        'webhook_received_at',
        'error_message',
        'payway_status_code',
        'payway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'customer_info' => 'array',
        'urls' => 'array',
        'custom_fields' => 'array',
        'response_data' => 'array',
        'webhook_data' => 'array',
        'payway_response' => 'array',
        'processed_at' => 'datetime',
        'webhook_received_at' => 'datetime',
    ];

    /**
     * Transaction status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Transaction type constants
     */
    const TYPE_PURCHASE = 'purchase';
    const TYPE_PRE_AUTH = 'pre-auth';

    /**
     * Scope to filter by status
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending transactions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get completed transactions
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get failed transactions
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction has failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(array $responseData = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'response_data' => array_merge($this->response_data ?? [], $responseData),
        ]);
    }

    /**
     * Mark transaction as completed with PayWay response
     */
    public function markAsCompletedWithPayWay($payWayResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payway_status_code' => '00',
            'payway_response' => $payWayResponse,
            'processed_at' => now()
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $errorMessage, array $responseData = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processed_at' => now(),
            'error_message' => $errorMessage,
            'response_data' => array_merge($this->response_data ?? [], $responseData),
        ]);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get customer name from customer info
     */
    public function getCustomerNameAttribute(): ?string
    {
        $info = $this->customer_info;
        if (!$info) return null;

        $firstName = $info['firstname'] ?? '';
        $lastName = $info['lastname'] ?? '';

        return trim($firstName . ' ' . $lastName) ?: null;
    }

    /**
     * Relationship with order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
