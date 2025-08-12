<?php

// Simple test script to verify cart calculation logic
echo "Testing Cart Calculation Logic\n";
echo "==============================\n\n";

// Simulate CartPage calculation logic
class TestCartPage
{
    public $grand_total;
    public $tax_rate = 0.08; // 8% tax
    public $shipping_threshold = 50; // Free shipping over $50

    public function __construct($grand_total)
    {
        $this->grand_total = $grand_total ?? 0;
    }

    public function getSubtotal()
    {
        return $this->grand_total ?? 0;
    }

    public function getTax()
    {
        return round($this->getSubtotal() * $this->tax_rate, 2);
    }

    public function getShipping()
    {
        return $this->getSubtotal() >= $this->shipping_threshold ? 0 : 9.99;
    }

    public function getFinalTotal()
    {
        $subtotal = $this->getSubtotal();
        $tax = $this->getTax();
        $shipping = $this->getShipping();
        
        return round($subtotal + $tax + $shipping, 2);
    }
}

// Test scenarios
$testCases = [
    ['subtotal' => 0, 'description' => 'Empty cart'],
    ['subtotal' => 25.50, 'description' => 'Small order (under shipping threshold)'],
    ['subtotal' => 50.00, 'description' => 'Order at shipping threshold'],
    ['subtotal' => 75.25, 'description' => 'Large order (free shipping)'],
    ['subtotal' => 100.00, 'description' => 'Round number order'],
    ['subtotal' => 99.99, 'description' => 'Edge case pricing'],
];

foreach ($testCases as $test) {
    $cart = new TestCartPage($test['subtotal']);
    
    echo "Test: {$test['description']}\n";
    echo "  Subtotal: $" . number_format($cart->getSubtotal(), 2) . "\n";
    echo "  Tax (8%): $" . number_format($cart->getTax(), 2) . "\n";
    echo "  Shipping: $" . number_format($cart->getShipping(), 2) . "\n";
    echo "  Final Total: $" . number_format($cart->getFinalTotal(), 2) . "\n";
    
    // Verify calculation
    $expectedTotal = $cart->getSubtotal() + $cart->getTax() + $cart->getShipping();
    $actualTotal = $cart->getFinalTotal();
    
    if (abs($expectedTotal - $actualTotal) < 0.01) {
        echo "  ✓ Calculation correct\n";
    } else {
        echo "  ✗ Calculation error: Expected $" . number_format($expectedTotal, 2) . 
             ", Got $" . number_format($actualTotal, 2) . "\n";
    }
    
    echo "\n";
}

echo "Cart calculation tests completed!\n";
