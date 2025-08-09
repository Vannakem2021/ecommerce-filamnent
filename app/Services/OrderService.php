<?php

namespace App\Services;

use App\Helpers\CartManagement;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    /**
     * Create an order from cart items
     */
    public function createOrderFromCart(array $cartItems, array $orderData, array $shippingAddress): Order
    {
        return DB::transaction(function () use ($cartItems, $orderData, $shippingAddress) {
            // 1. Validate inventory for all items
            $this->validateCartInventory($cartItems);
            
            // 2. Calculate order totals
            $totals = $this->calculateOrderTotals($cartItems, $orderData);
            
            // 3. Create the order
            $order = $this->createOrder($orderData, $totals);
            
            // 4. Create order items
            $this->createOrderItems($order, $cartItems);
            
            // 5. Create shipping address
            $this->createShippingAddress($order, $shippingAddress);
            
            // 6. Update inventory
            $this->updateInventory($cartItems);
            
            // 7. Process payment (placeholder for now)
            $this->processPayment($order, $orderData);
            
            // 8. Clear cart
            CartManagement::clearCartItems();
            
            // 9. Send confirmation email (placeholder)
            $this->sendOrderConfirmation($order);
            
            return $order->load(['items.product', 'items.variant', 'address']);
        });
    }
    
    /**
     * Validate inventory for all cart items
     */
    protected function validateCartInventory(array $cartItems): void
    {
        foreach ($cartItems as $item) {
            $this->validateItemInventory($item);
        }
    }
    
    /**
     * Validate inventory for a single cart item
     */
    protected function validateItemInventory(array $item): void
    {
        $productId = $item['product_id'];
        $variantId = $item['variant_id'] ?? null;
        $quantity = $item['quantity'];
        
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if (!$variant) {
                throw new Exception("Product variant not found: {$variantId}");
            }
            
            if ($variant->track_inventory && $variant->stock_quantity < $quantity) {
                throw new Exception("Insufficient stock for {$variant->name}. Available: {$variant->stock_quantity}, Requested: {$quantity}");
            }
        } else {
            $product = Product::find($productId);
            if (!$product) {
                throw new Exception("Product not found: {$productId}");
            }
            
            if ($product->track_inventory && $product->stock_quantity < $quantity) {
                throw new Exception("Insufficient stock for {$product->name}. Available: {$product->stock_quantity}, Requested: {$quantity}");
            }
        }
    }
    
    /**
     * Calculate order totals
     */
    protected function calculateOrderTotals(array $cartItems, array $orderData): array
    {
        $subtotal = 0;
        
        foreach ($cartItems as $item) {
            $subtotal += $item['total_amount'];
        }
        
        $taxRate = $orderData['tax_rate'] ?? 0.08;
        $shippingCost = $orderData['shipping_cost'] ?? 0;
        $discountAmount = $orderData['discount_amount'] ?? 0;
        
        $taxAmount = $subtotal * $taxRate;
        $grandTotal = $subtotal + $taxAmount + $shippingCost - $discountAmount;
        
        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingCost,
            'discount_amount' => $discountAmount,
            'grand_total' => $grandTotal,
        ];
    }
    
    /**
     * Create the order record
     */
    protected function createOrder(array $orderData, array $totals): Order
    {
        return Order::create([
            'user_id' => auth()->id(),
            'grand_total' => $totals['grand_total'],
            'payment_method' => $orderData['payment_method'] ?? 'card',
            'payment_status' => 'pending',
            'status' => 'new',
            'currency' => $orderData['currency'] ?? 'USD',
            'shipping_amount' => $totals['shipping_amount'],
            'shipping_method' => $orderData['shipping_method'] ?? 'standard',
            'notes' => $orderData['notes'] ?? null,
        ]);
    }
    
    /**
     * Create order items
     */
    protected function createOrderItems(Order $order, array $cartItems): void
    {
        foreach ($cartItems as $item) {
            $variantId = $item['variant_id'] ?? null;
            $variantSku = null;
            $variantAttributes = null;
            
            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                $variantSku = $variant->sku ?? null;
                $variantAttributes = $item['variant_attributes'] ?? null;
            }
            
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $variantId,
                'variant_sku' => $variantSku,
                'variant_attributes' => $variantAttributes,
                'quantity' => $item['quantity'],
                'unit_amount' => $item['unit_amount'],
                'total_amount' => $item['total_amount'],
            ]);
        }
    }
    
    /**
     * Create shipping address
     */
    protected function createShippingAddress(Order $order, array $addressData): void
    {
        Address::create([
            'order_id' => $order->id,
            'first_name' => $addressData['first_name'],
            'last_name' => $addressData['last_name'],
            'phone' => $addressData['phone'],
            'street_address' => $addressData['street_address'],
            'city' => $addressData['city'],
            'state' => $addressData['state'] ?? null,
            'zip_code' => $addressData['zip_code'],
        ]);
    }
    
    /**
     * Update inventory after order creation
     */
    protected function updateInventory(array $cartItems): void
    {
        foreach ($cartItems as $item) {
            $variantId = $item['variant_id'] ?? null;
            $quantity = $item['quantity'];
            
            if ($variantId) {
                $variant = ProductVariant::find($variantId);
                if ($variant && $variant->track_inventory) {
                    $variant->decrement('stock_quantity', $quantity);
                    
                    // Update stock status if needed
                    if ($variant->stock_quantity <= 0) {
                        $variant->update(['stock_status' => 'out_of_stock']);
                    } elseif ($variant->stock_quantity <= $variant->low_stock_threshold) {
                        $variant->update(['stock_status' => 'back_order']);
                    }
                }
            } else {
                $product = Product::find($item['product_id']);
                if ($product && $product->track_inventory) {
                    $product->decrement('stock_quantity', $quantity);
                    
                    // Update stock status if needed
                    if ($product->stock_quantity <= 0) {
                        $product->update(['stock_status' => 'out_of_stock', 'in_stock' => false]);
                    } elseif ($product->stock_quantity <= $product->low_stock_threshold) {
                        $product->update(['stock_status' => 'back_order']);
                    }
                }
            }
        }
    }
    
    /**
     * Process payment (placeholder implementation)
     */
    protected function processPayment(Order $order, array $orderData): void
    {
        // This is a placeholder for payment processing
        // In a real application, you would integrate with payment gateways like:
        // - Stripe
        // - PayPal
        // - Square
        // - etc.
        
        try {
            // Simulate payment processing
            $paymentMethod = $orderData['payment_method'] ?? 'card';
            
            if ($paymentMethod === 'card') {
                // Validate card details (already done in checkout validation)
                // Process payment with payment gateway
                // For now, we'll mark as paid
                $order->update(['payment_status' => 'paid']);
            } elseif ($paymentMethod === 'paypal') {
                // Process PayPal payment
                $order->update(['payment_status' => 'paid']);
            } else {
                // Handle other payment methods
                $order->update(['payment_status' => 'pending']);
            }
            
            Log::info("Payment processed for order {$order->id}", [
                'order_id' => $order->id,
                'amount' => $order->grand_total,
                'payment_method' => $paymentMethod,
            ]);
            
        } catch (Exception $e) {
            Log::error("Payment failed for order {$order->id}: " . $e->getMessage());
            $order->update(['payment_status' => 'failed']);
            throw new Exception("Payment processing failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send order confirmation email (placeholder)
     */
    protected function sendOrderConfirmation(Order $order): void
    {
        try {
            // This is a placeholder for email sending
            // In a real application, you would:
            // 1. Create email templates
            // 2. Use Laravel's Mail facade
            // 3. Queue the email for better performance
            
            Log::info("Order confirmation email sent for order {$order->id}", [
                'order_id' => $order->id,
                'user_email' => $order->user->email ?? 'guest',
            ]);
            
            // Example of how you might implement this:
            // Mail::to($order->user->email)->queue(new OrderConfirmationMail($order));
            
        } catch (Exception $e) {
            Log::error("Failed to send order confirmation email for order {$order->id}: " . $e->getMessage());
            // Don't throw exception here as order is already created
        }
    }
}
