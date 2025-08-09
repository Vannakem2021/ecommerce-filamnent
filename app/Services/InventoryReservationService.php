<?php

namespace App\Services;

use App\Models\InventoryReservation;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryReservationService
{
    /**
     * Reserve inventory for a product or variant
     */
    public function reserve(
        int $productId,
        int $quantity = 1,
        ?int $variantId = null,
        ?string $sessionId = null,
        ?int $userId = null,
        string $referenceType = 'cart',
        ?string $referenceId = null,
        int $expirationMinutes = 30
    ): array {
        return DB::transaction(function () use (
            $productId, $quantity, $variantId, $sessionId, $userId, 
            $referenceType, $referenceId, $expirationMinutes
        ) {
            // Check available inventory
            $available = $this->getAvailableInventory($productId, $variantId);
            
            if ($available < $quantity) {
                return [
                    'success' => false,
                    'message' => "Insufficient inventory. Available: {$available}, Requested: {$quantity}",
                    'available' => $available
                ];
            }

            // Create reservation
            $reservation = InventoryReservation::create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'session_id' => $sessionId,
                'user_id' => $userId,
                'status' => 'active',
                'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return [
                'success' => true,
                'reservation' => $reservation,
                'message' => 'Inventory reserved successfully'
            ];
        });
    }

    /**
     * Release a reservation
     */
    public function release(int $reservationId): bool
    {
        $reservation = InventoryReservation::find($reservationId);
        
        if (!$reservation || $reservation->status !== 'active') {
            return false;
        }

        $reservation->markAsCancelled();
        return true;
    }

    /**
     * Release reservations by reference
     */
    public function releaseByReference(string $referenceType, string $referenceId): int
    {
        return InventoryReservation::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Fulfill a reservation (convert to actual inventory reduction)
     */
    public function fulfill(int $reservationId): array
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = InventoryReservation::find($reservationId);
            
            if (!$reservation || $reservation->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Reservation not found or not active'
                ];
            }

            // Reduce actual inventory
            if ($reservation->product_variant_id) {
                $variant = ProductVariant::find($reservation->product_variant_id);
                if (!$variant->reduceStock($reservation->quantity)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to reduce variant inventory'
                    ];
                }
            } else {
                $product = Product::find($reservation->product_id);
                if (!$product->reduceStock($reservation->quantity)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to reduce product inventory'
                    ];
                }
            }

            // Mark reservation as fulfilled
            $reservation->markAsFulfilled();

            return [
                'success' => true,
                'message' => 'Reservation fulfilled successfully'
            ];
        });
    }

    /**
     * Get available inventory considering reservations
     */
    public function getAvailableInventory(int $productId, ?int $variantId = null): int
    {
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if (!$variant || !$variant->track_inventory) {
                return PHP_INT_MAX; // Unlimited if not tracking
            }
            
            $totalStock = $variant->stock_quantity;
            $reservedQuantity = $this->getReservedQuantity($productId, $variantId);
            
            return max(0, $totalStock - $reservedQuantity);
        } else {
            $product = Product::find($productId);
            if (!$product || !$product->track_inventory) {
                return PHP_INT_MAX; // Unlimited if not tracking
            }
            
            $totalStock = $product->stock_quantity;
            $reservedQuantity = $this->getReservedQuantity($productId);
            
            return max(0, $totalStock - $reservedQuantity);
        }
    }

    /**
     * Get total reserved quantity for a product or variant
     */
    public function getReservedQuantity(int $productId, ?int $variantId = null): int
    {
        $query = InventoryReservation::active()
            ->where('product_id', $productId);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        return $query->sum('quantity');
    }

    /**
     * Clean up expired reservations
     */
    public function cleanupExpiredReservations(): int
    {
        return InventoryReservation::expired()->update(['status' => 'expired']);
    }

    /**
     * Extend reservation expiration
     */
    public function extendReservation(int $reservationId, int $minutes = 30): bool
    {
        $reservation = InventoryReservation::find($reservationId);
        
        if (!$reservation || $reservation->status !== 'active') {
            return false;
        }

        $reservation->extend($minutes);
        return true;
    }

    /**
     * Get user's active reservations
     */
    public function getUserReservations(?int $userId = null, ?string $sessionId = null)
    {
        $query = InventoryReservation::active()
            ->with(['product', 'productVariant']);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->get();
    }

    /**
     * Check if sufficient inventory is available for reservation
     */
    public function canReserve(int $productId, int $quantity, ?int $variantId = null): bool
    {
        $available = $this->getAvailableInventory($productId, $variantId);
        return $available >= $quantity;
    }
}
